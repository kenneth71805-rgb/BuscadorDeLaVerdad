import DeadlineList, { type Deadline } from '@/components/deadline-list';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

type Props = {
    deadlines: Deadline[];
    stats: { published: number; pending: number; scheduled: number; staff: number };
    recentActivity: { id: number; action: string; actor: string; at: string | null }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin dashboard', href: '/admin' }];

export default function Dashboard({ deadlines,  stats, recentActivity }: Props) {
    const items = [
        ['Published articles', stats.published],
        ['Pending submissions', stats.pending],
        ['Scheduled', stats.scheduled],
        ['Active staff accounts', stats.staff],
    ] as const;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin dashboard" />
            <div className="flex flex-col gap-6 p-4">
                <dl className="grid grid-cols-2 gap-x-6 gap-y-3 border-b pb-4 md:grid-cols-4">
                    {items.map(([label, value]) => (
                        <div key={label}>
                            <dt className="text-muted-foreground text-xs uppercase">{label}</dt>
                            <dd className="text-2xl font-semibold">{value}</dd>
                        </div>
                    ))}
                </dl>

                <DeadlineList deadlines={deadlines} />

                <section>
                    <h2 className="mb-2 text-lg font-semibold">Recent activity</h2>
                    {recentActivity.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No activity recorded yet.</p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {recentActivity.map((log) => (
                                <li key={log.id} className="flex flex-wrap justify-between gap-2 py-2">
                                    <span>
                                        <span className="font-medium">{log.actor}</span> · {log.action}
                                    </span>
                                    <span className="text-muted-foreground">{formatWhen(log.at)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
