import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Row = {
    id: number;
    name: string;
    email: string;
    role_label: string;
    position: string | null;
    is_active: boolean;
    categories: string[];
};

type Props = {
    staff: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { q: string };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Staff accounts', href: '/admin/staff' }];

export default function Index({ staff, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [q, setQ] = useState(filters.q);

    const search: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/admin/staff', { q }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff accounts" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Staff accounts</h1>
                    <Button asChild>
                        <Link href="/admin/staff/create">Add staff account</Link>
                    </Button>
                </div>

                {flash?.success && <p className="rounded-md border border-green-600/30 bg-green-50 px-3 py-2 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{flash.success}</p>}

                <form onSubmit={search} className="flex max-w-md gap-2">
                    <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search name or email" aria-label="Search staff" />
                    <Button type="submit" variant="outline">
                        Search
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Name</th>
                                <th className="py-2 pr-4">Role</th>
                                <th className="py-2 pr-4">Categories</th>
                                <th className="py-2 pr-4">Status</th>
                                <th className="py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {staff.data.map((m) => (
                                <tr key={m.id} className="border-b">
                                    <td className="py-2 pr-4">
                                        <div className="font-medium">{m.name}</div>
                                        <div className="text-muted-foreground text-xs">{m.email}</div>
                                    </td>
                                    <td className="py-2 pr-4">
                                        {m.role_label}
                                        {m.position && <div className="text-muted-foreground text-xs">{m.position}</div>}
                                    </td>
                                    <td className="py-2 pr-4">{m.categories.join(', ') || '—'}</td>
                                    <td className="py-2 pr-4">{m.is_active ? 'Active' : 'Deactivated'}</td>
                                    <td className="py-2 text-right">
                                        <Link href={`/admin/staff/${m.id}/edit`} className="underline">
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {staff.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-6 text-center">
                                        No staff accounts found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
