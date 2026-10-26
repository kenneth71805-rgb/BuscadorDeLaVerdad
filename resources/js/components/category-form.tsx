import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type CategoryData = { id: number; name: string; slug: string; description: string | null; sort_order: number; is_active: boolean; show_in_nav: boolean };

type FormData = { name: string; description: string; sort_order: number; is_active: boolean; show_in_nav: boolean };

export default function CategoryForm({ category }: { category?: CategoryData }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: category?.name ?? '',
        description: category?.description ?? '',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
        show_in_nav: category?.show_in_nav ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (category) put(route('admin.categories.update', category.id));
        else post(route('admin.categories.store'));
    };

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required maxLength={100} />
                {category && <p className="text-muted-foreground text-xs">Web address: /{category.slug} (fixed so existing links keep working)</p>}
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="description">Description (shown on the section page)</Label>
                <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} maxLength={500} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.description} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="sort_order">Menu order (lower comes first)</Label>
                <Input id="sort_order" type="number" min={0} max={999} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} />
                <InputError message={errors.sort_order} />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                Active (inactive sections and their stories disappear from the public site)
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.show_in_nav} onChange={(e) => setData('show_in_nav', e.target.checked)} />
                Show in the public Sections menu
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    {category ? 'Save changes' : 'Create category'}
                </Button>
            </div>
        </form>
    );
}
