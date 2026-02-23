import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Users, FileText } from 'lucide-react';
import AppLogo from './app-logo';

// Define a type that includes an optional permission string
interface ProtectedNavItem extends NavItem {
    permission?: string;
}

const mainNavItems: ProtectedNavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users,
        permission: 'view users', // Matches your DB screenshot
    },
        {
        title: 'Manage accounts',
        href: '/manage-accounts',
        icon: Users,
        permission: 'manage-accounts', // Matches your DB screenshot
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: FileText,
        permission: 'view reports', // Matches your DB screenshot
    },
];

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    // Get the shared auth data from Inertia
    const { auth } = usePage().props as any;
    const permissions = auth.user?.permissions || [];

    // Filter items based on user permissions
    const filteredNavItems = mainNavItems.filter((item) => {
        if (!item.permission) return true;
        return permissions.includes(item.permission);
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}