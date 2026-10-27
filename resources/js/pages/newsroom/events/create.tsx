import EventForm from '@/components/event-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create({ categories }: { categories: { id: number; name: string }[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Events', href: '/newsroom/events' }, { title: 'New', href: '/newsroom/events/create' }]}>
            <Head title="New event" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New event</h1>
                <EventForm categories={categories} />
            </div>
        </AppLayout>
    );
}
