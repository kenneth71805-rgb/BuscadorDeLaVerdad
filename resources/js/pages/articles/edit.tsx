import ArticleForm, { type ArticleDraft } from '@/components/article-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

export default function Edit({ article, categories }: { article: ArticleDraft; categories: { id: number; name: string }[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Articles', href: '/articles' },
        { title: 'Edit', href: `/articles/${article.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${article.title}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit article</h1>
                <ArticleForm article={article} categories={categories} />
            </div>
        </AppLayout>
    );
}
