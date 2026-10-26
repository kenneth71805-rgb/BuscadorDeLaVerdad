import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

type Row = { id: number; name: string; slug: string; sort_order: number; is_active: boolean; show_in_nav: boolean; articles_count: number };

export default function Index({ categories }: { categories: Row[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Categories', href: '/admin/categories' }]}>
            <Head title="Categories" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Categories</h1>
                    <Button asChild>
                        <Link href="/admin/categories/create">New category</Link>
                    </Button>
                </div>
                <Flash />
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Order</th>
                                <th className="py-2 pr-4">Name</th>
                                <th className="py-2 pr-4">Address</th>
                                <th className="py-2 pr-4">Articles</th>
                                <th className="py-2 pr-4">Status</th>
                                <th className="py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {categories.map((c) => (
                                <tr key={c.id} className="border-b">
                                    <td className="py-2 pr-4">{c.sort_order}</td>
                                    <td className="py-2 pr-4 font-medium">{c.name}</td>
                                    <td className="py-2 pr-4">/{c.slug}</td>
                                    <td className="py-2 pr-4">{c.articles_count}</td>
                                    <td className="py-2 pr-4">{c.is_active ? (c.show_in_nav ? 'Active' : 'Active (not in menu)') : 'Inactive'}</td>
                                    <td className="py-2 text-right">
                                        <Link href={`/admin/categories/${c.id}/edit`} className="underline">
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
