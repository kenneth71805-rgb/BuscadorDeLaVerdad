import AssignmentForm, { AssignmentData } from '@/components/assignment-form';
import Flash from '@/components/flash';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';

type Props = {
    assignment: AssignmentData;
    categories: { id: number; name: string }[];
    staff: { id: number; name: string; role: string }[];
    can_edit: boolean;
    statuses: Record<string, string>;
};

export default function Edit({ assignment, categories, staff, can_edit, statuses }: Props) {
    const setStatus = (status: string) => router.put(route('assignments.status', assignment.id), { status }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ title: 'Assignments', href: '/assignments' }, { title: assignment.title, href: `/assignments/${assignment.id}/edit` }]}>
            <Head title={assignment.title} />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold">{assignment.title}</h1>
                <Flash />
                <section aria-labelledby="status-h">
                    <h2 id="status-h" className="mb-2 text-lg font-semibold">
                        Status: {statuses[assignment.status]}
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {Object.entries(statuses)
                            .filter(([value]) => value !== assignment.status)
                            .map(([value, label]) => (
                                <Button key={value} variant={value === 'done' ? 'default' : 'outline'} onClick={() => setStatus(value)}>
                                    Mark as {label.toLowerCase()}
                                </Button>
                            ))}
                    </div>
                </section>
                {!can_edit && <p className="text-muted-foreground text-sm">You can update the status of this assignment, but only its creator or an editor can change the details.</p>}
                <AssignmentForm assignment={assignment} categories={categories} staff={staff} readOnly={!can_edit} />
            </div>
        </AppLayout>
    );
}
