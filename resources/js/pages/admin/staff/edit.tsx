import StaffForm, { type StaffMember } from '@/components/staff-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

type Props = {
    member: StaffMember;
    roles: { value: string; label: string }[];
    categories: { id: number; name: string }[];
};

export default function Edit({ member, roles, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Staff accounts', href: '/admin/staff' },
        { title: member.name, href: `/admin/staff/${member.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${member.name}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit {member.name}</h1>
                <StaffForm member={member} roles={roles} categories={categories} />
            </div>
        </AppLayout>
    );
}
