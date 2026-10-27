import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, Link } from '@inertiajs/react';

type Props = { entries: { id: number; question: string; is_active: boolean; sort_order: number }[]; misses: { question: string; at: string }[] };

export default function Index({ entries, misses }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Helper FAQ', href: '/newsroom/faq' }]}>
            <Head title="Helper FAQ" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Website helper: FAQ answers</h1>
                    <Button asChild>
                        <Link href="/newsroom/faq/create">New FAQ entry</Link>
                    </Button>
                </div>
                <Flash />
                <p className="text-muted-foreground max-w-2xl text-sm">
                    The helper on the public site answers from these entries and from information that is already public (sections, upcoming events, latest stories, issues, contact details). Messages the helper cannot answer go to the <Link href="/inbox" className="underline">Inbox</Link>.
                </p>
                <ul className="divide-y text-sm">
                    {entries.map((e) => (
                        <li key={e.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
                            <span>
                                <span className="font-medium">{e.question}</span> <span className="text-muted-foreground">· {e.is_active ? 'Active' : 'Inactive'}</span>
                            </span>
                            <Link href={`/newsroom/faq/${e.id}/edit`} className="underline">
                                Edit
                            </Link>
                        </li>
                    ))}
                    {entries.length === 0 && <li className="text-muted-foreground py-6 text-center">No FAQ entries yet.</li>}
                </ul>

                <section aria-labelledby="misses-h">
                    <h2 id="misses-h" className="mb-1 text-lg font-semibold">
                        Questions the helper could not answer
                    </h2>
                    <p className="text-muted-foreground mb-2 text-xs">Stored anonymously (emails and numbers removed) and deleted after one week. Use them to write new answers.</p>
                    <ul className="divide-y text-sm">
                        {misses.map((m, i) => (
                            <li key={i} className="flex justify-between gap-2 py-2">
                                <span>{m.question}</span>
                                <span className="text-muted-foreground text-xs">{formatWhen(m.at)}</span>
                            </li>
                        ))}
                        {misses.length === 0 && <li className="text-muted-foreground py-2">None right now.</li>}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
