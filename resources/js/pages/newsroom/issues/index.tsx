import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

type Row = { id: number; title: string; issue_date: string; is_public: boolean; has_pdf: boolean };

export default function Index({ issues }: { issues: Row[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Issues', href: '/newsroom/issues' }]}>
            <Head title="Publication issues" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Publication issues</h1>
                    <Button asChild>
                        <Link href="/newsroom/issues/create">New issue</Link>
                    </Button>
                </div>
                <Flash />
                <ul className="divide-y text-sm">
                    {issues.map((i) => (
                        <li key={i.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
                            <span>
                                <span className="font-medium">{i.title}</span> <span className="text-muted-foreground">· {i.issue_date} · {i.is_public ? 'Public' : 'Private'}{i.has_pdf ? '' : ' · no PDF'}</span>
                            </span>
                            <Link href={`/newsroom/issues/${i.id}/edit`} className="underline">
                                Edit
                            </Link>
                        </li>
                    ))}
                    {issues.length === 0 && <li className="text-muted-foreground py-6 text-center">No issues yet.</li>}
                </ul>
            </div>
        </AppLayout>
    );
}
