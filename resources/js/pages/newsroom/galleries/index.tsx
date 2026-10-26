import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

type Row = { id: number; title: string; slug: string; status: string; items: number; public_items: number };

export default function Index({ galleries }: { galleries: Row[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Galleries', href: '/newsroom/galleries' }]}>
            <Head title="Galleries" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Photo galleries</h1>
                    <Button asChild>
                        <Link href="/newsroom/galleries/create">New gallery</Link>
                    </Button>
                </div>
                <Flash />
                <ul className="divide-y text-sm">
                    {galleries.map((g) => (
                        <li key={g.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
                            <span>
                                <span className="font-medium">{g.title}</span> <span className="text-muted-foreground">· {g.status} · {g.public_items} of {g.items} photos approved for the public</span>
                            </span>
                            <Link href={`/newsroom/galleries/${g.id}/edit`} className="underline">
                                Edit
                            </Link>
                        </li>
                    ))}
                    {galleries.length === 0 && <li className="text-muted-foreground py-6 text-center">No galleries yet.</li>}
                </ul>
            </div>
        </AppLayout>
    );
}
