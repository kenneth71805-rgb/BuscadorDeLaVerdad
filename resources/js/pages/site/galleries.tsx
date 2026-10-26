import PublicLayout, { NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type G = { title: string; description: string | null; url: string; count: number; cover: { url: string; alt: string }; published_at: string | null };
type Props = { nav: NavCategory[]; galleries: { data: G[]; links: { url: string | null; label: string; active: boolean }[] } };
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Galleries({ nav, galleries }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title="Photo galleries" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Photojournalism</h1>
            {galleries.data.length === 0 ? (
                <p className="text-ink-soft mt-6">No galleries have been published yet.</p>
            ) : (
                <ul className="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    {galleries.data.map((g) => (
                        <li key={g.url}>
                            <Link href={g.url}>
                                <img src={g.cover.url} alt={g.cover.alt} loading="lazy" className="aspect-[4/3] w-full object-cover" />
                            </Link>
                            <h2 className="font-display mt-2 text-xl leading-snug">
                                <Link href={g.url} className="hover:text-forest">
                                    {g.title}
                                </Link>
                            </h2>
                            <p className="text-ink-soft text-xs">
                                {g.count} photo{g.count === 1 ? '' : 's'} · {formatDate(g.published_at)}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
            <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2 text-sm">
                {galleries.links.filter((l) => l.url).map((l) => (
                    <Link key={l.label} href={l.url!} className={`border px-3 py-1 ${l.active ? 'bg-forest text-white' : ''}`}>
                        {pageLabel(l.label)}
                    </Link>
                ))}
            </nav>
        </PublicLayout>
    );
}
