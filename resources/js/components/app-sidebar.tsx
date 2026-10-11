import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    FileText,
    LayoutGrid,
    Receipt,
    Settings2,
    Users,
    Wrench,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { CrmPermissions, NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.crmPermissions as CrmPermissions | null;
    const prefix = slug ? `/${slug}` : '';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: slug ? `${prefix}/dashboard` : '/',
            icon: LayoutGrid,
        },
        permissions?.canViewCustomers && {
            title: 'Customers',
            href: `${prefix}/customers`,
            icon: Users,
        },
        permissions?.canViewRequests && {
            title: 'Requests',
            href: `${prefix}/requests`,
            icon: ClipboardList,
        },
        permissions?.canViewEstimates && {
            title: 'Estimates',
            href: `${prefix}/estimates`,
            icon: FileText,
        },
        permissions?.canViewWorkOrders && {
            title: 'Work orders',
            href: `${prefix}/work-orders`,
            icon: Wrench,
        },
        permissions?.canViewSchedule && {
            title: 'Schedule',
            href: `${prefix}/schedule`,
            icon: CalendarDays,
        },
        permissions?.canViewWorkOrders && {
            title: 'My jobs',
            href: `${prefix}/my-jobs`,
            icon: Wrench,
        },
        permissions?.canViewInvoices && {
            title: 'Invoices',
            href: `${prefix}/invoices`,
            icon: Receipt,
        },
        permissions?.canViewReports && {
            title: 'Reports',
            href: `${prefix}/reports`,
            icon: FileText,
        },
        permissions?.canManageSettings && {
            title: 'Org settings',
            href: `${prefix}/organization-settings`,
            icon: Settings2,
        },
    ].filter(Boolean) as NavItem[];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={slug ? `${prefix}/dashboard` : '/'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
