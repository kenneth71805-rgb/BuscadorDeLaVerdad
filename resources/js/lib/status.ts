export const statusLabels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    category_approved: 'Category approved',
    in_review: 'In review',
    revision_requested: 'Revision requested',
    resubmitted: 'Resubmitted',
    approved: 'Approved',
    scheduled: 'Scheduled',
    published: 'Published',
    rejected: 'Rejected',
    archived: 'Archived',
};

export function formatWhen(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '';
}
