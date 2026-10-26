import IssueForm from '@/components/issue-form';
import { PickerMedia } from '@/components/media-picker';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create({ media }: { media: PickerMedia[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Issues', href: '/newsroom/issues' }, { title: 'New', href: '/newsroom/issues/create' }]}>
            <Head title="New issue" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New publication issue</h1>
                <IssueForm media={media} />
            </div>
        </AppLayout>
    );
}
