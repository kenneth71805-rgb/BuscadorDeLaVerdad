import PublicLayout, { NavCategory } from '@/components/public-layout';
import { Head, Link } from '@inertiajs/react';

type Props = { nav: NavCategory[]; years: { year: number; count: number }[] };

export default function Archives({ nav, years }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title="Archives" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Archives</h1>
            {years.length === 0 ? (
                <p className="text-ink-soft mt-6">Nothing has been published yet.</p>
            ) : (
                <ul className="divide-paper-edge mt-4 divide-y">
                    {years.map((y) => (
                        <li key={y.year} className="py-3">
                            <Link href={`/archives/${y.year}`} className="font-display hover:text-forest text-2xl">
                                {y.year}
                            </Link>{' '}
                            <span className="text-ink-soft text-sm">
                                · {y.count} stor{y.count === 1 ? 'y' : 'ies'}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
            <p className="mt-6 text-sm">
                Looking for a printed edition? See the <Link href="/issues" className="underline">publication issues</Link>.
            </p>
        </PublicLayout>
    );
}
