import PublicLayout, { NavCategory, formatDate } from '@/components/public-layout';
import { Head } from '@inertiajs/react';

type Photo = { id: number; url: string; thumb: string; alt: string; caption: string | null; credit: string | null; width: number; height: number };
type Props = { nav: NavCategory[]; gallery: { title: string; description: string | null; published_at: string | null }; photos: Photo[] };

export default function Gallery({ nav, gallery, photos }: Props) {
    return (
        <PublicLayout nav={nav}>
            <Head title={gallery.title}>
                <meta property="og:title" content={gallery.title} />
                {photos[0] && <meta property="og:image" content={photos[0].url} />}
            </Head>
            <h1 className="font-display text-forest text-4xl font-semibold">{gallery.title}</h1>
            <p className="text-ink-soft mt-1 text-sm">{formatDate(gallery.published_at)}</p>
            {gallery.description && <p className="mt-3 max-w-[68ch] text-lg">{gallery.description}</p>}
            <div className="mt-8 grid gap-10">
                {photos.map((p) => (
                    <figure key={p.id} className="mx-auto max-w-4xl">
                        <img src={p.url} alt={p.alt} width={p.width} height={p.height} loading="lazy" className="w-full" />
                        {(p.caption || p.credit) && (
                            <figcaption className="text-ink-soft mt-1 text-sm">
                                {p.caption} {p.credit && <span className="italic">Photo: {p.credit}</span>}
                            </figcaption>
                        )}
                    </figure>
                ))}
            </div>
        </PublicLayout>
    );
}
