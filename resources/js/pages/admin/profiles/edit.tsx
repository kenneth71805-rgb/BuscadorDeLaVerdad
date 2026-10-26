import ProfileForm, { type ProfileData } from '@/components/profile-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ profile, categories }: { profile: ProfileData; categories: { id: number; name: string }[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Editorial board', href: '/admin/profiles' }, { title: profile.name, href: `/admin/profiles/${profile.id}/edit` }]}>
            <Head title={`Edit ${profile.name}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit {profile.name}</h1>
                <ProfileForm profile={profile} categories={categories} />
            </div>
        </AppLayout>
    );
}
