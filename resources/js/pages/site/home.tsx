import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    lead: Card | null;
    latest: Card[];
    sections: { name: string; slug: string; stories: Card[] }[];
};

export default function Home({ nav, lead, latest, sections }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title="Home">
                <meta name="description" content="Los Sueños – Buscador De La Verdad, the official student publication of Kolehiyo ng Lungsod ng Dasmariñas." />
            </Head>

            {!lead ? (
                <section className="mx-auto max-w-xl py-20 text-center">
                    <h1 className="font-display text-forest text-3xl">The first issue is in the works.</h1>
                    <p className="text-ink-soft mt-3">Stories will appear here as soon as the editors publish them.</p>
                </section>
            ) : (
                <>
                    <div className="grid gap-10 lg:grid-cols-12">
                        <article className="lg:col-span-8">
                            <Link href={`/${lead.category.slug}`} className="text-brass-text text-xs tracking-widest uppercase">
                                {lead.category.name}
                            </Link>
                            <h1 className="font-display mt-2 text-4xl leading-tight font-semibold sm:text-5xl">
                                <Link href={lead.url} className="hover:text-forest">
                                    {lead.title}
                                </Link>
                            </h1>
                            {lead.subtitle && <p className="text-ink-soft mt-3 text-xl">{lead.subtitle}</p>}
                            <p className="mt-4 text-lg leading-relaxed">{lead.excerpt}</p>
                            <p className="text-ink-soft mt-3 text-sm">
                                By {lead.author} · {formatDate(lead.published_at)} · {lead.minutes} min read
                            </p>
                        </article>

                        <aside aria-labelledby="latest-heading" className="border-paper-edge lg:col-span-4 lg:border-l lg:pl-8">
                            <h2 id="latest-heading" className="font-display text-forest mb-3 text-xl">
                                Latest
                            </h2>
                            {latest.length === 0 ? (
                                <p className="text-ink-soft text-sm">More stories soon.</p>
                            ) : (
                                <ul className="divide-paper-edge divide-y">
                                    {latest.map((s) => (
                                        <li key={s.url} className="py-3">
                                            <span className="text-brass-text text-[0.7rem] tracking-widest uppercase">{s.category.name}</span>
                                            <h3 className="font-display text-lg leading-snug">
                                                <Link href={s.url} className="hover:text-forest">
                                                    {s.title}
                                                </Link>
                                            </h3>
                                            <p className="text-ink-soft text-xs">{formatDate(s.published_at)}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </aside>
                    </div>

                    {sections.map((section) => (
                        <section key={section.slug} className="mt-12" aria-labelledby={`sec-${section.slug}`}>
                            <div className="border-ink flex items-baseline justify-between border-b-2 pb-1">
                                <h2 id={`sec-${section.slug}`} className="font-display text-forest text-2xl">
                                    {section.name}
                                </h2>
                                <Link href={`/${section.slug}`} className="text-sm underline">
                                    All {section.name}
                                </Link>
                            </div>
                            <div className="mt-4 grid gap-6 md:grid-cols-3">
                                {section.stories.map((s, i) => (
                                    <article key={s.url} className={i === 0 ? 'md:col-span-2' : ''}>
                                        <h3 className={`font-display leading-snug ${i === 0 ? 'text-2xl' : 'text-lg'}`}>
                                            <Link href={s.url} className="hover:text-forest">
                                                {s.title}
                                            </Link>
                                        </h3>
                                        <p className="text-ink-soft mt-1 text-sm">{s.excerpt}</p>
                                    </article>
                                ))}
                            </div>
                        </section>
                    ))}
                </>
            )}
        </PublicLayout>
    );
}
