import { FormEventHandler, useEffect, useRef, useState } from 'react';

type Link = { label: string; url: string };
type Message = { from: 'you' | 'helper'; text: string; links?: Link[]; unanswered?: string };

const xsrf = () => decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');

async function post(url: string, body: object) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
    return { status: res.status, data: await res.json().catch(() => ({})) };
}

export default function AssistantWidget() {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<Message[]>([]);
    const [text, setText] = useState('');
    const [busy, setBusy] = useState(false);
    const [escalating, setEscalating] = useState<string | null>(null);
    const [form, setForm] = useState({ name: '', email: '', consent: false });
    const [sent, setSent] = useState(false);
    const [error, setError] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (open) inputRef.current?.focus();
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open]);

    const ask: FormEventHandler = async (e) => {
        e.preventDefault();
        const question = text.trim();
        if (!question || busy) return;
        setText('');
        setError('');
        setMessages((m) => [...m, { from: 'you', text: question }]);
        setBusy(true);
        const { status, data } = await post('/assistant', { question });
        setBusy(false);

        if (status === 429) return setError('Too many questions. Please try again in a minute.');
        if (status !== 200) return setError('Sorry, the helper is not available right now.');
        setMessages((m) => [...m, data.answer ? { from: 'helper', text: data.answer, links: data.links } : { from: 'helper', text: 'I do not have an answer for that.', unanswered: question }]);
    };

    const escalate: FormEventHandler = async (e) => {
        e.preventDefault();
        setError('');
        const { status, data } = await post('/assistant/escalate', { ...form, message: escalating });
        if (status === 200) {
            setSent(true);
            setEscalating(null);
        } else if (status === 429) setError('Too many messages. Please try again later.');
        else setError(Object.values((data.errors ?? {}) as Record<string, string[]>).flat()[0] ?? 'Could not send your message.');
    };

    return (
        <div className="fixed right-4 bottom-4 z-40 text-sm">
            {!open && (
                <button type="button" onClick={() => setOpen(true)} className="bg-forest rounded-full px-4 py-2 text-white shadow-lg" aria-haspopup="dialog">
                    Ask the helper
                </button>
            )}
            {open && (
                <section role="dialog" aria-label="Website helper" className="bg-paper border-ink-soft flex max-h-[80vh] w-[22rem] max-w-[calc(100vw-2rem)] flex-col border shadow-xl">
                    <header className="bg-forest flex items-center justify-between px-3 py-2 text-white">
                        <h2 className="font-semibold">Website helper</h2>
                        <button type="button" onClick={() => setOpen(false)} aria-label="Close helper">
                            ✕
                        </button>
                    </header>
                    <p className="text-ink-soft border-paper-edge border-b px-3 py-2 text-xs">
                        This is an automated FAQ helper, not a person and not artificial intelligence. It answers only from this website and the editors&apos; FAQ. Please do not type personal details.
                    </p>

                    <div className="flex-1 space-y-3 overflow-y-auto px-3 py-3" aria-live="polite">
                        {messages.length === 0 && <p className="text-ink-soft">Try: &ldquo;What sections do you have?&rdquo;, &ldquo;Any upcoming events?&rdquo; or &ldquo;How do I contact you?&rdquo;</p>}
                        {messages.map((m, i) => (
                            <div key={i} className={m.from === 'you' ? 'text-right' : ''}>
                                <p className={`inline-block max-w-[90%] whitespace-pre-line px-3 py-2 text-left ${m.from === 'you' ? 'bg-forest text-white' : 'bg-white'}`}>{m.text}</p>
                                {m.links && m.links.length > 0 && (
                                    <ul className="mt-1 space-y-1">
                                        {m.links.map((l) => (
                                            <li key={l.url}>
                                                <a href={l.url} className="text-forest underline">
                                                    {l.label}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {m.unanswered && !sent && (
                                    <button type="button" onClick={() => setEscalating(m.unanswered!)} className="mt-1 underline">
                                        Send this question to the staff
                                    </button>
                                )}
                            </div>
                        ))}
                        {sent && <p className="bg-white px-3 py-2">Thank you. Your message was sent to the staff. They will reply by email.</p>}
                    </div>

                    {escalating && (
                        <form onSubmit={escalate} className="border-paper-edge space-y-2 border-t px-3 py-3">
                            <p className="font-medium">Send to the staff</p>
                            <p className="text-ink-soft text-xs">&ldquo;{escalating}&rdquo;</p>
                            <input aria-label="Your name" placeholder="Your name" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="border-ink-soft w-full border bg-white px-2 py-1" maxLength={100} />
                            <input aria-label="Your email" type="email" placeholder="Your email" required value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="border-ink-soft w-full border bg-white px-2 py-1" maxLength={150} />
                            <label className="flex items-start gap-2 text-xs">
                                <input type="checkbox" checked={form.consent} onChange={(e) => setForm({ ...form, consent: e.target.checked })} className="mt-0.5" />
                                I agree that the staff may use my name, email and question to reply to me. Resolved messages are deleted after one week.
                            </label>
                            <div className="flex gap-2">
                                <button type="submit" className="bg-forest px-3 py-1 text-white">
                                    Send
                                </button>
                                <button type="button" onClick={() => setEscalating(null)} className="px-3 py-1 underline">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    )}

                    {error && (
                        <p role="alert" className="px-3 pb-1 text-red-700">
                            {error}
                        </p>
                    )}

                    {!escalating && (
                        <form onSubmit={ask} className="border-paper-edge flex gap-2 border-t p-2">
                            <label htmlFor="helper-q" className="sr-only">
                                Your question
                            </label>
                            <input id="helper-q" ref={inputRef} value={text} onChange={(e) => setText(e.target.value)} maxLength={200} placeholder="Type your question" className="border-ink-soft flex-1 border bg-white px-2 py-1" />
                            <button type="submit" disabled={busy} className="bg-forest px-3 py-1 text-white disabled:opacity-60">
                                Ask
                            </button>
                        </form>
                    )}
                </section>
            )}
        </div>
    );
}
