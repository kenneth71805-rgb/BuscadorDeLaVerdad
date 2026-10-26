import GalleryForm, { GalleryData } from '@/components/gallery-form';
import { PickerMedia } from '@/components/media-picker';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit({ gallery, media }: { gallery: GalleryData; media: PickerMedia[] }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Galleries', href: '/newsroom/galleries' }, { title: gallery.title, href: `/newsroom/galleries/${gallery.id}/edit` }]}>
            <Head title={`Edit ${gallery.title}`} />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit gallery</h1>
                <GalleryForm gallery={gallery} media={media} />
            </div>
        </AppLayout>
    );
}
