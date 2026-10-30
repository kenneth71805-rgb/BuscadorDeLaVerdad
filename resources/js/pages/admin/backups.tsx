import Flash from '@/components/flash';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatWhen } from '@/lib/status';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Props = { backups: { name: string; mb: number; at: string }[]; keep: number };

export default function Backups({ backups, keep }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const [busy, setBusy] = useState(false);

    return (
        <AppLayout breadcrumbs={[{ title: 'Backups', href: '/admin/backups' }]}>
            <Head title="Backups" />
            <div className="flex max-w-3xl flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Backups</h1>
                <Flash />
                <p className="text-muted-foreground text-sm">
                    Each backup is one archive with the whole database and every uploaded photo and PDF. A backup runs automatically every night at 2:00 AM (the scheduler must be running), and the newest {keep} are kept.
                </p>
                <p className="rounded-md border border-amber-500/40 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    Backups are stored on this same server. If the server is lost, so are they. Copy the archives to another place regularly (see the Backup and Recovery guide). Archives contain private data, so they cannot be downloaded from the website.
                </p>
                <div>
                    <Button
                        disabled={busy}
                        onClick={() => {
                            setBusy(true);
                            router.post(route('admin.backups.store'), {}, { preserveScroll: true, onFinish: () => setBusy(false) });
                        }}
                    >
                        {busy ? 'Backing up…' : 'Back up now'}
                    </Button>
                    <InputError message={errors.backup} className="mt-2" />
                </div>
                <ul className="divide-y text-sm">
                    {backups.map((b) => (
                        <li key={b.name} className="flex flex-wrap justify-between gap-2 py-2">
                            <span className="font-mono text-xs">{b.name}</span>
                            <span className="text-muted-foreground">
                                {b.mb} MB · {formatWhen(b.at)}
                            </span>
                        </li>
                    ))}
                    {backups.length === 0 && <li className="text-muted-foreground py-4">No backups yet.</li>}
                </ul>
            </div>
        </AppLayout>
    );
}
