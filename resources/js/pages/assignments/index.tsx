import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link, router } from '@inertiajs/react';

type Row = { id: number; title: string; category: string; assignee: string; due_at: string; status: string; overdue: boolean; can_edit: boolean; mine: boolean };
type Props = {
    items: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { status: string | null; mine: boolean };
    statuses: Record<string, string>;
};
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({ items, filters, statuses }: Props) {
    const apply = (next: Partial<{ status: string; mine: string }>) => router.get('/assignments', { status: filters.status ?? '', mine: filters.mine ? '1' : '', ...next }, { preserveState: true });

    return (
        <AppLayout breadcrumbs={[{ title: 'Assignments', href: '/assignments' }]}>
            <Head title="Assignments" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Assignments and deadlines</h1>
                    <Button asChild>
                        <Link href="/assignments/create">New assignment</Link>
                    </Button>
                </div>
                <Flash />
                <div className="flex flex-wrap items-center gap-3 text-sm">
                    <label className="flex items-center gap-2">
                        <input type="checkbox" checked={filters.mine} onChange={(e) => apply({ mine: e.target.checked ? '1' : '' })} />
                        Only mine
                    </label>
                    <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => apply({ status: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2">
                        <option value="">All statuses</option>
                        {Object.entries(statuses).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Assignment</th>
                                <th className="py-2 pr-4">Category</th>
                                <th className="py-2 pr-4">Assigned to</th>
                                <th className="py-2 pr-4">Deadline</th>
                                <th className="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.map((a) => (
                                <tr key={a.id} className="border-b">
                                    <td className="py-2 pr-4 font-medium">
                                        <Link href={`/assignments/${a.id}/edit`} className="underline">
                                            {a.title}
                                        </Link>
                                    </td>
                                    <td className="py-2 pr-4">{a.category}</td>
                                    <td className="py-2 pr-4">
                                        {a.assignee}
                                        {a.mine ? ' (you)' : ''}
                                    </td>
                                    <td className={`py-2 pr-4 ${a.overdue ? 'font-semibold text-red-700' : ''}`}>
                                        {formatWhen(a.due_at)}
                                        {a.overdue ? ' · overdue' : ''}
                                    </td>
                                    <td className="py-2">{statuses[a.status]}</td>
                                </tr>
                            ))}
                            {items.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-6 text-center">
                                        No assignments.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
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
