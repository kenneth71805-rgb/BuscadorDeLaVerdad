import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    article: Card & { paragraphs: string[]; lead: { url: string; alt: string; caption: string | null; credit: string | null; width: number; height: number } | null; corrected_at: string | null; corrections: { note: string; at: string | null }[] };
    related: Card[];
};

export default function Article({ nav, article, related }: Props) {
    const description = article.subtitle ?? article.excerpt;

    return (
        <PublicLayout nav={nav}>
            <Head title={article.title}>
                <meta name="description" content={description} />
                <meta property="og:type" content="article" />
                <meta property="og:title" content={article.title} />
                <meta property="og:description" content={description} />
                <meta property="og:site_name" content="Los Sueños – Buscador De La Verdad" />
                {article.lead && <meta property="og:image" content={article.lead.url} />}
            </Head>

            <article className="mx-auto max-w-[68ch]">
                <Link href={`/${article.category.slug}`} className="text-brass-text text-xs tracking-widest uppercase">
                    {article.category.name}
                </Link>
                <h1 className="font-display mt-2 text-4xl leading-tight font-semibold sm:text-5xl">{article.title}</h1>
                {article.subtitle && <p className="text-ink-soft mt-3 text-xl">{article.subtitle}</p>}
                <p className="text-ink-soft border-paper-edge mt-4 border-y py-2 text-sm">
                    By <span className="text-ink font-medium">{article.author}</span> · <time dateTime={article.published_at ?? undefined}>{formatDate(article.published_at)}</time> ·{' '}
                    {article.minutes} min read
                    {article.corrected_at && <> · <span className="font-medium">Updated {formatDate(article.corrected_at)}</span></>}
                </p>

                {article.lead && (
                    <figure className="mt-6">
                        <img src={article.lead.url} alt={article.lead.alt} width={article.lead.width} height={article.lead.height} className="w-full" />
                        {(article.lead.caption || article.lead.credit) && (
                            <figcaption className="text-ink-soft mt-1 text-sm">
                                {article.lead.caption} {article.lead.credit && <span className="italic">Photo: {article.lead.credit}</span>}
                            </figcaption>
                        )}
                    </figure>
                )}

                <div className="mt-6 text-[1.125rem] leading-[1.8]">
                    {article.paragraphs.map((p, i) => (
                        <p key={i} className={i === 0 ? 'mb-5 first-letter:font-display first-letter:text-forest first-letter:float-left first-letter:mr-2 first-letter:text-6xl first-letter:leading-[0.85]' : 'mb-5'}>
                            {p}
                        </p>
                    ))}
                </div>

                {article.corrections.length > 0 && (
                    <aside aria-labelledby="corr-h" className="border-brass mt-8 border-l-4 bg-white px-4 py-3 text-sm">
                        <h2 id="corr-h" className="font-display text-brass-text text-base font-semibold">
                            {article.corrections.length === 1 ? 'Correction' : 'Corrections'}
                        </h2>
                        <ul className="mt-1 space-y-1">
                            {article.corrections.map((c, i) => (
                                <li key={i}>
                                    {c.note} <span className="text-ink-soft">({formatDate(c.at)})</span>
                                </li>
                            ))}
                        </ul>
                    </aside>
                )}

                <p className="text-ink-soft border-paper-edge mt-8 border-t pt-3 text-sm">Spotted an error? Corrections and feedback will be accepted through the Contact page once it is available.</p>
            </article>

            {related.length > 0 && (
                <section className="mx-auto mt-12 max-w-[68ch]" aria-labelledby="related">
                    <h2 id="related" className="font-display text-forest border-ink border-b-2 pb-1 text-xl">
                        More from {article.category.name}
                    </h2>
                    <ul className="divide-paper-edge divide-y">
                        {related.map((s) => (
                            <li key={s.url} className="py-3">
                                <Link href={s.url} className="font-display hover:text-forest text-lg">
                                    {s.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </PublicLayout>
    );
}
