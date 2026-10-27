import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, Bell, BookOpen, CalendarDays, CalendarPlus, ClipboardList, GalleryHorizontal, Image, Inbox, LayoutGrid, Newspaper, Settings, MessageCircleQuestion, ScrollText, Tags, UserSquare, Users } from 'lucide-react';
import AppLogo from './app-logo';

const navByRole: Record<string, NavItem[]> = {
    admin: [
        { title: 'Dashboard', url: '/admin', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Reports', url: '/reports', icon: BarChart3 },
        { title: 'Calendar', url: '/calendar', icon: CalendarDays },
        { title: 'Assignments', url: '/assignments', icon: ClipboardList },
        { title: 'Notifications', url: '/notifications', icon: Bell },
        { title: 'Media library', url: '/media', icon: Image },
        { title: 'Staff accounts', url: '/admin/staff', icon: Users },
        { title: 'Galleries', url: '/newsroom/galleries', icon: GalleryHorizontal },
        { title: 'Issues (PDF)', url: '/newsroom/issues', icon: BookOpen },
        { title: 'Events', url: '/newsroom/events', icon: CalendarPlus },
        { title: 'Categories', url: '/admin/categories', icon: Tags },
        { title: 'Editorial board', url: '/admin/profiles', icon: UserSquare },
        { title: 'Helper FAQ', url: '/newsroom/faq', icon: MessageCircleQuestion },
        { title: 'Inbox', url: '/inbox', icon: Inbox },
        { title: 'Audit log', url: '/admin/audit', icon: ScrollText },
        { title: 'Site settings', url: '/admin/settings', icon: Settings },
    ],
    editor_in_chief: [
        { title: 'Editorial desk', url: '/editor', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Reports', url: '/reports', icon: BarChart3 },
        { title: 'Calendar', url: '/calendar', icon: CalendarDays },
        { title: 'Assignments', url: '/assignments', icon: ClipboardList },
        { title: 'Notifications', url: '/notifications', icon: Bell },
        { title: 'Media library', url: '/media', icon: Image },
        { title: 'Galleries', url: '/newsroom/galleries', icon: GalleryHorizontal },
        { title: 'Issues (PDF)', url: '/newsroom/issues', icon: BookOpen },
        { title: 'Events', url: '/newsroom/events', icon: CalendarPlus },
        { title: 'Helper FAQ', url: '/newsroom/faq', icon: MessageCircleQuestion },
        { title: 'Inbox', url: '/inbox', icon: Inbox },
    ],
    category_head: [
        { title: 'My category', url: '/category', icon: LayoutGrid },
        { title: 'Articles', url: '/articles', icon: Newspaper },
        { title: 'Reports', url: '/reports', icon: BarChart3 },
        { title: 'Calendar', url: '/calendar', icon: CalendarDays },
        { title: 'Assignments', url: '/assignments', icon: ClipboardList },
        { title: 'Notifications', url: '/notifications', icon: Bell },
        { title: 'Media library', url: '/media', icon: Image },
    ],
};

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const { unread } = usePage<SharedData>().props;
    const base = (auth.user.role && navByRole[auth.user.role]) || [];
    const items = base.map((i) => (i.url === '/notifications' && unread ? { ...i, title: `Notifications (${unread})` } : i));
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
