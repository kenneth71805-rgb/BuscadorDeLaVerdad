import PublicLayout, { NavCategory, formatDate } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type I = { title: string; slug: string; issue_date: string; url: string; cover: { url: string; alt: string } | null };
type Props = { nav: NavCategory[]; issues: { data: I[]; links: { url: string | null; label: string; active: boolean }[] } };
const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Issues({ nav, issues }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title="Publication issues" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Issues</h1>
            {issues.data.length === 0 ? (
                <p className="text-ink-soft mt-6">No issues have been published yet.</p>
            ) : (
                <ul className="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {issues.data.map((i) => (
                        <li key={i.slug}>
                            <Link href={i.url} className="block">
                                {i.cover ? <img src={i.cover.url} alt={i.cover.alt} loading="lazy" className="aspect-[3/4] w-full object-cover" /> : <div className="bg-paper-edge text-ink-soft flex aspect-[3/4] items-center justify-center text-sm">No cover</div>}
                            </Link>
                            <h2 className="font-display mt-2 text-lg leading-snug">
                                <Link href={i.url} className="hover:text-forest">
                                    {i.title}
                                </Link>
                            </h2>
                            <p className="text-ink-soft text-xs">{formatDate(i.issue_date)}</p>
                        </li>
                    ))}
                </ul>
            )}
            <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2 text-sm">
                {issues.links.filter((l) => l.url).map((l) => (
                    <Link key={l.label} href={l.url!} className={`border px-3 py-1 ${l.active ? 'bg-forest text-white' : ''}`}>
                        {pageLabel(l.label)}
                    </Link>
                ))}
            </nav>
        </PublicLayout>
    );
}
