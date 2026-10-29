import DeadlineList, { type Deadline } from '@/components/deadline-list';
import AppLayout from '@/layouts/app-layout';
import { formatWhen, statusLabels } from '@/lib/status';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

type Props = {
    corrections: { id: number; title: string; at: string | null }[];
    deadlines: Deadline[];
    byStatus: Record<string, number>;
    queue: { id: number; title: string; category: string; author: string; status: string; updated_at: string | null }[];
    byCategory: { id: number; name: string; pending: number }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Editorial desk', href: '/editor' }];

export default function Dashboard({ corrections, deadlines,  byStatus, queue, byCategory }: Props) {
    const focus = ['in_review', 'category_approved', 'revision_requested', 'approved', 'scheduled'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editorial desk" />
            <div className="flex flex-col gap-6 p-4">
                <dl className="grid grid-cols-2 gap-x-6 gap-y-3 border-b pb-4 md:grid-cols-5">
                    {focus.map((s) => (
                        <div key={s}>
                            <dt className="text-muted-foreground text-xs uppercase">{statusLabels[s]}</dt>
                            <dd className="text-2xl font-semibold">{byStatus[s] ?? 0}</dd>
                        </div>
                    ))}
                </dl>

                <DeadlineList deadlines={deadlines} />

                <section>
                    <h2 className="mb-2 text-lg font-semibold">Review queue (oldest first)</h2>
                    {queue.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nothing is waiting for your decision.</p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {queue.map((a) => (
                                <li key={a.id} className="flex flex-wrap justify-between gap-2 py-2">
                                    <span>
                                        <span className="font-medium">{a.title}</span>
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {a.category} · {a.author} · {statusLabels[a.status]}
                                        </span>
                                    </span>
                                    <span className="text-muted-foreground">{formatWhen(a.updated_at)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {corrections.length > 0 && (
                    <section aria-labelledby="corr-h">
                        <h2 id="corr-h" className="mb-2 text-lg font-semibold">
                            Corrections waiting for your decision
                        </h2>
                        <ul className="divide-y text-sm">
                            {corrections.map((c) => (
                                <li key={c.id} className="flex flex-wrap justify-between gap-2 py-2">
                                    <a href={`/corrections/${c.id}`} className="font-medium underline">
                                        {c.title}
                                    </a>
                                    <span className="text-muted-foreground">{formatWhen(c.at)}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section>
                    <h2 className="mb-2 text-lg font-semibold">Pending by category</h2>
                    <ul className="divide-y text-sm">
                        {byCategory.map((c) => (
                            <li key={c.id} className="flex justify-between py-2">
                                <span>{c.name}</span>
                                <span className="font-medium">{c.pending}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
