import Flash from '@/components/flash';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link } from '@inertiajs/react';

type Props = {
    items: { data: { id: number; name: string; type: string; status: string; created_at: string | null }[]; links: { url: string | null; label: string; active: boolean }[] };
    status: string | null;
};

const tabs: [string, string][] = [['', 'All'], ['new', 'New'], ['read', 'Read'], ['resolved', 'Resolved']];
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({ items, status }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Inbox', href: '/inbox' }]}>
            <Head title="Inbox" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Contact inbox</h1>
                <Flash />
                <nav aria-label="Filter" className="flex gap-3 text-sm">
                    {tabs.map(([value, label]) => (
                        <Link key={label} href={value ? `/inbox?status=${value}` : '/inbox'} className={(status ?? '') === value ? 'font-semibold underline' : ''}>
                            {label}
                        </Link>
                    ))}
                </nav>
                <ul className="divide-y text-sm">
                    {items.data.map((i) => (
                        <li key={i.id} className="flex flex-wrap justify-between gap-2 py-3">
                            <span>
                                <Link href={`/inbox/${i.id}`} className="font-medium underline">
                                    {i.name}
                                </Link>{' '}
                                <span className="text-muted-foreground">· {i.type} · {i.status}</span>
                            </span>
                            <span className="text-muted-foreground">{formatWhen(i.created_at)}</span>
                        </li>
                    ))}
                    {items.data.length === 0 && <li className="text-muted-foreground py-6 text-center">No messages.</li>}
                </ul>
                <nav aria-label="Pagination" className="flex flex-wrap gap-2 text-sm">
                    {items.links
                        .filter((l) => l.url)
                        .map((l) => (
                            <Link key={l.label} href={l.url!} className={`rounded border px-3 py-1 ${l.active ? 'bg-primary text-primary-foreground' : ''}`}>
                                {pageLabel(l.label)}
                            </Link>
                        ))}
                </nav>
            </div>
        </AppLayout>
    );
}
