import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, router } from '@inertiajs/react';

type Item = { id: number; name: string; email: string; type: string; message: string; status: string; created_at: string | null; closed_at: string | null };

export default function Show({ item }: { item: Item }) {
    const setStatus = (status: string) => router.put(route('inbox.update', item.id), { status }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ title: 'Inbox', href: '/inbox' }, { title: item.name, href: `/inbox/${item.id}` }]}>
            <Head title={`Message from ${item.name}`} />
            <div className="flex max-w-2xl flex-col gap-4 p-4">
                <Flash />
                <h1 className="text-2xl font-semibold">{item.type}</h1>
                <p className="text-muted-foreground text-sm">
                    From {item.name} ({item.email}) · {formatWhen(item.created_at)} · status: {item.status}
                </p>
                <p className="whitespace-pre-wrap rounded-md border p-4">{item.message}</p>
                {item.closed_at && <p className="text-muted-foreground text-xs">Resolved messages are deleted automatically one week after they are resolved.</p>}
                <div className="flex flex-wrap gap-2">
                    {item.status !== 'read' && item.status !== 'resolved' && (
                        <Button variant="outline" onClick={() => setStatus('read')}>
                            Mark as read
                        </Button>
                    )}
                    {item.status !== 'resolved' ? <Button onClick={() => setStatus('resolved')}>Mark as resolved</Button> : <Button variant="outline" onClick={() => setStatus('read')}>Reopen</Button>}
                </div>
            </div>
        </AppLayout>
    );
}
