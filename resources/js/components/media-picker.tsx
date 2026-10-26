import { Button } from '@/components/ui/button';

export type PickerMedia = { id: number; url: string; alt: string; is_public: boolean };

type Props = {
    media: PickerMedia[];
    selected: number[];
    onChange: (ids: number[]) => void;
    single?: boolean;
};

/** Choose one or more photos from the library. For several, the order chosen is the display order. */
export default function MediaPicker({ media, selected, onChange, single }: Props) {
    const byId = new Map(media.map((m) => [m.id, m]));

    const toggle = (id: number) => {
        if (single) return onChange(selected[0] === id ? [] : [id]);
        onChange(selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id]);
    };

    const move = (i: number, delta: number) => {
        const next = [...selected];
        const j = i + delta;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]];
        onChange(next);
    };

    if (media.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                The media library is empty. <a className="underline" href="/media">Upload photos first.</a>
            </p>
        );
    }

    return (
        <div className="grid gap-3">
            <ul className="flex flex-wrap gap-2">
                {media.map((m) => {
                    const on = selected.includes(m.id);
                    return (
                        <li key={m.id}>
                            <button
                                type="button"
                                onClick={() => toggle(m.id)}
                                aria-pressed={on}
                                className={`flex w-28 flex-col items-center gap-1 rounded border p-2 text-xs ${on ? 'border-primary ring-primary ring-2' : ''}`}
                            >
                                <img src={m.url} alt={m.alt} className="aspect-[4/3] w-full rounded object-cover" loading="lazy" />
                                <span>{on ? (single ? 'Selected' : `#${selected.indexOf(m.id) + 1}`) : m.is_public ? 'Approved' : 'Not approved'}</span>
                            </button>
                        </li>
                    );
                })}
            </ul>

            {!single && selected.length > 1 && (
                <ol className="grid gap-1 text-sm">
                    {selected.map((id, i) => (
                        <li key={id} className="flex items-center gap-2">
                            <span className="w-6">{i + 1}.</span>
                            <span className="flex-1 truncate">{byId.get(id)?.alt ?? `Photo ${id}`}</span>
                            <Button type="button" size="sm" variant="outline" aria-label="Move up" onClick={() => move(i, -1)} disabled={i === 0}>
                                ↑
                            </Button>
                            <Button type="button" size="sm" variant="outline" aria-label="Move down" onClick={() => move(i, 1)} disabled={i === selected.length - 1}>
                                ↓
                            </Button>
                        </li>
                    ))}
                </ol>
            )}
            <p className="text-muted-foreground text-xs">Photos that are not approved for public release are saved but will not appear on the public site until an editor approves them.</p>
        </div>
    );
}
