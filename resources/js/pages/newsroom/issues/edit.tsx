import IssueForm, { IssueData } from '@/components/issue-form';
import { PickerMedia } from '@/components/media-picker';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ issue, media }: { issue: IssueData; media: PickerMedia[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Issues', href: '/newsroom/issues' }, { title: issue.title, href: `/newsroom/issues/${issue.id}/edit` }]}>
            <Head title={`Edit ${issue.title}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit issue</h1>
                <IssueForm issue={issue} media={media} />
            </div>
        </AppLayout>
    );
}
