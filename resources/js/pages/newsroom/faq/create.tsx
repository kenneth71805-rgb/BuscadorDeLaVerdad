import FaqForm from '@/components/faq-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Helper FAQ', href: '/newsroom/faq' }, { title: 'New', href: '/newsroom/faq/create' }]}>
            <Head title="New FAQ entry" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New FAQ entry</h1>
                <FaqForm />
            </div>
        </AppLayout>
    );
}
