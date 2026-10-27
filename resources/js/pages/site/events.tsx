import PublicLayout, { NavCategory } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type E = { title: string; description: string | null; location: string | null; starts_at: string; ends_at: string | null; category: string | null };
type Props = { nav: NavCategory[]; events: { data: E[]; links: { url: string | null; label: string; active: boolean }[] } };
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');
const when = (iso: string) => new Date(iso).toLocaleString(undefined, { dateStyle: 'full', timeStyle: 'short' });

export default function Events({ nav, events }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title="Events" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Upcoming events</h1>
            {events.data.length === 0 ? (
                <p className="text-ink-soft mt-6">No public events are scheduled right now.</p>
            ) : (
                <ul className="divide-paper-edge mt-4 divide-y">
                    {events.data.map((e, i) => (
                        <li key={i} className="py-5">
                            {e.category && <span className="text-brass-text text-[0.7rem] tracking-widest uppercase">{e.category}</span>}
                            <h2 className="font-display text-2xl leading-snug">{e.title}</h2>
                            <p className="text-ink-soft text-sm">
                                {when(e.starts_at)}
                                {e.location ? ` · ${e.location}` : ''}
                            </p>
                            {e.description && <p className="mt-2 max-w-[68ch]">{e.description}</p>}
                        </li>
                    ))}
                </ul>
            )}
            <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2 text-sm">
                {events.links.filter((l) => l.url).map((l) => (
                    <Link key={l.label} href={l.url!} className={`border px-3 py-1 ${l.active ? 'bg-forest text-white' : ''}`}>
                        {pageLabel(l.label)}
                    </Link>
                ))}
            </nav>
        </PublicLayout>
    );
}
