import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type EventData = { id: number; title: string; description: string | null; location: string | null; category_id: number | null; is_public: boolean; starts_at: string; ends_at: string | null };
type FormData = { title: string; description: string; starts_at: string; ends_at: string; location: string; category_id: number | ''; is_public: boolean };

export default function EventForm({ event, categories }: { event?: EventData; categories: { id: number; name: string }[] }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        title: event?.title ?? '',
        description: event?.description ?? '',
        starts_at: event?.starts_at ?? '',
        ends_at: event?.ends_at ?? '',
        location: event?.location ?? '',
        category_id: event?.category_id ?? '',
        is_public: event?.is_public ?? false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (event) put(route('newsroom.events.update', event.id));
        else post(route('newsroom.events.store'));
    };

    const dt = 'border-input bg-background h-10 rounded-md border px-3 text-sm';

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={150} />
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="starts_at">Starts</Label>
                <input id="starts_at" type="datetime-local" className={dt} value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} required />
                <InputError message={errors.starts_at} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="ends_at">Ends (optional)</Label>
                <input id="ends_at" type="datetime-local" className={dt} value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} />
                <InputError message={errors.ends_at} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="location">Location</Label>
                <Input id="location" value={data.location} onChange={(e) => setData('location', e.target.value)} maxLength={200} />
                <InputError message={errors.location} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="category_id">Category (optional)</Label>
                <select id="category_id" className={dt} value={data.category_id} onChange={(e) => setData('category_id', e.target.value === '' ? '' : Number(e.target.value))}>
                    <option value="">None</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={4} maxLength={2000} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.description} />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_public} onChange={(e) => setData('is_public', e.target.checked)} />
                Show on the public Events page (otherwise it is on the staff calendar only)
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    Save event
                </Button>
            </div>
        </form>
    );
}
