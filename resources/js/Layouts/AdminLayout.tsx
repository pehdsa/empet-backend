import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Dog,
    Tags,
    PawPrint,
    FileSearch,
    MapPin,
    GitCompare,
    LogOut,
    PanelLeftIcon,
} from 'lucide-react';
import { Toaster, toast } from 'sonner';
import { useEffect, type ReactNode } from 'react';

import type { SharedProps } from '@/Types/inertia';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: LayoutDashboard, routeName: 'admin.dashboard' },
    { name: 'Raças', href: '/admin/breeds', icon: Dog, routeName: 'admin.breeds' },
    { name: 'Características', href: '/admin/characteristics', icon: Tags, routeName: 'admin.characteristics' },
    { name: 'Pets', href: '/admin/pets', icon: PawPrint, routeName: 'admin.pets' },
    { name: 'Reports', href: '/admin/reports', icon: FileSearch, routeName: 'admin.reports' },
    { name: 'Avistamentos', href: '/admin/sightings', icon: MapPin, routeName: 'admin.sightings' },
    { name: 'Matches', href: '/admin/matches', icon: GitCompare, routeName: 'admin.matches' },
];

const envBadgeColors: Record<string, string> = {
    production: 'bg-gray-200 text-gray-700',
    staging: 'bg-yellow-100 text-yellow-800',
    local: 'bg-blue-100 text-blue-800',
};

interface AdminLayoutProps {
    children: ReactNode;
    breadcrumbs?: { label: string; href?: string }[];
}

export default function AdminLayout({ children, breadcrumbs }: AdminLayoutProps) {
    const { auth, flash, app, url } = usePage<SharedProps & { url: string }>().props;

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
        if (flash.warning) toast.warning(flash.warning);
    }, [flash.success, flash.error, flash.warning]);

    const initials = auth.user?.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <SidebarProvider>
            <Sidebar>
                <SidebarHeader className="p-4">
                    <div className="flex items-center gap-2">
                        <span className="text-lg font-bold text-primary">
                            {app.name}
                        </span>
                        <Badge
                            variant="secondary"
                            className={`text-[10px] uppercase ${envBadgeColors[app.environment] ?? envBadgeColors.local}`}
                        >
                            {app.environment === 'production' ? 'PROD' : app.environment.toUpperCase()}
                        </Badge>
                    </div>
                </SidebarHeader>

                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Menu</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {navigation.map((item) => {
                                    const isActive = url?.startsWith(item.href);
                                    return (
                                        <SidebarMenuItem key={item.name}>
                                            <SidebarMenuButton asChild isActive={isActive}>
                                                <Link href={item.href}>
                                                    <item.icon className="size-4" />
                                                    <span>{item.name}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                </SidebarContent>

                <SidebarFooter className="p-4">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button className="flex w-full items-center gap-3 rounded-lg p-2 text-sm hover:bg-accent">
                                <Avatar className="size-8">
                                    <AvatarFallback className="bg-primary text-primary-foreground text-xs">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1 text-left">
                                    <p className="truncate font-medium">{auth.user?.name}</p>
                                    <p className="truncate text-xs text-muted-foreground">{auth.user?.email}</p>
                                </div>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-56">
                            <DropdownMenuItem asChild>
                                <Link href="/admin/logout" method="post" as="button" className="w-full">
                                    <LogOut className="mr-2 size-4" />
                                    Sair
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarFooter>
            </Sidebar>

            <SidebarInset>
                <header className="flex h-14 items-center gap-2 border-b px-4">
                    <SidebarTrigger>
                        <PanelLeftIcon className="size-4" />
                    </SidebarTrigger>
                    <Separator orientation="vertical" className="h-4" />
                    {breadcrumbs && breadcrumbs.length > 0 && (
                        <Breadcrumb>
                            <BreadcrumbList>
                                {breadcrumbs.map((crumb, i) => (
                                    <BreadcrumbItem key={crumb.label}>
                                        {i > 0 && <BreadcrumbSeparator />}
                                        {crumb.href ? (
                                            <Link href={crumb.href} className="text-muted-foreground hover:text-foreground">
                                                {crumb.label}
                                            </Link>
                                        ) : (
                                            <BreadcrumbPage>{crumb.label}</BreadcrumbPage>
                                        )}
                                    </BreadcrumbItem>
                                ))}
                            </BreadcrumbList>
                        </Breadcrumb>
                    )}
                </header>

                <main className="flex-1 p-6">
                    {children}
                </main>
            </SidebarInset>

            <Toaster richColors position="top-right" />
        </SidebarProvider>
    );
}
