import ArticleForm, { type MediaOption } from '@/components/article-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Articles', href: '/articles' },
    { title: 'New article', href: '/articles/create' },
];

export default function Create({ categories, media }: { categories: { id: number; name: string }[]; media: MediaOption[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New article" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New article</h1>
                <ArticleForm categories={categories} media={media} />
            </div>
        </AppLayout>
    );
}
