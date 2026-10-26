import CategoryForm, { type CategoryData } from '@/components/category-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ category }: { category: CategoryData }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Categories', href: '/admin/categories' }, { title: category.name, href: `/admin/categories/${category.id}/edit` }]}>
            <Head title={`Edit ${category.name}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit {category.name}</h1>
                <CategoryForm category={category} />
            </div>
        </AppLayout>
    );
}
