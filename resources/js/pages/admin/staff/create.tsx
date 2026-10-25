import StaffForm from '@/components/staff-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff accounts', href: '/admin/staff' },
    { title: 'New account', href: '/admin/staff/create' },
];

export default function Create({ roles, categories }: { roles: { value: string; label: string }[]; categories: { id: number; name: string }[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New staff account" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New staff account</h1>
                <StaffForm roles={roles} categories={categories} />
            </div>
        </AppLayout>
    );
}
