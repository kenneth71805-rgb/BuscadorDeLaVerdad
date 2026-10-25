import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    position: string | null;
    is_active: boolean;
    category_ids: number[];
    can_override: boolean;
    can_publish: boolean;
};

type Props = {
    member?: StaffMember;
    roles: { value: string; label: string }[];
    categories: { id: number; name: string }[];
};

type FormData = {
    name: string;
    email: string;
    role: string;
    position: string;
    is_active: boolean;
    can_override: boolean;
    can_publish: boolean;
    category_ids: number[];
    password: string;
};

export default function StaffForm({ member, roles, categories }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: member?.name ?? '',
        email: member?.email ?? '',
        role: member?.role ?? 'category_head',
        position: member?.position ?? '',
        is_active: member?.is_active ?? true,
        can_override: member?.can_override ?? false,
        can_publish: member?.can_publish ?? false,
        category_ids: member?.category_ids ?? [],
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (member) {
            put(route('admin.staff.update', member.id));
        } else {
            post(route('admin.staff.store'));
        }
    };

    const toggleCategory = (id: number) =>
        setData('category_ids', data.category_ids.includes(id) ? data.category_ids.filter((c) => c !== id) : [...data.category_ids, id]);

    return (
        <form onSubmit={submit} className="flex max-w-xl flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="name">Full name</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                <InputError message={errors.email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <select
                    id="role"
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                    className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                >
                    {roles.map((r) => (
                        <option key={r.value} value={r.value}>
                            {r.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.role} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="position">Position title (optional)</Label>
                <Input id="position" value={data.position} onChange={(e) => setData('position', e.target.value)} />
                <InputError message={errors.position} />
            </div>

            {data.role === 'category_head' && (
                <fieldset className="grid gap-2">
                    <legend className="mb-1 text-sm font-medium">Assigned categories</legend>
                    {categories.map((c) => (
                        <label key={c.id} className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.category_ids.includes(c.id)} onChange={() => toggleCategory(c.id)} />
                            {c.name}
                        </label>
                    ))}
                    <InputError message={errors.category_ids} />
                    <label className="mt-2 flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data.can_publish} onChange={(e) => setData('can_publish', e.target.checked)} />
                        May publish approved articles directly
                    </label>
                </fieldset>
            )}

            {data.role === 'admin' && (
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={data.can_override} onChange={(e) => setData('can_override', e.target.checked)} />
                    May override editorial decisions (always requires a reason and is logged)
                </label>
            )}

            <div className="grid gap-2">
                <Label htmlFor="password">{member ? 'New password (leave blank to keep current)' : 'Temporary password'}</Label>
                <Input
                    id="password"
                    type="password"
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required={!member}
                />
                <p className="text-muted-foreground text-xs">At least 12 characters with letters and numbers. Share it in person, not by chat.</p>
                <InputError message={errors.password} />
            </div>

            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                Account is active
            </label>

            <div>
                <Button type="submit" disabled={processing}>
                    {member ? 'Save changes' : 'Create account'}
                </Button>
            </div>
        </form>
    );
}
