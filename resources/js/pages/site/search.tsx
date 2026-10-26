import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    q: string;
    results: { data: Card[]; links: { url: string | null; label: string; active: boolean }[] } | null;
};

export default function Search({ nav, q, results }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title={q ? `Search: ${q}` : 'Search'} />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Search</h1>

            {results === null ? (
                <p className="text-ink-soft mt-6">Type at least two characters in the search box above.</p>
            ) : results.data.length === 0 ? (
                <p className="mt-6">No published stories match “{q}”.</p>
            ) : (
                <ul className="divide-paper-edge mt-4 divide-y">
                    {results.data.map((s) => (
                        <li key={s.url} className="py-4">
                            <span className="text-brass-text text-[0.7rem] tracking-widest uppercase">{s.category.name}</span>
                            <h2 className="font-display text-xl leading-snug">
                                <Link href={s.url} className="hover:text-forest">
                                    {s.title}
                                </Link>
                            </h2>
                            <p className="text-ink-soft text-sm">{s.excerpt}</p>
                            <p className="text-ink-soft text-xs">{formatDate(s.published_at)}</p>
                        </li>
                    ))}
                </ul>
            )}
        </PublicLayout>
    );
}
