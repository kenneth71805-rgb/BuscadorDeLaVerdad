<?php

namespace App\Services;

use App\Enums\ArticleStatus as S;
use App\Models\Article;
use App\Models\User;

/** Turns editorial status changes into in-app notifications for the right people. */
class ArticleNotifier
{
    public function handle(Article $article, S $to, ?User $actor, ?string $reason): void
    {
        $url = route('articles.show', $article, false);
        $title = "“{$article->title}”";
        $author = $article->author;

        match ($to) {
            S::Submitted, S::Resubmitted => $this->toReviewers($article, "{$title} is waiting for category review.", $url, $actor),
            S::CategoryApproved => Notifier::send(Notifier::editors(), "{$title} was approved by the category and awaits your editorial review.", $url, 'review', $actor),
            S::RevisionRequested => Notifier::send([$author], "Revision requested on {$title}".($reason ? ": {$reason}" : '.'), $url, 'revision', $actor),
            S::Rejected => Notifier::send([$author], "{$title} was not accepted".($reason ? ": {$reason}" : '.'), $url, 'rejected', $actor),
            S::Approved => Notifier::send([$author], "{$title} was approved.", $url, 'approved', $actor),
            S::Scheduled => Notifier::send([$author], "{$title} is scheduled for ".$article->scheduled_for?->format('M j, Y g:i A').'.', $url, 'scheduled', $actor),
            S::Published => Notifier::send([$author], "{$title} is now published.", $url, 'published', $actor),
            default => null,
        };
    }

    private function toReviewers(Article $article, string $message, string $url, ?User $actor): void
    {
        $reviewers = Notifier::reviewers($article->category_id)->reject(fn (User $u) => $u->id === $article->author_id);

        // If nobody can review this category, the Editor-in-Chief is told instead so nothing sits unseen.
        Notifier::send($reviewers->isNotEmpty() ? $reviewers : Notifier::editors(), $message, $url, 'review', $actor);
    }
}
