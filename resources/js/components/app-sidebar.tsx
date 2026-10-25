import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Users } from 'lucide-react';
import AppLogo from './app-logo';

const navByRole: Record<string, NavItem[]> = {
    admin: [
        { title: 'Dashboard', url: '/admin', icon: LayoutGrid },
        { title: 'Staff accounts', url: '/admin/staff', icon: Users },
    ],
    editor_in_chief: [{ title: 'Editorial desk', url: '/editor', icon: LayoutGrid }],
    category_head: [{ title: 'My category', url: '/category', icon: LayoutGrid }],
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
