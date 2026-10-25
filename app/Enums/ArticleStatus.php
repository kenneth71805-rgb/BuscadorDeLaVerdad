<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Editorial lifecycle. The transition map is the single source of truth;
 * services and policies must call canTransitionTo() on the server.
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case CategoryApproved = 'category_approved';
    case InReview = 'in_review';
    case RevisionRequested = 'revision_requested';
    case Resubmitted = 'resubmitted';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::CategoryApproved, self::RevisionRequested, self::Rejected],
            self::CategoryApproved => [self::InReview, self::RevisionRequested],
            self::InReview => [self::Approved, self::RevisionRequested, self::Rejected],
            self::RevisionRequested => [self::Resubmitted, self::Draft],
            self::Resubmitted => [self::InReview, self::RevisionRequested, self::Rejected],
            self::Approved => [self::Scheduled, self::Published, self::RevisionRequested],
            self::Scheduled => [self::Published, self::Approved],
            self::Published => [self::Archived], // corrections are a separate revision; the live article stays published
            self::Rejected => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedNext(), true);
    }

    /** Transitions that require a written reason (stored in status history). */
    public static function requiresReason(self $to): bool
    {
        return in_array($to, [self::RevisionRequested, self::Rejected], true);
    }

    /** Only this status may ever be shown to guests (and only when published_at <= now). */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }
}
