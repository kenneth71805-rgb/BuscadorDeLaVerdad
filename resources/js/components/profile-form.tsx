import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type ProfileData = { id: number; name: string; position: string; category_id: number | null; bio: string | null; link_url: string | null; sort_order: number; is_public: boolean; archived: boolean };

type FormData = { name: string; position: string; category_id: number | ''; bio: string; link_url: string; sort_order: number; is_public: boolean; archived: boolean };

export default function ProfileForm({ profile, categories }: { profile?: ProfileData; categories: { id: number; name: string }[] }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: profile?.name ?? '',
        position: profile?.position ?? '',
        category_id: profile?.category_id ?? '',
        bio: profile?.bio ?? '',
        link_url: profile?.link_url ?? '',
        sort_order: profile?.sort_order ?? 0,
        is_public: profile?.is_public ?? false,
        archived: profile?.archived ?? false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (profile) put(route('admin.profiles.update', profile.id));
        else post(route('admin.profiles.store'));
    };

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required maxLength={120} />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="position">Position</Label>
                <Input id="position" value={data.position} onChange={(e) => setData('position', e.target.value)} required maxLength={120} placeholder="e.g. Head, Sports" />
                <InputError message={errors.position} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="category_id">Category (optional)</Label>
                <select id="category_id" value={data.category_id} onChange={(e) => setData('category_id', e.target.value === '' ? '' : Number(e.target.value))} className="border-input bg-background h-10 rounded-md border px-3 text-sm">
                    <option value="">None</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.category_id} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="bio">Short biography (public)</Label>
                <textarea id="bio" value={data.bio} onChange={(e) => setData('bio', e.target.value)} rows={4} maxLength={2000} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.bio} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="link_url">Public link (optional, https://…)</Label>
                <Input id="link_url" type="url" value={data.link_url} onChange={(e) => setData('link_url', e.target.value)} />
                <InputError message={errors.link_url} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="sort_order">Display order (lower comes first)</Label>
                <Input id="sort_order" type="number" min={0} max={999} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} />
                <InputError message={errors.sort_order} />
            </div>
            <p className="text-muted-foreground text-xs">Profiles hold only what you type here. Do not enter private emails or phone numbers: this text is shown to the public.</p>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_public} onChange={(e) => setData('is_public', e.target.checked)} />
                Approved for the public About page
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.archived} onChange={(e) => setData('archived', e.target.checked)} />
                Archived (moves to “Former members”; hidden entirely if not approved for the public)
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    {profile ? 'Save changes' : 'Create profile'}
                </Button>
            </div>
        </form>
    );
}
