import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type ArticleDraft = { id: number; title: string; subtitle: string | null; body: string; category_id: number; lead_media_id: number | null };
export type MediaOption = { id: number; url: string; alt: string; is_public: boolean };

type FormData = { title: string; subtitle: string; body: string; category_id: number | ''; lead_media_id: number | null };

export default function ArticleForm({ article, categories, media }: { article?: ArticleDraft; categories: { id: number; name: string }[]; media: MediaOption[] }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        title: article?.title ?? '',
        subtitle: article?.subtitle ?? '',
        body: article?.body ?? '',
        category_id: article?.category_id ?? (categories.length === 1 ? categories[0].id : ''),
        lead_media_id: article?.lead_media_id ?? null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (article) {
            put(route('articles.update', article.id));
        } else {
            post(route('articles.store'));
        }
    };

    if (categories.length === 0) {
        return <p className="text-sm">You have no category to write in yet. Please ask an Admin to assign one.</p>;
    }

    return (
        <form onSubmit={submit} className="flex max-w-3xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="category_id">Category</Label>
                <select
                    id="category_id"
                    value={data.category_id}
                    onChange={(e) => setData('category_id', e.target.value === '' ? '' : Number(e.target.value))}
                    className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                    required
                >
                    <option value="">Choose a category…</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.category_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="title">Headline</Label>
                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} maxLength={200} required />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="subtitle">Subtitle (optional)</Label>
                <Input id="subtitle" value={data.subtitle} onChange={(e) => setData('subtitle', e.target.value)} maxLength={300} />
                <InputError message={errors.subtitle} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="body">Article</Label>
                <textarea
                    id="body"
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    rows={18}
                    required
                    className="border-input bg-background rounded-md border p-3 font-serif text-base leading-relaxed"
                />
                <p className="text-muted-foreground text-xs">Separate paragraphs with a blank line. Images and rich formatting are coming in a later stage.</p>
                <InputError message={errors.body} />
            </div>

            <fieldset className="grid gap-2">
                <legend className="mb-1 text-sm font-medium">Lead photo (optional)</legend>
                <div className="flex flex-wrap gap-3">
                    <label className="flex w-28 flex-col items-center gap-1 rounded border p-2 text-xs">
                        <input type="radio" name="lead" checked={data.lead_media_id === null} onChange={() => setData('lead_media_id', null)} />
                        No photo
                    </label>
                    {media.map((m) => (
                        <label key={m.id} className="flex w-28 flex-col items-center gap-1 rounded border p-2 text-xs">
                            <img src={m.url} alt={m.alt} className="aspect-[4/3] w-full rounded object-cover" />
                            <input type="radio" name="lead" checked={data.lead_media_id === m.id} onChange={() => setData('lead_media_id', m.id)} />
                            {m.is_public ? 'Approved' : 'Awaiting approval'}
                        </label>
                    ))}
                </div>
                <p className="text-muted-foreground text-xs">
                    Upload photos in the <a href="/media" className="underline">Media library</a> first. An editor must approve a photo before the article can be published with it.
                </p>
                <InputError message={errors.lead_media_id} />
            </fieldset>

            <div>
                <Button type="submit" disabled={processing}>
                    {article ? 'Save changes' : 'Save draft'}
                </Button>
            </div>
        </form>
    );
}
