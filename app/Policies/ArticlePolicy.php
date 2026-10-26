<?php

namespace App\Policies;

use App\Enums\ArticleStatus as S;
use App\Enums\Role;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        if ($user->hasRole(Role::Admin, Role::EditorInChief)) {
            return true;
        }

        return $user->headsCategory($article->category_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::EditorInChief, Role::CategoryHead);
    }

    public function update(User $user, Article $article): bool
    {
        // Live and archived articles are never edited in place; corrections
        // will go through a pending revision (planned) so the public text stays stable.
        if (in_array($article->status, [S::Published, S::Archived], true)) {
            return false;
        }

        if ($user->hasRole(Role::Admin, Role::EditorInChief)) {
            return true;
        }

        return $user->headsCategory($article->category_id)
            && in_array($article->status, [S::Draft, S::RevisionRequested], true);
    }

    /**
     * Who may move an article to a given status. The status graph itself
     * (what is a legal move at all) lives in ArticleStatus.
     */
    public function transition(User $user, Article $article, S $to): bool
    {
        $from = $article->status;

        if (! $user->is_active || ! $from->canTransitionTo($to)) {
            return false;
        }

        if ($user->hasRole(Role::Admin)) {
            return (bool) $user->can_override; // reason + audit enforced by ArticleWorkflow
        }

        if ($user->hasRole(Role::EditorInChief)) {
            return true;
        }

        if (! $user->headsCategory($article->category_id)) {
            return false;
        }

        $isAuthor = $article->author_id === $user->id;
        $canReview = $user->canReviewCategory($article->category_id) && ! $isAuthor;

        return match ($to) {
            S::Submitted, S::Resubmitted, S::Draft => true,
            S::CategoryApproved => $canReview,
            S::RevisionRequested => $canReview
                && in_array($from, [S::Submitted, S::CategoryApproved, S::Resubmitted], true),
            S::Scheduled, S::Published => (bool) $user->can_publish && $from === S::Approved,
            default => false, // InReview, Approved, Rejected, Archived: Editor-in-Chief only
        };
    }
}
