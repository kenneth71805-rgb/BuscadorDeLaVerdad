import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link } from '@inertiajs/react';

type Row = { id: number; title: string; starts_at: string; location: string | null; is_public: boolean };

export default function Index({ events }: { events: Row[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Events', href: '/newsroom/events' }]}>
            <Head title="Events" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Events and coverage dates</h1>
                    <Button asChild>
                        <Link href="/newsroom/events/create">New event</Link>
                    </Button>
                </div>
                <Flash />
                <ul className="divide-y text-sm">
                    {events.map((e) => (
                        <li key={e.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
                            <span>
                                <span className="font-medium">{e.title}</span>{' '}
                                <span className="text-muted-foreground">
                                    · {formatWhen(e.starts_at)}
                                    {e.location ? ` · ${e.location}` : ''} · {e.is_public ? 'Public' : 'Staff only'}
                                </span>
                            </span>
                            <Link href={`/newsroom/events/${e.id}/edit`} className="underline">
                                Edit
                            </Link>
                        </li>
                    ))}
                    {events.length === 0 && <li className="text-muted-foreground py-6 text-center">No events yet.</li>}
                </ul>
            </div>
        </AppLayout>
    );
}
