import CategoryForm from '@/components/category-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Categories', href: '/admin/categories' }, { title: 'New', href: '/admin/categories/create' }]}>
            <Head title="New category" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New category</h1>
                <CategoryForm />
            </div>
        </AppLayout>
    );
}
