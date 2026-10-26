import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    year: number;
    category: string | null;
    articles: { data: Card[]; links: { url: string | null; label: string; active: boolean }[] };
};
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function ArchiveYear({ nav, year, category, articles }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title={`Archive ${year}`} />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Archive: {year}</h1>
            <nav aria-label="Filter by section" className="mt-3 flex flex-wrap gap-3 text-sm">
                <Link href={`/archives/${year}`} className={!category ? 'font-semibold underline' : ''}>
                    All sections
                </Link>
                {nav.map((c) => (
                    <Link key={c.slug} href={`/archives/${year}?category=${c.slug}`} className={category === c.slug ? 'font-semibold underline' : ''}>
                        {c.name}
                    </Link>
                ))}
            </nav>
            {articles.data.length === 0 ? (
                <p className="text-ink-soft mt-6">No stories in this section for {year}.</p>
            ) : (
                <ul className="divide-paper-edge mt-2 divide-y">
                    {articles.data.map((s) => (
                        <li key={s.url} className="py-4">
                            <span className="text-brass-text text-[0.7rem] tracking-widest uppercase">{s.category.name}</span>
                            <h2 className="font-display text-xl leading-snug">
                                <Link href={s.url} className="hover:text-forest">
                                    {s.title}
                                </Link>
                            </h2>
                            <p className="text-ink-soft text-xs">
                                By {s.author} · {formatDate(s.published_at)}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
            <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2 text-sm">
                {articles.links.filter((l) => l.url).map((l) => (
                    <Link key={l.label} href={l.url!} className={`border px-3 py-1 ${l.active ? 'bg-forest text-white' : ''}`}>
                        {pageLabel(l.label)}
                    </Link>
                ))}
            </nav>
        </PublicLayout>
    );
}
