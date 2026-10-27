import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link, router } from '@inertiajs/react';

type Item = { id: string; message: string; url: string | null; kind: string; read: boolean; at: string | null };
type Props = { items: { data: Item[]; links: { url: string | null; label: string; active: boolean }[] } };
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({ items }: Props) {
    const open = (n: Item) => {
        router.put(route('notifications.read', n.id), {}, { preserveScroll: true, onSuccess: () => n.url && router.visit(n.url) });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Notifications', href: '/notifications' }]}>
            <Head title="Notifications" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Notifications</h1>
                    <Button variant="outline" onClick={() => router.put(route('notifications.read-all'), {}, { preserveScroll: true })}>
                        Mark all as read
                    </Button>
                </div>
                <Flash />
                <ul className="divide-y text-sm">
                    {items.data.map((n) => (
                        <li key={n.id} className="flex flex-wrap items-start justify-between gap-2 py-3">
                            <button type="button" onClick={() => open(n)} className={`max-w-2xl text-left ${n.read ? 'text-muted-foreground' : 'font-medium'}`}>
                                {!n.read && <span className="mr-2 inline-block size-2 rounded-full bg-blue-600" aria-label="Unread" />}
                                {n.message}
                            </button>
                            <span className="text-muted-foreground text-xs">{formatWhen(n.at)}</span>
                        </li>
                    ))}
                    {items.data.length === 0 && <li className="text-muted-foreground py-6 text-center">You have no notifications.</li>}
                </ul>
                <nav aria-label="Pagination" className="flex flex-wrap gap-2 text-sm">
                    {items.links.filter((l) => l.url).map((l) => (
                        <Link key={l.label} href={l.url!} className={`rounded border px-3 py-1 ${l.active ? 'bg-primary text-primary-foreground' : ''}`}>
                            {pageLabel(l.label)}
                        </Link>
                    ))}
                </nav>
            </div>
        </AppLayout>
    );
}
