import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Log = { id: number; action: string; actor: string; subject: string | null; meta: Record<string, unknown> | null; at: string | null };
type Props = {
    logs: { data: Log[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { action: string; actor: number | null; from: string | null; to: string | null };
    staff: { id: number; name: string }[];
};
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Audit({ logs, filters, staff }: Props) {
    const [f, setF] = useState({ action: filters.action ?? '', actor: filters.actor ? String(filters.actor) : '', from: filters.from ?? '', to: filters.to ?? '' });

    const apply: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/admin/audit', f, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Audit log', href: '/admin/audit' }]}>
            <Head title="Audit log" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Audit log</h1>
                <p className="text-muted-foreground max-w-2xl text-sm">A permanent record of important actions: who did what, and when. Entries cannot be edited or deleted from the website. Visible to Admins only.</p>

                <form onSubmit={apply} className="flex flex-wrap items-end gap-2 text-sm">
                    <label className="grid gap-1">
                        Action starts with
                        <Input value={f.action} onChange={(e) => setF({ ...f, action: e.target.value })} placeholder="e.g. article." className="w-44" />
                    </label>
                    <label className="grid gap-1">
                        Person
                        <select value={f.actor} onChange={(e) => setF({ ...f, actor: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2">
                            <option value="">Anyone</option>
                            {staff.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-1">
                        From
                        <input type="date" value={f.from} onChange={(e) => setF({ ...f, from: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2" />
                    </label>
                    <label className="grid gap-1">
                        To
                        <input type="date" value={f.to} onChange={(e) => setF({ ...f, to: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2" />
                    </label>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">When</th>
                                <th className="py-2 pr-4">Who</th>
                                <th className="py-2 pr-4">Action</th>
                                <th className="py-2 pr-4">Item</th>
                                <th className="py-2">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.map((l) => (
                                <tr key={l.id} className="border-b align-top">
                                    <td className="py-2 pr-4 whitespace-nowrap">{formatWhen(l.at)}</td>
                                    <td className="py-2 pr-4">{l.actor}</td>
                                    <td className="py-2 pr-4 font-mono text-xs">{l.action}</td>
                                    <td className="py-2 pr-4">{l.subject ?? '—'}</td>
                                    <td className="py-2 font-mono text-xs break-all">{l.meta ? JSON.stringify(l.meta) : ''}</td>
                                </tr>
                            ))}
                            {logs.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-6 text-center">
                                        No entries match.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <nav aria-label="Pagination" className="flex flex-wrap gap-2 text-sm">
                    {logs.links.filter((l) => l.url).map((l) => (
                        <Link key={l.label} href={l.url!} className={`rounded border px-3 py-1 ${l.active ? 'bg-primary text-primary-foreground' : ''}`}>
                            {pageLabel(l.label)}
                        </Link>
                    ))}
                </nav>
            </div>
        </AppLayout>
    );
}
