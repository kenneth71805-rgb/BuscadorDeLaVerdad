import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = { article: { id: number; title: string; subtitle: string | null; body: string } };

export default function Correction({ article }: Props) {
    const errors2 = usePage().props.errors as Record<string, string>;
    const { data, setData, post, processing, errors } = useForm({
        title: article.title,
        subtitle: article.subtitle ?? '',
        body: article.body,
        reason: '',
        public_note: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('corrections.store', article.id));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Articles', href: '/articles' }, { title: article.title, href: `/articles/${article.id}` }, { title: 'Correction', href: `/articles/${article.id}/correction` }]}>
            <Head title="Propose a correction" />
            <form onSubmit={submit} className="flex max-w-3xl flex-col gap-5 p-4">
                <h1 className="text-2xl font-semibold">Propose a correction</h1>
                <p className="text-muted-foreground text-sm">
                    The published article stays exactly as it is while your correction is reviewed. When the Editor-in-Chief approves it, the new text replaces the live text and the old text is kept in the record.
                </p>
                <InputError message={errors2.correction} />

                <div className="grid gap-2">
                    <Label htmlFor="title">Headline</Label>
                    <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={200} />
                    <InputError message={errors.title} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="subtitle">Subtitle</Label>
                    <Input id="subtitle" value={data.subtitle} onChange={(e) => setData('subtitle', e.target.value)} maxLength={300} />
                    <InputError message={errors.subtitle} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="body">Article text</Label>
                    <textarea id="body" value={data.body} onChange={(e) => setData('body', e.target.value)} rows={16} required className="border-input bg-background rounded-md border p-3 font-serif text-base leading-relaxed" />
                    <InputError message={errors.body} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="reason">What is wrong, and why is it being changed? (internal, required)</Label>
                    <textarea id="reason" value={data.reason} onChange={(e) => setData('reason', e.target.value)} rows={3} required maxLength={1000} className="border-input bg-background rounded-md border p-2 text-sm" />
                    <InputError message={errors.reason} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="public_note">Public correction notice (optional)</Label>
                    <Input id="public_note" value={data.public_note} onChange={(e) => setData('public_note', e.target.value)} maxLength={500} placeholder="e.g. An earlier version gave the wrong date for the event. It is now corrected." />
                    <p className="text-muted-foreground text-xs">Shown to readers under the article. Leave empty for a small fix such as a typo. The article will still show “Updated” with the date.</p>
                    <InputError message={errors.public_note} />
                </div>
                <div>
                    <Button type="submit" disabled={processing}>
                        Submit correction
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
