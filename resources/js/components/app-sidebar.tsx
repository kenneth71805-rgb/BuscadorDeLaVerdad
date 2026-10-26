import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, GalleryHorizontal, Image, Inbox, LayoutGrid, Newspaper, Settings, Tags, UserSquare, Users } from 'lucide-react';
import AppLogo from './app-logo';

const navByRole: Record<string, NavItem[]> = {
    admin: [
        { title: 'Dashboard', url: '/admin', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Media library', url: '/media', icon: Image },
        { title: 'Staff accounts', url: '/admin/staff', icon: Users },
        { title: 'Galleries', url: '/newsroom/galleries', icon: GalleryHorizontal },
        { title: 'Issues (PDF)', url: '/newsroom/issues', icon: BookOpen },
        { title: 'Categories', url: '/admin/categories', icon: Tags },
        { title: 'Editorial board', url: '/admin/profiles', icon: UserSquare },
        { title: 'Inbox', url: '/inbox', icon: Inbox },
        { title: 'Site settings', url: '/admin/settings', icon: Settings },
    ],
    editor_in_chief: [
        { title: 'Editorial desk', url: '/editor', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Media library', url: '/media', icon: Image },
        { title: 'Galleries', url: '/newsroom/galleries', icon: GalleryHorizontal },
        { title: 'Issues (PDF)', url: '/newsroom/issues', icon: BookOpen },
        { title: 'Inbox', url: '/inbox', icon: Inbox },
    ],
    category_head: [
        { title: 'My category', url: '/category', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Media library', url: '/media', icon: Image },
    ],
};

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const items = (auth.user.role && navByRole[auth.user.role]) || [];
    const home = items[0]?.url ?? '/dashboard';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={home} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
