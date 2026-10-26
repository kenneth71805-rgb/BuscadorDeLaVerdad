import PublicLayout, { NavCategory } from '@/components/public-layout';
import InputError from '@/components/input-error';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = {
    nav: NavCategory[];
    info: Record<string, string | null>;
    types: Record<string, string>;
};

export default function Contact({ nav, info, types }: Props) {
    const { flash } = usePage<SharedData>().props;
    const { data, setData, post, processing, errors, reset } = useForm<{ name: string; email: string; type: string; message: string; consent: boolean; website: string }>({
        name: '',
        email: '',
        type: 'general',
        message: '',
        consent: false,
        website: '', // honeypot
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('site.contact.store'), { preserveScroll: true, onSuccess: () => reset() });
    };

    const field = 'border-ink-soft w-full border bg-white px-3 py-2';

    return (
        <PublicLayout nav={nav}>
            <Head title="Contact" />
            <h1 className="font-display text-forest border-ink border-b-2 pb-2 text-4xl font-semibold">Contact us</h1>

            <div className="mt-6 grid gap-10 lg:grid-cols-3">
                <section className="lg:col-span-1">
                    <h2 className="font-display text-xl">The newsroom</h2>
                    <ul className="mt-2 space-y-1 text-sm">
                        {info.contact_email && (
                            <li>
                                Email: <a className="underline" href={`mailto:${info.contact_email}`}>{info.contact_email}</a>
                            </li>
                        )}
                        {info.contact_address && <li>{info.contact_address}</li>}
                        {info.facebook_url && (
                            <li>
                                <a className="underline" href={info.facebook_url} rel="noopener noreferrer" target="_blank">Facebook</a>
                            </li>
                        )}
                        {info.instagram_url && (
                            <li>
                                <a className="underline" href={info.instagram_url} rel="noopener noreferrer" target="_blank">Instagram</a>
                            </li>
                        )}
                        {!info.contact_email && !info.contact_address && !info.facebook_url && !info.instagram_url && <li className="text-ink-soft">Use the form to reach the editors.</li>}
                    </ul>
                </section>

                <form onSubmit={submit} className="flex flex-col gap-4 lg:col-span-2" noValidate>
                    {flash?.success && (
                        <p role="status" className="border border-green-700/30 bg-green-50 px-3 py-2 text-green-900">
                            {flash.success}
                        </p>
                    )}

                    <div>
                        <label htmlFor="type" className="mb-1 block text-sm font-medium">What is this about?</label>
                        <select id="type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={field}>
                            {Object.entries(types).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                        <InputError message={errors.type} />
                    </div>
                    <div>
                        <label htmlFor="name" className="mb-1 block text-sm font-medium">Your name</label>
                        <input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={field} maxLength={100} required />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <label htmlFor="email" className="mb-1 block text-sm font-medium">Your email (so we can reply)</label>
                        <input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} maxLength={150} required />
                        <InputError message={errors.email} />
                    </div>
                    <div>
                        <label htmlFor="message" className="mb-1 block text-sm font-medium">Message</label>
                        <textarea id="message" value={data.message} onChange={(e) => setData('message', e.target.value)} rows={7} className={field} maxLength={5000} required />
                        <InputError message={errors.message} />
                    </div>

                    {/* Honeypot: hidden from people and assistive tech; bots tend to fill it. */}
                    <div aria-hidden="true" className="absolute -left-[9999px]">
                        <label htmlFor="website">Leave this field empty</label>
                        <input id="website" tabIndex={-1} autoComplete="off" value={data.website} onChange={(e) => setData('website', e.target.value)} />
                    </div>

                    <div className="text-ink-soft border-paper-edge border p-3 text-sm">
                        <p className="font-medium text-ink">Privacy notice</p>
                        <p className="mt-1">
                            We use your name, email and message only to answer you and, if you ask for one, to review a correction. Only authorized publication staff can read it. Under the Data Privacy Act of 2012 you may ask to see or delete your message. Resolved messages are deleted automatically one week after they are closed.
                        </p>
                    </div>
                    <label className="flex items-start gap-2 text-sm">
                        <input type="checkbox" checked={data.consent} onChange={(e) => setData('consent', e.target.checked)} className="mt-1" />
                        I have read the privacy notice and agree that my message may be processed as described.
                    </label>
                    <InputError message={errors.consent} />

                    <div>
                        <button type="submit" disabled={processing} className="bg-forest px-5 py-2 text-white disabled:opacity-60">
                            Send message
                        </button>
                    </div>
                </form>
            </div>
        </PublicLayout>
    );
}
