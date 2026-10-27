import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type FaqData = { id: number; question: string; answer: string; keywords: string | null; sort_order: number; is_active: boolean };
type FormData = { question: string; answer: string; keywords: string; sort_order: number; is_active: boolean };

export default function FaqForm({ entry }: { entry?: FaqData }) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        question: entry?.question ?? '',
        answer: entry?.answer ?? '',
        keywords: entry?.keywords ?? '',
        sort_order: entry?.sort_order ?? 0,
        is_active: entry?.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (entry) put(route('newsroom.faq.update', entry.id));
        else post(route('newsroom.faq.store'));
    };

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="question">Question</Label>
                <Input id="question" value={data.question} onChange={(e) => setData('question', e.target.value)} required maxLength={255} />
                <InputError message={errors.question} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="answer">Answer (shown exactly as written)</Label>
                <textarea id="answer" value={data.answer} onChange={(e) => setData('answer', e.target.value)} rows={5} required maxLength={2000} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.answer} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="keywords">Trigger words (separated by commas)</Label>
                <Input id="keywords" value={data.keywords} onChange={(e) => setData('keywords', e.target.value)} maxLength={500} placeholder="correction, error, mistake, wrong" />
                <p className="text-muted-foreground text-xs">The helper picks this answer when a visitor&apos;s question contains these words. Only write answers you are sure are correct and current.</p>
                <InputError message={errors.keywords} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="sort_order">Order (lower comes first when two answers match equally)</Label>
                <Input id="sort_order" type="number" min={0} max={999} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                Active
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    Save
                </Button>
            </div>
        </form>
    );
}
