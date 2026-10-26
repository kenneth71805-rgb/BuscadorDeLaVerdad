import Flash from '@/components/flash';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

type Item = {
    id: number;
    url: string;
    alt: string;
    caption: string | null;
    credit: string | null;
    is_public: boolean;
    uploader: string;
    width: number;
    height: number;
    can_edit: boolean;
    can_delete: boolean;
    can_release: boolean;
};

type Props = { items: { data: Item[]; links: { url: string | null; label: string; active: boolean }[] } };

const pageLabel = (label: string) => label.replace('&laquo;', '«').replace('&raquo;', '»');

function Card({ item }: { item: Item }) {
    const errors = usePage().props.errors as Record<string, string>;

    const save: FormEventHandler<HTMLFormElement> = (e) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        router.put(route('media.update', item.id), { alt: String(f.get('alt')), caption: String(f.get('caption') ?? ''), credit: String(f.get('credit') ?? '') }, { preserveScroll: true });
    };

    return (
        <li className="flex flex-col gap-2 rounded-md border p-3 text-sm">
            <img src={item.url} alt={item.alt} className="aspect-[4/3] w-full rounded object-cover" loading="lazy" />
            <p className={item.is_public ? 'font-medium text-green-700' : 'font-medium text-amber-700'}>{item.is_public ? 'Approved for public release' : 'Not approved (private)'}</p>
            <p className="text-muted-foreground text-xs">
                {item.uploader} · {item.width}×{item.height}
                {item.credit ? ` · Photo: ${item.credit}` : ''}
            </p>
            {item.caption && <p className="text-xs italic">{item.caption}</p>}

            {item.can_edit && (
                <details>
                    <summary className="cursor-pointer text-xs underline">Edit details</summary>
                    <form onSubmit={save} className="mt-2 grid gap-2">
                        <Input name="alt" defaultValue={item.alt} placeholder="Description for screen readers" required maxLength={300} aria-label="Alt text" />
                        <Input name="caption" defaultValue={item.caption ?? ''} placeholder="Caption" maxLength={500} aria-label="Caption" />
                        <Input name="credit" defaultValue={item.credit ?? ''} placeholder="Photographer credit" maxLength={150} aria-label="Credit" />
                        <Button type="submit" size="sm" variant="outline">
                            Save
                        </Button>
                    </form>
                </details>
            )}

            <div className="mt-auto flex flex-wrap gap-2">
                {item.can_release && (
                    <Button size="sm" variant={item.is_public ? 'outline' : 'default'} onClick={() => router.put(route('media.release', item.id), { public: !item.is_public }, { preserveScroll: true })}>
                        {item.is_public ? 'Withdraw' : 'Approve for public'}
                    </Button>
                )}
                {item.can_delete && (
                    <Button size="sm" variant="destructive" onClick={() => confirm('Delete this photo permanently?') && router.delete(route('media.destroy', item.id), { preserveScroll: true })}>
                        Delete
                    </Button>
                )}
            </div>
            <InputError message={errors.delete} />
        </li>
    );
}

export default function Index({ items }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null; alt: string; caption: string; credit: string }>({ file: null, alt: '', caption: '', credit: '' });

    const upload: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('media.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Media library', href: '/media' }]}>
            <Head title="Media library" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold">Media library</h1>
                <Flash />

                <form onSubmit={upload} className="grid max-w-2xl gap-3 rounded-md border p-4">
                    <h2 className="font-semibold">Upload a photo</h2>
                    <div className="grid gap-1">
                        <Label htmlFor="file">Photo (JPEG, PNG or WebP, up to 8 MB)</Label>
                        <input id="file" ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData('file', e.target.files?.[0] ?? null)} required />
                        <InputError message={errors.file} />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="alt">Description for screen readers (required)</Label>
                        <Input id="alt" value={data.alt} onChange={(e) => setData('alt', e.target.value)} required maxLength={300} />
                        <InputError message={errors.alt} />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="caption">Caption</Label>
                        <Input id="caption" value={data.caption} onChange={(e) => setData('caption', e.target.value)} maxLength={500} />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="credit">Photographer credit</Label>
                        <Input id="credit" value={data.credit} onChange={(e) => setData('credit', e.target.value)} maxLength={150} />
                    </div>
                    <p className="text-muted-foreground text-xs">Uploads stay private until an editor approves them for public release. Location data and other hidden metadata are removed automatically.</p>
                    <div>
                        <Button type="submit" disabled={processing}>
                            Upload
                        </Button>
                    </div>
                </form>

                {items.data.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No photos yet.</p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {items.data.map((m) => (
                            <Card key={m.id} item={m} />
                        ))}
                    </ul>
                )}

                <nav aria-label="Pagination" className="flex flex-wrap gap-2 text-sm">
                    {items.links
                        .filter((l) => l.url)
                        .map((l) => (
                            <Link key={l.label} href={l.url!} className={`rounded border px-3 py-1 ${l.active ? 'bg-primary text-primary-foreground' : ''}`}>
                                {pageLabel(l.label)}
                            </Link>
                        ))}
                </nav>
            </div>
        </AppLayout>
    );
}
