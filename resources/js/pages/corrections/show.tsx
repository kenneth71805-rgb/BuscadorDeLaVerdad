import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Text = { title: string | null; subtitle: string | null; body: string | null };
type Props = {
    revision: {
        id: number; version: number; status: string; reason: string; public_note: string | null; requested_by: string; created_at: string | null;
        decided_by: string | null; decided_at: string | null; decision_reason: string | null; proposed: Text; current: Text;
    };
    article: { id: number; title: string; category: string };
    can_decide: boolean;
};

const labels: Record<string, string> = { pending: 'Waiting for review', applied: 'Approved and published', declined: 'Declined' };

function Panel({ heading, text }: { heading: string; text: Text }) {
    return (
        <section className="rounded-md border p-4">
            <h2 className="text-muted-foreground mb-2 text-xs font-semibold uppercase">{heading}</h2>
            <h3 className="font-display text-xl font-semibold">{text.title}</h3>
            {text.subtitle && <p className="text-muted-foreground">{text.subtitle}</p>}
            <div className="mt-3 font-serif leading-relaxed whitespace-pre-line">{text.body}</div>
        </section>
    );
}

export default function Show({ revision, article, can_decide }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const [reason, setReason] = useState('');
    const decide = (decision: 'approve' | 'decline') => router.put(route('corrections.decide', revision.id), { decision, reason }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ title: 'Articles', href: '/articles' }, { title: article.title, href: `/articles/${article.id}` }, { title: `Correction ${revision.version}`, href: `/corrections/${revision.id}` }]}>
            <Head title={`Correction: ${article.title}`} />
            <div className="flex flex-col gap-5 p-4">
                <div>
                    <p className="text-muted-foreground text-xs uppercase">
                        {article.category} · {labels[revision.status]}
                    </p>
                    <h1 className="text-2xl font-semibold">Correction to “{article.title}”</h1>
                    <p className="text-muted-foreground text-sm">
                        Proposed by {revision.requested_by} · {formatWhen(revision.created_at)} ·{' '}
                        <Link href={`/articles/${article.id}`} className="underline">
                            Back to the article
                        </Link>
                    </p>
                </div>

                <section className="rounded-md border p-4 text-sm">
                    <p>
                        <span className="font-semibold">Why (internal):</span> {revision.reason}
                    </p>
                    <p className="mt-2">
                        <span className="font-semibold">Public notice:</span> {revision.public_note ?? 'None. Readers will only see “Updated”.'}
                    </p>
                    {revision.decided_at && (
                        <p className="mt-2">
                            <span className="font-semibold">Decision:</span> {labels[revision.status]} by {revision.decided_by} · {formatWhen(revision.decided_at)}
                            {revision.decision_reason ? ` — ${revision.decision_reason}` : ''}
                        </p>
                    )}
                </section>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel heading={revision.status === 'applied' ? 'Text that was replaced' : 'Live text (stays public until approved)'} text={revision.current} />
                    <Panel heading="Proposed text" text={revision.proposed} />
                </div>

                {can_decide && (
                    <section className="grid max-w-xl gap-3 rounded-md border p-4" aria-labelledby="decide-h">
                        <h2 id="decide-h" className="font-semibold">
                            Your decision
                        </h2>
                        <label htmlFor="reason" className="text-sm font-medium">
                            Comment (required to decline)
                        </label>
                        <textarea id="reason" value={reason} onChange={(e) => setReason(e.target.value)} rows={3} maxLength={1000} className="border-input bg-background rounded-md border p-2 text-sm" />
                        <InputError message={errors.correction ?? errors.reason} />
                        <div className="flex gap-2">
                            <Button type="button" onClick={() => decide('approve')}>
                                Approve and publish
                            </Button>
                            <Button type="button" variant="destructive" onClick={() => decide('decline')}>
                                Decline
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
