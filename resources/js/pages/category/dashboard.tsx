import AppLayout from '@/layouts/app-layout';
import { formatWhen, statusLabels } from '@/lib/status';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

type Props = {
    categories: { id: number; name: string }[];
    byStatus: Record<string, number>;
    needsAttention: { id: number; title: string; category: string; status: string; updated_at: string | null }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My category', href: '/category' }];

export default function Dashboard({ categories, byStatus, needsAttention }: Props) {
    const focus = ['draft', 'submitted', 'revision_requested', 'approved', 'published'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My category" />
            <div className="flex flex-col gap-6 p-4">
                {categories.length === 0 ? (
                    <p className="text-sm">You have not been assigned to a category yet. Please ask an Admin.</p>
                ) : (
                    <p className="text-muted-foreground text-sm">Your categories: {categories.map((c) => c.name).join(', ')}</p>
                )}

                <dl className="grid grid-cols-2 gap-x-6 gap-y-3 border-b pb-4 md:grid-cols-5">
                    {focus.map((s) => (
                        <div key={s}>
                            <dt className="text-muted-foreground text-xs uppercase">{statusLabels[s]}</dt>
                            <dd className="text-2xl font-semibold">{byStatus[s] ?? 0}</dd>
                        </div>
                    ))}
                </dl>

                <section>
                    <h2 className="mb-2 text-lg font-semibold">Drafts and revision requests</h2>
                    {needsAttention.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nothing needs your attention.</p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {needsAttention.map((a) => (
                                <li key={a.id} className="flex flex-wrap justify-between gap-2 py-2">
                                    <span>
                                        <span className="font-medium">{a.title}</span>
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {a.category} · {statusLabels[a.status]}
                                        </span>
                                    </span>
                                    <span className="text-muted-foreground">{formatWhen(a.updated_at)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
