import { formatWhen } from '@/lib/status';
import { Link } from '@inertiajs/react';

export type Deadline = { id: number; title: string; category: string; assignee: string; due_at: string; overdue: boolean; url: string };

export default function DeadlineList({ deadlines }: { deadlines: Deadline[] }) {
    return (
        <section aria-labelledby="deadlines-h">
            <div className="mb-2 flex items-baseline justify-between">
                <h2 id="deadlines-h" className="text-lg font-semibold">
                    Upcoming deadlines
                </h2>
                <Link href="/assignments" className="text-sm underline">
                    All assignments
                </Link>
            </div>
            {deadlines.length === 0 ? (
                <p className="text-muted-foreground text-sm">No open assignments.</p>
            ) : (
                <ul className="divide-y text-sm">
                    {deadlines.map((d) => (
                        <li key={d.id} className="flex flex-wrap justify-between gap-2 py-2">
                            <span>
                                <Link href={d.url} className="font-medium underline">
                                    {d.title}
                                </Link>
                                <span className="text-muted-foreground">
                                    {' '}
                                    · {d.category} · {d.assignee}
                                </span>
                            </span>
                            <span className={d.overdue ? 'font-semibold text-red-700' : 'text-muted-foreground'}>
                                {formatWhen(d.due_at)}
                                {d.overdue ? ' · overdue' : ''}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
