import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatWhen, statusLabels } from '@/lib/status';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Props = {
    articles: {
        data: { id: number; title: string; category: string; author: string; status: string; updated_at: string | null }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status: string | null; q: string };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Articles', href: '/articles' }];
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({ articles, filters }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status ?? '');

    const apply: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/articles', { q, status }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Articles" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Articles</h1>
                    <Button asChild>
                        <Link href="/articles/create">New article</Link>
                    </Button>
                </div>

                {flash?.success && <p className="rounded-md border border-green-600/30 bg-green-50 px-3 py-2 text-sm text-green-800">{flash.success}</p>}

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search headlines" aria-label="Search headlines" className="max-w-xs" />
                    <select value={status} onChange={(e) => setStatus(e.target.value)} aria-label="Filter by status" className="border-input bg-background h-9 rounded-md border px-2 text-sm">
                        <option value="">All statuses</option>
                        {Object.entries(statusLabels).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Headline</th>
                                <th className="py-2 pr-4">Category</th>
                                <th className="py-2 pr-4">Author</th>
                                <th className="py-2 pr-4">Status</th>
                                <th className="py-2">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            {articles.data.map((a) => (
                                <tr key={a.id} className="border-b">
                                    <td className="py-2 pr-4 font-medium">
                                        <Link href={`/articles/${a.id}`} className="underline">
                                            {a.title}
                                        </Link>
                                    </td>
                                    <td className="py-2 pr-4">{a.category}</td>
                                    <td className="py-2 pr-4">{a.author}</td>
                                    <td className="py-2 pr-4">{statusLabels[a.status]}</td>
                                    <td className="text-muted-foreground py-2">{formatWhen(a.updated_at)}</td>
                                </tr>
                            ))}
                            {articles.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-6 text-center">
                                        No articles yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Pagination" className="flex flex-wrap gap-2 text-sm">
                    {articles.links
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
