import PublicLayout, { NavCategory, formatDate } from '@/components/public-layout';
import { Head } from '@inertiajs/react';

type Props = {
    nav: NavCategory[];
    issue: { title: string; issue_date: string; description: string | null; cover: { url: string; alt: string } | null; download_url: string; size_mb: number };
};

export default function Issue({ nav, issue }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title={issue.title} />
            <div className="grid gap-8 md:grid-cols-3">
                <div>{issue.cover ? <img src={issue.cover.url} alt={issue.cover.alt} className="w-full" /> : <div className="bg-paper-edge text-ink-soft flex aspect-[3/4] items-center justify-center">No cover</div>}</div>
                <div className="md:col-span-2">
                    <h1 className="font-display text-forest text-4xl font-semibold">{issue.title}</h1>
                    <p className="text-ink-soft mt-1 text-sm">{formatDate(issue.issue_date)}</p>
                    {issue.description && <p className="mt-4 max-w-[68ch] text-lg">{issue.description}</p>}
                    <a href={issue.download_url} className="bg-forest mt-6 inline-block px-5 py-2 text-white">
                        Download PDF ({issue.size_mb} MB)
                    </a>
                </div>
            </div>
        </PublicLayout>
    );
}
