<?php

namespace App\Services;

use App\Enums\ArticleStatus as S;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Every status change goes through here: legal move, authorized actor,
 * reason where required, history + audit written in one transaction.
 */
class ArticleWorkflow
{
    public function transition(
        Article $article,
        S $to,
        User $actor,
        ?string $reason = null,
        ?CarbonInterface $scheduledFor = null,
    ): Article {
        return DB::transaction(function () use ($article, $to, $actor, $reason, $scheduledFor) {
            $article = Article::query()->lockForUpdate()->findOrFail($article->getKey());
            $from = $article->status;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidStatusTransition("Cannot move an article from {$from->value} to {$to->value}.");
            }

            Gate::forUser($actor)->authorize('transition', [$article, $to]);

            $reason = $reason !== null ? trim($reason) : null;
            $isOverride = $actor->hasRole(Role::Admin);

            if ((S::requiresReason($to) || $isOverride) && ($reason === null || $reason === '')) {
                throw new InvalidStatusTransition('A written reason is required for this decision.');
            }

            if (in_array($to, [S::Scheduled, S::Published], true) && $article->leadMedia && ! $article->leadMedia->is_public) {
                throw new InvalidStatusTransition('The lead photo has not been approved for public release. Approve it first (or remove it from the article).');
            }

            if ($to === S::Scheduled) {
                if ($scheduledFor === null || $scheduledFor->isPast()) {
                    throw new InvalidStatusTransition('Scheduling needs a future date and time.');
                }
                $article->scheduled_for = $scheduledFor;
            }

            if ($to === S::Published) {
                $article->published_at = now();
                $article->scheduled_for = null;
            }

            $article->status = $to;
            $article->save();

            $this->log($article, $from, $to, $actor, $reason, $isOverride);

            return $article;
        });
    }

    /** Called by the scheduler; moves due Scheduled articles to Published. */
    public function publishDueScheduled(): int
    {
        $count = 0;

        Article::query()
            ->where('status', S::Scheduled->value)
            ->where('scheduled_for', '<=', now())
            ->each(function (Article $article) use (&$count) {
                DB::transaction(function () use ($article, &$count) {
                    $article = Article::query()->lockForUpdate()->find($article->getKey());
                    if ($article === null || $article->status !== S::Scheduled) {
                        return;
                    }
                    $article->status = S::Published;
                    $article->published_at = $article->scheduled_for;
                    $article->scheduled_for = null;
                    $article->save();
                    $this->log($article, S::Scheduled, S::Published, null, 'Scheduled publication', false);
                    $count++;
                });
            });

        return $count;
    }

    private function log(Article $article, S $from, S $to, ?User $actor, ?string $reason, bool $override): void
    {
        ArticleStatusHistory::create([
            'article_id' => $article->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'actor_id' => $actor?->id,
            'reason' => $reason,
        ]);

        AuditLog::record(
            $override ? 'article.override' : 'article.status_changed',
            $article,
            ['from' => $from->value, 'to' => $to->value, 'reason' => $reason],
            $actor,
        );

        // A notification problem must never block an editorial decision.
        try {
            app(ArticleNotifier::class)->handle($article->loadMissing('author'), $to, $actor, $reason);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
