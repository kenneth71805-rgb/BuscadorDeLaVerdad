import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Flash() {
    const { flash } = usePage<SharedData>().props;
    if (!flash?.success) return null;

    return (
        <p role="status" className="rounded-md border border-green-600/30 bg-green-50 px-3 py-2 text-sm text-green-800">
            {flash.success}
        </p>
    );
}
