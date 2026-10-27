import PublicLayout, { Card, NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    lead: Card | null;
    latest: Card[];
    sections: { name: string; slug: string; stories: Card[] }[];
    galleries: { title: string; url: string; cover: { url: string; alt: string } }[];
    events: { title: string; location: string | null; starts_at: string; category: string | null }[];
    issues: { title: string; slug: string; url: string; issue_date: string; cover: { url: string; alt: string } | null }[];
};

export default function Home({ nav, lead, latest, sections, galleries, events, issues }: Props) {
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
                            {lead.image && (
                                <Link href={lead.url} className="mb-4 block">
                                    <img src={lead.image.url} alt={lead.image.alt} width={lead.image.width} height={lead.image.height} className="aspect-[3/2] w-full object-cover" />
                                </Link>
                            )}
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

                    {galleries.length > 0 && (
                        <section className="mt-12" aria-labelledby="photo-highlights">
                            <div className="border-ink flex items-baseline justify-between border-b-2 pb-1">
                                <h2 id="photo-highlights" className="font-display text-forest text-2xl">
                                    Photojournalism
                                </h2>
                                <Link href="/galleries" className="text-sm underline">
                                    All galleries
                                </Link>
                            </div>
                            <div className="mt-4 grid gap-6 sm:grid-cols-3">
                                {galleries.map((g) => (
                                    <Link key={g.url} href={g.url} className="block">
                                        <img src={g.cover.url} alt={g.cover.alt} loading="lazy" className="aspect-[4/3] w-full object-cover" />
                                        <span className="font-display mt-2 block text-lg leading-snug">{g.title}</span>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}

                    {events.length > 0 && (
                        <section className="mt-12" aria-labelledby="upcoming-events">
                            <div className="border-ink flex items-baseline justify-between border-b-2 pb-1">
                                <h2 id="upcoming-events" className="font-display text-forest text-2xl">
                                    Upcoming events
                                </h2>
                                <Link href="/events" className="text-sm underline">
                                    All events
                                </Link>
                            </div>
                            <ul className="divide-paper-edge divide-y">
                                {events.map((e, i) => (
                                    <li key={i} className="py-3">
                                        <span className="font-display text-lg">{e.title}</span>
                                        <span className="text-ink-soft text-sm">
                                            {' '}
                                            · {new Date(e.starts_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}
                                            {e.location ? ` · ${e.location}` : ''}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {issues.length > 0 && (
                        <section className="mt-12" aria-labelledby="recent-issues">
                            <div className="border-ink flex items-baseline justify-between border-b-2 pb-1">
                                <h2 id="recent-issues" className="font-display text-forest text-2xl">
                                    Recent issues
                                </h2>
                                <Link href="/issues" className="text-sm underline">
                                    All issues
                                </Link>
                            </div>
                            <div className="mt-4 grid gap-6 sm:grid-cols-3">
                                {issues.map((i) => (
                                    <Link key={i.slug} href={i.url} className="block">
                                        {i.cover ? <img src={i.cover.url} alt={i.cover.alt} loading="lazy" className="aspect-[3/4] w-full object-cover" /> : <div className="bg-paper-edge text-ink-soft flex aspect-[3/4] items-center justify-center text-sm">No cover</div>}
                                        <span className="font-display mt-2 block text-lg leading-snug">{i.title}</span>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}
                </>
            )}
        </PublicLayout>
    );
}
