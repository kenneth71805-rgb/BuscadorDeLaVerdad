import Flash from '@/components/flash';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Values = Record<'contact_email' | 'contact_address' | 'facebook_url' | 'instagram_url' | 'about_history' | 'about_mission' | 'about_vision' | 'about_principles', string> & { assistant_enabled: boolean };

const textareas: [Exclude<keyof Values, 'assistant_enabled'>, string][] = [
    ['about_history', 'Background and history'],
    ['about_mission', 'Mission'],
    ['about_vision', 'Vision'],
    ['about_principles', 'Editorial principles'],
];

export default function Settings({ settings }: { settings: Record<string, string | null> }) {
    const { data, setData, put, processing, errors, transform } = useForm<Values>({
        contact_email: settings.contact_email ?? '',
        contact_address: settings.contact_address ?? '',
        facebook_url: settings.facebook_url ?? '',
        instagram_url: settings.instagram_url ?? '',
        about_history: settings.about_history ?? '',
        about_mission: settings.about_mission ?? '',
        about_vision: settings.about_vision ?? '',
        about_principles: settings.about_principles ?? '',
        assistant_enabled: settings.assistant_enabled !== '0',
    });
    transform((d) => ({ ...d, assistant_enabled: d.assistant_enabled ? '1' : '0' }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.settings.update'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Site settings', href: '/admin/settings' }]}>
            <Head title="Site settings" />
            <form onSubmit={submit} className="flex max-w-2xl flex-col gap-5 p-4">
                <h1 className="text-2xl font-semibold">Site settings</h1>
                <Flash />
                <p className="text-muted-foreground text-sm">Everything here is shown publicly on the Contact and About pages. Empty fields are simply left out.</p>

                <div className="grid gap-2">
                    <Label htmlFor="contact_email">Official contact email</Label>
                    <Input id="contact_email" type="email" value={data.contact_email} onChange={(e) => setData('contact_email', e.target.value)} />
                    <InputError message={errors.contact_email} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="contact_address">Office / address</Label>
                    <Input id="contact_address" value={data.contact_address} onChange={(e) => setData('contact_address', e.target.value)} />
                    <InputError message={errors.contact_address} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="facebook_url">Facebook page (https://…)</Label>
                    <Input id="facebook_url" type="url" value={data.facebook_url} onChange={(e) => setData('facebook_url', e.target.value)} />
                    <InputError message={errors.facebook_url} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="instagram_url">Instagram (https://…)</Label>
                    <Input id="instagram_url" type="url" value={data.instagram_url} onChange={(e) => setData('instagram_url', e.target.value)} />
                    <InputError message={errors.instagram_url} />
                </div>

                <label className="flex items-start gap-2 text-sm">
                    <input type="checkbox" checked={data.assistant_enabled} onChange={(e) => setData('assistant_enabled', e.target.checked)} className="mt-1" />
                    <span>
                        Show the automated website helper on public pages.
                        <span className="text-muted-foreground block text-xs">It is a rule-based FAQ helper (not AI). Manage its answers under Helper FAQ.</span>
                    </span>
                </label>

                {textareas.map(([key, label]) => (
                    <div key={key} className="grid gap-2">
                        <Label htmlFor={key}>{label}</Label>
                        <textarea id={key} value={data[key]} onChange={(e) => setData(key, e.target.value)} rows={5} className="border-input bg-background rounded-md border p-2 text-sm" />
                        <InputError message={errors[key]} />
                    </div>
                ))}

                <div>
                    <Button type="submit" disabled={processing}>
                        Save settings
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
