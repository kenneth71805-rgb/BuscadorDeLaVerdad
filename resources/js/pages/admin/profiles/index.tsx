import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

type Row = { id: number; name: string; position: string; category: string | null; is_public: boolean; archived: boolean; sort_order: number };

export default function Index({ profiles }: { profiles: Row[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Editorial board', href: '/admin/profiles' }]}>
            <Head title="Editorial board" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Editorial board and Category Heads</h1>
                    <Button asChild>
                        <Link href="/admin/profiles/create">New profile</Link>
                    </Button>
                </div>
                <Flash />
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Order</th>
                                <th className="py-2 pr-4">Name</th>
                                <th className="py-2 pr-4">Position</th>
                                <th className="py-2 pr-4">Category</th>
                                <th className="py-2 pr-4">Public page</th>
                                <th className="py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {profiles.map((p) => (
                                <tr key={p.id} className="border-b">
                                    <td className="py-2 pr-4">{p.sort_order}</td>
                                    <td className="py-2 pr-4 font-medium">{p.name}</td>
                                    <td className="py-2 pr-4">{p.position}</td>
                                    <td className="py-2 pr-4">{p.category ?? '—'}</td>
                                    <td className="py-2 pr-4">{!p.is_public ? 'Hidden' : p.archived ? 'Former member' : 'Shown'}</td>
                                    <td className="py-2 text-right">
                                        <Link href={`/admin/profiles/${p.id}/edit`} className="underline">
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {profiles.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground py-6 text-center">
                                        No profiles yet.
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
