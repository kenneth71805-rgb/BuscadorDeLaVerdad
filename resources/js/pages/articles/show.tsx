import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen, statusLabels } from '@/lib/status';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Action = { to: string; label: string; needs_reason: boolean; needs_schedule: boolean; danger: boolean };

type Props = {
    article: {
        id: number;
        title: string;
        subtitle: string | null;
        paragraphs: string[];
        category: string;
        author: string;
        status: string;
        scheduled_for: string | null;
        published_at: string | null;
        public_url: string | null;
    };
    lead: { id: number; url: string; alt: string; caption: string | null; credit: string | null; is_public: boolean; can_release: boolean } | null;
    can_edit: boolean;
    actions: Action[];
    history: { id: number; from: string | null; to: string; actor: string; reason: string | null; at: string | null }[];
};

export default function Show({ article, lead, can_edit, actions, history }: Props) {
    const { flash, errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
    const [reason, setReason] = useState('');
    const [scheduledFor, setScheduledFor] = useState('');
    const [busy, setBusy] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Articles', href: '/articles' },
        { title: article.title, href: `/articles/${article.id}` },
    ];

    const act = (to: string) => {
        setBusy(true);
        router.post(
            route('articles.transition', article.id),
            { to, reason, scheduled_for: scheduledFor },
            { preserveScroll: true, onSuccess: () => { setReason(''); setScheduledFor(''); }, onFinish: () => setBusy(false) },
        );
    };

    const needsReason = actions.some((a) => a.needs_reason);
    const needsSchedule = actions.some((a) => a.needs_schedule);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={article.title} />
            <div className="grid gap-8 p-4 lg:grid-cols-3">
                <article className="lg:col-span-2">
                    {flash?.success && <p className="mb-4 rounded-md border border-green-600/30 bg-green-50 px-3 py-2 text-sm text-green-800">{flash.success}</p>}
                    <p className="text-muted-foreground text-xs uppercase">
                        {article.category} · {statusLabels[article.status]}
                    </p>
                    <h1 className="font-display mt-1 text-3xl leading-tight font-semibold">{article.title}</h1>
                    {article.subtitle && <p className="text-muted-foreground mt-2 text-lg">{article.subtitle}</p>}
                    <p className="text-muted-foreground mt-2 text-sm">By {article.author}</p>
                    {lead && (
                        <figure className="mt-4 max-w-[68ch]">
                            <img src={lead.url} alt={lead.alt} className="w-full rounded" />
                            <figcaption className="text-muted-foreground mt-1 text-xs">
                                {lead.caption} {lead.credit && `Photo: ${lead.credit}`} · {lead.is_public ? 'Approved for public release' : 'NOT yet approved for public release'}
                            </figcaption>
                            {lead.can_release && (
                                <Button type="button" size="sm" className="mt-2" variant={lead.is_public ? 'outline' : 'default'} onClick={() => router.put(route('media.release', lead.id), { public: !lead.is_public }, { preserveScroll: true })}>
                                    {lead.is_public ? 'Withdraw photo from public' : 'Approve photo for public release'}
                                </Button>
                            )}
                        </figure>
                    )}
                    <div className="mt-6 max-w-[68ch] font-serif text-lg leading-[1.8]">
                        {article.paragraphs.map((p, i) => (
                            <p key={i} className="mb-4">
                                {p}
                            </p>
                        ))}
                    </div>
                </article>

                <aside className="flex flex-col gap-6">
                    <section aria-labelledby="actions-h">
                        <h2 id="actions-h" className="mb-2 text-lg font-semibold">
                            Next steps
                        </h2>
                        {can_edit && (
                            <Button asChild variant="outline" className="mb-3">
                                <Link href={`/articles/${article.id}/edit`}>Edit article</Link>
                            </Button>
                        )}
                        {article.public_url && (
                            <p className="mb-3 text-sm">
                                <a href={article.public_url} className="underline" target="_blank" rel="noreferrer">
                                    View on the public site
                                </a>
                            </p>
                        )}
                        {actions.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No actions are available to you at this stage.</p>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {needsReason && (
                                    <div className="grid gap-1">
                                        <label htmlFor="reason" className="text-sm font-medium">
                                            Comment / reason (required to reject or return)
                                        </label>
                                        <textarea id="reason" value={reason} onChange={(e) => setReason(e.target.value)} rows={4} className="border-input bg-background rounded-md border p-2 text-sm" />
                                    </div>
                                )}
                                {needsSchedule && (
                                    <div className="grid gap-1">
                                        <label htmlFor="when" className="text-sm font-medium">
                                            Publish at (for “Schedule”)
                                        </label>
                                        <input id="when" type="datetime-local" value={scheduledFor} onChange={(e) => setScheduledFor(e.target.value)} className="border-input bg-background h-9 rounded-md border px-2 text-sm" />
                                    </div>
                                )}
                                <InputError message={errors.transition ?? errors.to ?? errors.scheduled_for ?? errors.reason} />
                                <div className="flex flex-wrap gap-2">
                                    {actions.map((a) => (
                                        <Button key={a.to} type="button" disabled={busy} variant={a.danger ? 'destructive' : 'default'} onClick={() => act(a.to)}>
                                            {a.label}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </section>

                    <section aria-labelledby="history-h">
                        <h2 id="history-h" className="mb-2 text-lg font-semibold">
                            History
                        </h2>
                        <ol className="divide-y text-sm">
                            {history.map((h) => (
                                <li key={h.id} className="py-2">
                                    <div>
                                        <span className="font-medium">{statusLabels[h.to]}</span> <span className="text-muted-foreground">by {h.actor}</span>
                                    </div>
                                    {h.reason && <p className="mt-1 italic">“{h.reason}”</p>}
                                    <div className="text-muted-foreground text-xs">{formatWhen(h.at)}</div>
                                </li>
                            ))}
                        </ol>
                    </section>
                </aside>
            </div>
        </AppLayout>
    );
}
