import InputError from '@/components/input-error';
import MediaPicker, { PickerMedia } from '@/components/media-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type IssueData = { id: number; title: string; issue_date: string; description: string | null; cover_media_id: number | null; is_public: boolean; has_pdf: boolean };

type FormData = { title: string; issue_date: string; description: string; cover_media_id: number | null; is_public: boolean; pdf: File | null; _method: string };

export default function IssueForm({ issue, media }: { issue?: IssueData; media: PickerMedia[] }) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        title: issue?.title ?? '',
        issue_date: issue?.issue_date ?? '',
        description: issue?.description ?? '',
        cover_media_id: issue?.cover_media_id ?? null,
        is_public: issue?.is_public ?? false,
        pdf: null,
        _method: issue ? 'put' : 'post',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // Files need multipart; Laravel reads the _method field to treat this POST as a PUT.
        if (issue) post(route('newsroom.issues.update', issue.id), { forceFormData: true });
        else post(route('newsroom.issues.store'), { forceFormData: true });
    };

    return (
        <form onSubmit={submit} className="flex max-w-3xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="title">Issue title</Label>
                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={150} />
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="issue_date">Publication date</Label>
                <Input id="issue_date" type="date" value={data.issue_date} onChange={(e) => setData('issue_date', e.target.value)} required />
                <InputError message={errors.issue_date} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} maxLength={2000} className="border-input bg-background rounded-md border p-2 text-sm" />
                <InputError message={errors.description} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="pdf">PDF edition (up to 40 MB){issue?.has_pdf ? ' — leave empty to keep the current file' : ''}</Label>
                <input id="pdf" type="file" accept="application/pdf" onChange={(e) => setData('pdf', e.target.files?.[0] ?? null)} required={!issue} />
                <p className="text-muted-foreground text-xs">PDFs containing scripts or embedded files are refused. Export a plain PDF from your layout software.</p>
                <InputError message={errors.pdf} />
            </div>
            <fieldset className="grid gap-2">
                <legend className="mb-1 text-sm font-medium">Cover image (optional; must be approved for public release to show)</legend>
                <MediaPicker single media={media} selected={data.cover_media_id ? [data.cover_media_id] : []} onChange={(ids) => setData('cover_media_id', ids[0] ?? null)} />
                <InputError message={errors.cover_media_id} />
            </fieldset>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_public} onChange={(e) => setData('is_public', e.target.checked)} />
                Public (visitors can see and download this issue)
            </label>
            <div>
                <Button type="submit" disabled={processing}>
                    Save issue
                </Button>
            </div>
        </form>
    );
}
