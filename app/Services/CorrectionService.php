<?php

namespace App\Services;

use App\Enums\ArticleStatus as S;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Corrections to published articles. The live article stays exactly as it is until the
 * Editor-in-Chief approves the change; then the new text replaces it and the old text is kept.
 */
class CorrectionService
{
    public function propose(Article $article, User $user, array $data): ArticleRevision
    {
        return DB::transaction(function () use ($article, $user, $data) {
            $article = Article::query()->lockForUpdate()->findOrFail($article->id);

            if ($article->status !== S::Published) {
                throw new InvalidStatusTransition('Only published articles can be corrected.');
            }
            if ($article->revisions()->where('status', 'pending')->exists()) {
                throw new InvalidStatusTransition('A correction for this article is already waiting for review.');
            }

            $revision = ArticleRevision::create([
                'article_id' => $article->id,
                'version' => ((int) $article->revisions()->max('version')) + 1,
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'body' => $data['body'],
                'reason' => $data['reason'],
                'public_note' => $data['public_note'] ?? null,
                'requested_by' => $user->id,
            ]);
            AuditLog::record('article.correction_proposed', $article, ['revision' => $revision->id], $user);

            if ($user->hasRole(Role::EditorInChief)) {
                // The Editor-in-Chief holds final authority, so their own correction is applied at once (and recorded as such).
                $this->apply($article, $revision, $user, 'Approved by the Editor-in-Chief who proposed it.');
            } else {
                Notifier::send(Notifier::editors(), "A correction to “{$article->title}” needs your review.", route('corrections.show', $revision, false), 'correction', $user);
            }

            return $revision;
        });
    }

    public function decide(ArticleRevision $revision, User $user, bool $approve, ?string $reason): ArticleRevision
    {
        return DB::transaction(function () use ($revision, $user, $approve, $reason) {
            $revision = ArticleRevision::query()->lockForUpdate()->findOrFail($revision->id);
            $article = Article::query()->lockForUpdate()->findOrFail($revision->article_id);
            $reason = $reason !== null ? trim($reason) : null;

            if ($revision->status !== 'pending') {
                throw new InvalidStatusTransition('This correction has already been decided.');
            }

            if ($approve) {
                if ($article->status !== S::Published) {
                    throw new InvalidStatusTransition('The article is no longer published, so this correction cannot be applied.');
                }
                $this->apply($article, $revision, $user, $reason);
            } else {
                if ($reason === null || $reason === '') {
                    throw new InvalidStatusTransition('Please explain why the correction is declined.');
                }
                $revision->forceFill(['status' => 'declined', 'decided_by' => $user->id, 'decided_at' => now(), 'decision_reason' => $reason])->save();
                AuditLog::record('article.correction_declined', $article, ['revision' => $revision->id, 'reason' => $reason], $user);
            }

            $word = $approve ? 'approved' : 'declined';
            Notifier::send([$revision->requester], "Your correction to “{$article->title}” was {$word}".($reason ? ": {$reason}" : '.'), route('articles.show', $article, false), 'correction', $user);

            return $revision->fresh();
        });
    }

    private function apply(Article $article, ArticleRevision $revision, User $user, ?string $note): void
    {
        $revision->forceFill([
            'previous_title' => $article->title, 'previous_subtitle' => $article->subtitle, 'previous_body' => $article->body,
            'status' => 'applied', 'decided_by' => $user->id, 'decided_at' => now(), 'decision_reason' => $note,
        ])->save();

        // slug and published_at never change, so the public address and publication date stay stable.
        $article->forceFill(['title' => $revision->title, 'subtitle' => $revision->subtitle, 'body' => $revision->body, 'corrected_at' => now()])->save();

        AuditLog::record('article.corrected', $article, ['revision' => $revision->id, 'public_note' => (bool) $revision->public_note], $user);
    }
}
