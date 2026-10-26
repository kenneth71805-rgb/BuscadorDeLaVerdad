import PublicLayout, { NavCategory } from '@/components/public-layout';
import { Head } from '@inertiajs/react';

type Profile = { id: number; name: string; position: string; category: string | null; bio: string | null; link_url: string | null; photo: { url: string; alt: string } | null };

type Props = {
    nav: NavCategory[];
    content: Record<string, string | null>;
    current: Profile[];
    former: Profile[];
};

function Block({ title, text }: { title: string; text: string | null }) {
    if (!text) return null;
    return (
        <section className="mt-8">
            <h2 className="font-display text-forest text-2xl">{title}</h2>
            <div className="mt-2 max-w-[68ch] text-lg leading-[1.8]">
                {text.split(/\n{2,}/).map((p, i) => (
                    <p key={i} className="mb-4">
                        {p}
                    </p>
                ))}
            </div>
        </section>
    );
}

function People({ title, people }: { title: string; people: Profile[] }) {
    if (people.length === 0) return null;
    return (
        <section className="mt-10" aria-labelledby={title}>
            <h2 id={title} className="font-display text-forest border-ink border-b-2 pb-1 text-2xl">
                {title}
            </h2>
            <ul className="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {people.map((p) => (
                    <li key={p.id}>
                        {p.photo && <img src={p.photo.url} alt={p.photo.alt} loading="lazy" className="mb-2 aspect-square w-32 rounded-full object-cover" />}
                        <h3 className="font-display text-xl leading-snug">{p.name}</h3>
                        <p className="text-brass-text text-sm tracking-wide uppercase">{p.position}{p.category ? ` · ${p.category}` : ''}</p>
                        {p.bio && <p className="text-ink-soft mt-1 text-sm">{p.bio}</p>}
                        {p.link_url && (
                            <a href={p.link_url} className="mt-1 inline-block text-sm underline" rel="noopener noreferrer nofollow" target="_blank">
                                Profile link
                            </a>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}

export default function About({ nav, content, current, former }: Props) {
    const empty = !content.about_history && !content.about_mission && !content.about_vision && !content.about_principles && current.length === 0 && former.length === 0;

    return (
        <PublicLayout nav={nav}>
            <Head title="About" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">About Los Sueños</h1>
            {empty && <p className="text-ink-soft mt-6">Information about the publication will appear here soon.</p>}
            <Block title="Our story" text={content.about_history} />
            <Block title="Mission" text={content.about_mission} />
            <Block title="Vision" text={content.about_vision} />
            <Block title="Editorial principles" text={content.about_principles} />
            <People title="Editorial board and Category Heads" people={current} />
            <People title="Former members" people={former} />
        </PublicLayout>
    );
}
