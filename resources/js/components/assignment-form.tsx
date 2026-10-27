import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type AssignmentData = { id: number; title: string; description: string | null; category_id: number; assigned_to: number; due_at: string; status: string };
type Props = { assignment?: AssignmentData; categories: { id: number; name: string }[]; staff: { id: number; name: string; role: string }[]; readOnly?: boolean };
type FormData = { title: string; description: string; category_id: number | ''; assigned_to: number | ''; due_at: string };

export default function AssignmentForm({ assignment, categories, staff, readOnly }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        title: assignment?.title ?? '',
        description: assignment?.description ?? '',
        category_id: assignment?.category_id ?? (categories.length === 1 ? categories[0].id : ''),
        assigned_to: assignment?.assigned_to ?? '',
        due_at: assignment?.due_at ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (assignment) put(route('assignments.update', assignment.id));
        else post(route('assignments.store'));
    };

    const select = 'border-input bg-background h-10 rounded-md border px-3 text-sm';

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <fieldset disabled={readOnly} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label htmlFor="title">What needs to be done</Label>
                    <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={150} />
                    <InputError message={errors.title} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="description">Details (optional)</Label>
                    <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={4} maxLength={2000} className="border-input bg-background rounded-md border p-2 text-sm" />
                    <InputError message={errors.description} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="category_id">Category</Label>
                    <select id="category_id" className={select} value={data.category_id} onChange={(e) => setData('category_id', e.target.value === '' ? '' : Number(e.target.value))} required>
                        <option value="">Choose…</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.category_id} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="assigned_to">Assign to</Label>
                    <select id="assigned_to" className={select} value={data.assigned_to} onChange={(e) => setData('assigned_to', e.target.value === '' ? '' : Number(e.target.value))} required>
                        <option value="">Choose…</option>
                        {staff.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name} ({s.role})
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.assigned_to} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="due_at">Deadline</Label>
                    <input id="due_at" type="datetime-local" value={data.due_at} onChange={(e) => setData('due_at', e.target.value)} required className="border-input bg-background h-10 rounded-md border px-3 text-sm" />
                    <InputError message={errors.due_at} />
                </div>
            </fieldset>
            {!readOnly && (
                <div>
                    <Button type="submit" disabled={processing}>
                        {assignment ? 'Save changes' : 'Create assignment'}
                    </Button>
                </div>
            )}
        </form>
    );
}
