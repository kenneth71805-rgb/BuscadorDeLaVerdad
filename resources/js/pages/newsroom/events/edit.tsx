import EventForm, { EventData } from '@/components/event-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ event, categories }: { event: EventData; categories: { id: number; name: string }[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Events', href: '/newsroom/events' }, { title: event.title, href: `/newsroom/events/${event.id}/edit` }]}>
            <Head title={`Edit ${event.title}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit event</h1>
                <EventForm event={event} categories={categories} />
            </div>
        </AppLayout>
    );
}
