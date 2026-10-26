import InputError from '@/components/input-error';
import MediaPicker, { PickerMedia } from '@/components/media-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type GalleryData = { id: number; title: string; slug: string; description: string | null; status: string; items: number[] };

type FormData = { title: string; description: string; status: string; items: number[] };

export default function GalleryForm({ gallery, media }: { gallery?: GalleryData; media: PickerMedia[] }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        title: gallery?.title ?? '',
        description: gallery?.description ?? '',
        status: gallery?.status ?? 'draft',
        items: gallery?.items ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (gallery) put(route('newsroom.galleries.update', gallery.id));
        else post(route('newsroom.galleries.store'));
    };

    return (
        <form onSubmit={submit} className="flex max-w-3xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={150} />
                {gallery && <p className="text-muted-foreground text-xs">Web address: /galleries/{gallery.slug} (fixed)</p>}
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} maxLength={1000} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.description} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="status">Status</Label>
                <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className="border-input bg-background h-10 rounded-md border px-3 text-sm">
                    <option value="draft">Draft (private)</option>
                    <option value="published">Published</option>
                </select>
                <InputError message={errors.status} />
            </div>
            <fieldset className="grid gap-2">
                <legend className="mb-1 text-sm font-medium">Photos ({data.items.length} selected)</legend>
                <MediaPicker media={media} selected={data.items} onChange={(ids) => setData('items', ids)} />
                <InputError message={errors.items} />
            </fieldset>
            <div>
                <Button type="submit" disabled={processing}>
                    Save gallery
                </Button>
            </div>
        </form>
    );
}
