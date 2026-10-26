import BrandMark from '@/components/brand-mark';
import { Link, router } from '@inertiajs/react';
import { FormEventHandler, PropsWithChildren, useState } from 'react';

export type NavCategory = { name: string; slug: string };

export type Card = {
    title: string;
    subtitle: string | null;
    excerpt: string;
    category: { name: string; slug: string };
    author: string;
    published_at: string | null;
    minutes: number;
    url: string;
    image: { url: string; alt: string; caption: string | null; credit: string | null; width: number; height: number } | null;
};

export function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : '';
}

export default function PublicLayout({ nav, children }: PropsWithChildren<{ nav: NavCategory[] }>) {
    const [q, setQ] = useState('');

    const search: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/search', { q });
    };

    return (
        <div className="bg-paper text-ink min-h-screen font-serif">
            <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">
                Skip to content
            </a>

            <header className="border-brass border-b-4 border-double">
                <div className="mx-auto flex max-w-6xl flex-col items-center px-4 pt-6 pb-4 text-center">
                    <Link href="/" className="text-forest flex flex-col items-center gap-2">
                        <BrandMark className="h-16 w-auto" />
                        <span className="font-display text-4xl font-semibold tracking-tight sm:text-6xl">Los Sueños</span>
                    </Link>
                    <p className="text-brass-text mt-1 text-xs tracking-[0.35em] uppercase sm:text-sm">Buscador De La Verdad</p>
                    <p className="text-ink-soft mt-1 text-xs">Official student publication of Kolehiyo ng Lungsod ng Dasmariñas</p>
                </div>

                <nav aria-label="Primary" className="border-paper-edge border-t">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm">
                        <ul className="flex flex-wrap items-center gap-x-5 gap-y-1">
                            <li>
                                <Link href="/" className="hover:text-forest underline-offset-4 hover:underline">
                                    Home
                                </Link>
                            </li>
                            <li className="relative">
                                <details className="group">
                                    <summary className="hover:text-forest cursor-pointer list-none underline-offset-4 hover:underline">Sections ▾</summary>
                                    <ul className="bg-paper border-paper-edge absolute left-0 z-20 mt-2 w-64 border p-2 shadow-md">
                                        {nav.length === 0 && <li className="text-ink-soft p-2">No sections yet.</li>}
                                        {nav.map((c) => (
                                            <li key={c.slug}>
                                                <Link href={`/${c.slug}`} className="hover:bg-paper-edge block px-2 py-1.5">
                                                    {c.name}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </details>
                            </li>
                            <li>
                                <Link href="/galleries" className="hover:text-forest underline-offset-4 hover:underline">
                                    Photos
                                </Link>
                            </li>
                            <li>
                                <Link href="/issues" className="hover:text-forest underline-offset-4 hover:underline">
                                    Issues
                                </Link>
                            </li>
                            <li>
                                <Link href="/archives" className="hover:text-forest underline-offset-4 hover:underline">
                                    Archives
                                </Link>
                            </li>
                            <li>
                                <Link href="/about" className="hover:text-forest underline-offset-4 hover:underline">
                                    About
                                </Link>
                            </li>
                            <li>
                                <Link href="/contact" className="hover:text-forest underline-offset-4 hover:underline">
                                    Contact
                                </Link>
                            </li>
                        </ul>
                        <form onSubmit={search} role="search" className="flex">
                            <label htmlFor="site-search" className="sr-only">
                                Search stories
                            </label>
                            <input
                                id="site-search"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Search stories"
                                className="border-ink-soft w-40 border bg-white px-2 py-1 sm:w-56"
                            />
                            <button type="submit" className="bg-forest px-3 py-1 text-white">
                                Search
                            </button>
                        </form>
                    </div>
                </nav>
            </header>

            <main id="main" className="mx-auto max-w-6xl px-4 py-8">
                {children}
            </main>

            <footer className="border-brass mt-12 border-t-4 border-double">
                <div className="text-ink-soft mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-6 text-xs">
                    <p>© Los Sueños – Buscador De La Verdad, Kolehiyo ng Lungsod ng Dasmariñas</p>
                    <Link href="/login" className="underline">
                        Staff login
                    </Link>
                </div>
            </footer>
        </div>
    );
}
