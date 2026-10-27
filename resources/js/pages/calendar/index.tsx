import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

type Item = { time: string; type: 'event' | 'deadline' | 'publication'; title: string; detail: string; url: string | null };
type Props = { month: string; label: string; prev: string; next: string; days: { date: string; label: string; items: Item[] }[] };

const kinds: Record<Item['type'], string> = { event: 'Event', deadline: 'Deadline', publication: 'Publication' };

export default function Calendar({ label, prev, next, days }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Editorial calendar', href: '/calendar' }]}>
            <Head title="Editorial calendar" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Editorial calendar: {label}</h1>
                    <nav aria-label="Month" className="flex gap-3 text-sm">
                        <Link href={`/calendar?month=${prev}`} className="underline">
                            ← Previous
                        </Link>
                        <Link href="/calendar" className="underline">
                            This month
                        </Link>
                        <Link href={`/calendar?month=${next}`} className="underline">
                            Next →
                        </Link>
                    </nav>
                </div>
                <p className="text-muted-foreground text-xs">Shows events, assignment deadlines and scheduled publications that you are allowed to see.</p>

                {days.length === 0 ? (
                    <p className="text-muted-foreground py-8 text-center text-sm">Nothing scheduled this month.</p>
                ) : (
                    <ol className="divide-y">
                        {days.map((d) => (
                            <li key={d.date} className="grid gap-2 py-3 md:grid-cols-[12rem_1fr]">
                                <h2 className="font-semibold">{d.label}</h2>
                                <ul className="grid gap-2 text-sm">
                                    {d.items.map((i, idx) => (
                                        <li key={idx} className="flex flex-wrap gap-x-3">
                                            <span className="text-muted-foreground w-20">{i.time}</span>
                                            <span className="w-24 text-xs font-medium uppercase">{kinds[i.type]}</span>
                                            <span className="flex-1">
                                                {i.url ? (
                                                    <Link href={i.url} className="font-medium underline">
                                                        {i.title}
                                                    </Link>
                                                ) : (
                                                    <span className="font-medium">{i.title}</span>
                                                )}
                                                {i.detail && <span className="text-muted-foreground"> · {i.detail}</span>}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ol>
                )}
            </div>
        </AppLayout>
    );
}
