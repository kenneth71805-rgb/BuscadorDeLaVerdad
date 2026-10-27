import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { statusLabels } from '@/lib/status';
import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Props = {
    period: { from: string; to: string };
    category: number | null;
    categories: { id: number; name: string }[];
    published_total: number;
    published_by_day: { date: string; count: number }[];
    by_category: { category: string; total: number; published: number; pending: number; rejected: number }[];
    turnaround: { hours: number | null; sample: number };
    stuck: { id: number; title: string; category: string; author: string; status: string; days: number; overdue: boolean }[];
    readership: { recorded: boolean; total: number; popular: { title: string; category: string; views: number }[] };
    overdue_days: number;
};

export default function Reports(p: Props) {
    const [f, setF] = useState({ from: p.period.from, to: p.period.to, category: p.category ? String(p.category) : '' });
    const apply: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/reports', f, { preserveState: true });
    };
    const max = Math.max(1, ...p.published_by_day.map((d) => d.count));
    const days = p.turnaround.hours === null ? null : p.turnaround.hours / 24;

    return (
        <AppLayout breadcrumbs={[{ title: 'Reports', href: '/reports' }]}>
            <Head title="Reports" />
            <div className="flex flex-col gap-8 p-4">
                <h1 className="text-2xl font-semibold">Reports</h1>

                <form onSubmit={apply} className="flex flex-wrap items-end gap-2 text-sm">
                    <label className="grid gap-1">
                        From
                        <input type="date" value={f.from} onChange={(e) => setF({ ...f, from: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2" />
                    </label>
                    <label className="grid gap-1">
                        To
                        <input type="date" value={f.to} onChange={(e) => setF({ ...f, to: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2" />
                    </label>
                    <label className="grid gap-1">
                        Category
                        <select value={f.category} onChange={(e) => setF({ ...f, category: e.target.value })} className="border-input bg-background h-9 rounded-md border px-2">
                            <option value="">{p.categories.length > 1 ? 'All' : 'Mine'}</option>
                            {p.categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <Button type="submit" variant="outline">
                        Update
                    </Button>
                </form>

                <dl className="grid grid-cols-2 gap-x-6 gap-y-3 border-b pb-4 md:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground text-xs uppercase">Stories published</dt>
                        <dd className="text-2xl font-semibold">{p.published_total}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-xs uppercase">Average time to publish</dt>
                        <dd className="text-2xl font-semibold">{days === null ? 'No data' : days >= 1 ? `${days.toFixed(1)} days` : `${p.turnaround.hours} hours`}</dd>
                        {days !== null && <dd className="text-muted-foreground text-xs">from first submission, {p.turnaround.sample} stories</dd>}
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-xs uppercase">Waiting too long</dt>
                        <dd className="text-2xl font-semibold">{p.stuck.filter((s) => s.overdue).length}</dd>
                        <dd className="text-muted-foreground text-xs">over {p.overdue_days} days</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-xs uppercase">Page views</dt>
                        <dd className="text-2xl font-semibold">{p.readership.recorded ? p.readership.total : 'Not recorded yet'}</dd>
                    </div>
                </dl>

                <section aria-labelledby="pub-h">
                    <h2 id="pub-h" className="mb-2 text-lg font-semibold">
                        Publishing activity by day
                    </h2>
                    {p.published_by_day.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nothing was published in this period.</p>
                    ) : (
                        <ul className="grid gap-1 text-sm">
                            {p.published_by_day.map((d) => (
                                <li key={d.date} className="flex items-center gap-3">
                                    <span className="w-28">{d.date}</span>
                                    <span className="bg-primary h-3" style={{ width: `${(d.count / max) * 60}%`, minWidth: '0.5rem' }} aria-hidden="true" />
                                    <span>{d.count}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section aria-labelledby="cat-h">
                    <h2 id="cat-h" className="mb-2 text-lg font-semibold">
                        Submissions by category (stories started in this period)
                    </h2>
                    <table className="w-full text-left text-sm">
                        <thead className="border-b text-xs uppercase">
                            <tr>
                                <th className="py-2 pr-4">Category</th>
                                <th className="py-2 pr-4">Started</th>
                                <th className="py-2 pr-4">Published</th>
                                <th className="py-2 pr-4">Pending</th>
                                <th className="py-2">Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                            {p.by_category.map((c) => (
                                <tr key={c.category} className="border-b">
                                    <td className="py-2 pr-4 font-medium">{c.category}</td>
                                    <td className="py-2 pr-4">{c.total}</td>
                                    <td className="py-2 pr-4">{c.published}</td>
                                    <td className="py-2 pr-4">{c.pending}</td>
                                    <td className="py-2">{c.rejected}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>

                <section aria-labelledby="stuck-h">
                    <h2 id="stuck-h" className="mb-2 text-lg font-semibold">
                        Waiting for a decision (oldest first)
                    </h2>
                    {p.stuck.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nothing is waiting.</p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {p.stuck.map((s) => (
                                <li key={s.id} className="flex flex-wrap justify-between gap-2 py-2">
                                    <span>
                                        <Link href={`/articles/${s.id}`} className="font-medium underline">
                                            {s.title}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {s.category} · {s.author} · {statusLabels[s.status]}
                                        </span>
                                    </span>
                                    <span className={s.overdue ? 'font-semibold text-red-700' : 'text-muted-foreground'}>
                                        {s.days} day{s.days === 1 ? '' : 's'}
                                        {s.overdue ? ' · overdue' : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section aria-labelledby="read-h">
                    <h2 id="read-h" className="mb-2 text-lg font-semibold">
                        Most read stories
                    </h2>
                    {!p.readership.recorded ? (
                        <p className="text-muted-foreground max-w-2xl text-sm">No page views have been recorded yet. Counting starts automatically when visitors read published stories.</p>
                    ) : (
                        <>
                            <ol className="list-decimal pl-5 text-sm">
                                {p.readership.popular.map((r, i) => (
                                    <li key={i} className="py-1">
                                        <span className="font-medium">{r.title}</span> <span className="text-muted-foreground">· {r.category} · {r.views} views</span>
                                    </li>
                                ))}
                            </ol>
                            <p className="text-muted-foreground mt-2 max-w-2xl text-xs">Page views, not unique visitors. Staff visits and known crawlers are not counted. Nothing about visitors is stored: no cookies, addresses or accounts.</p>
                        </>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
