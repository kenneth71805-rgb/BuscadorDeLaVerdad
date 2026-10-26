import ProfileForm from '@/components/profile-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create({ categories }: { categories: { id: number; name: string }[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Editorial board', href: '/admin/profiles' }, { title: 'New', href: '/admin/profiles/create' }]}>
            <Head title="New profile" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New profile</h1>
                <ProfileForm categories={categories} />
            </div>
        </AppLayout>
    );
}
