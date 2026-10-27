import FaqForm, { FaqData } from '@/components/faq-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ entry }: { entry: FaqData }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Helper FAQ', href: '/newsroom/faq' }, { title: 'Edit', href: `/newsroom/faq/${entry.id}/edit` }]}>
            <Head title="Edit FAQ entry" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit FAQ entry</h1>
                <FaqForm entry={entry} />
            </div>
        </AppLayout>
    );
}
