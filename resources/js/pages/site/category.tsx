import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    category: { name: string; slug: string; description: string | null };
    articles: { data: Card[]; links: { url: string | null; label: string; active: boolean }[] };
};

const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Category({ nav, category, articles }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title={category.name} />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">{category.name}</h1>
            {category.description && <p className="text-ink-soft mt-2">{category.description}</p>}

            {articles.data.length === 0 ? (
                <p className="text-ink-soft mt-8">No stories have been published in this section yet.</p>
            ) : (
                <ul className="divide-paper-edge mt-4 divide-y">
                    {articles.data.map((s) => (
                        <li key={s.url} className="flex gap-4 py-5">
                            {s.image && <img src={s.image.url} alt={s.image.alt} loading="lazy" className="hidden aspect-[4/3] w-40 shrink-0 object-cover sm:block" />}
                            <div>
                            <h2 className="font-display text-2xl leading-snug">
                                <Link href={s.url} className="hover:text-forest">
                                    {s.title}
                                </Link>
                            </h2>
                            <p className="text-ink-soft mt-1">{s.excerpt}</p>
                            <p className="text-ink-soft mt-1 text-xs">
                                By {s.author} · {formatDate(s.published_at)}
                            </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2 text-sm">
                {articles.links
                    .filter((l) => l.url)
                    .map((l) => (
                        <Link key={l.label} href={l.url!} className={`border px-3 py-1 ${l.active ? 'bg-forest text-white' : ''}`}>
                            {pageLabel(l.label)}
                        </Link>
                    ))}
            </nav>
        </PublicLayout>
    );
}
