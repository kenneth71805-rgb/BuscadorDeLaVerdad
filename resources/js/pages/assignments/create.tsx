import AssignmentForm from '@/components/assignment-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

type Props = { categories: { id: number; name: string }[]; staff: { id: number; name: string; role: string }[] };

export default function Create({ categories, staff }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Assignments', href: '/assignments' }, { title: 'New', href: '/assignments/create' }]}>
            <Head title="New assignment" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New assignment</h1>
                <AssignmentForm categories={categories} staff={staff} />
            </div>
        </AppLayout>
    );
}
