import GalleryForm from '@/components/gallery-form';
import { PickerMedia } from '@/components/media-picker';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create({ media }: { media: PickerMedia[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Galleries', href: '/newsroom/galleries' }, { title: 'New', href: '/newsroom/galleries/create' }]}>
            <Head title="New gallery" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">New gallery</h1>
                <GalleryForm media={media} />
            </div>
        </AppLayout>
    );
}
