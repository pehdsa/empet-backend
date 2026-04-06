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
    PanelLeft,
} from 'lucide-react';
import { Toaster, toast } from 'sonner';
import { useEffect, useRef, useState, type ReactNode } from 'react';

import type { SharedProps } from '@/Types/inertia';

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { name: 'Raças', href: '/admin/breeds', icon: Dog },
    { name: 'Características', href: '/admin/characteristics', icon: Tags },
    { name: 'Pets', href: '/admin/pets', icon: PawPrint },
    { name: 'Reports', href: '/admin/reports', icon: FileSearch },
    { name: 'Avistamentos', href: '/admin/sightings', icon: MapPin },
    { name: 'Matches', href: '/admin/matches', icon: GitCompare },
];

const envConfig: Record<string, { label: string; bg: string; text: string }> = {
    production: { label: 'PROD', bg: '#F3F4F6', text: '#374151' },
    staging: { label: 'STAGING', bg: '#FEF3C7', text: '#92400E' },
    local: { label: 'LOCAL', bg: '#DBEAFE', text: '#1E40AF' },
};

interface AdminLayoutProps {
    children: ReactNode;
    breadcrumbs?: { label: string; href?: string }[];
}

function UserMenu({ initials, name, email }: { initials?: string; name?: string; email?: string }) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
        }
        if (open) document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [open]);

    return (
        <div ref={ref} className="relative border-t border-[#E2E2E2] pt-3">
            <button
                onClick={() => setOpen(!open)}
                className="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 hover:bg-[#F8F8F8]"
            >
                <div className="flex size-8 items-center justify-center rounded-full bg-primary">
                    <span className="text-[11px] font-semibold text-white">{initials}</span>
                </div>
                <div className="flex flex-1 flex-col gap-px text-left">
                    <span className="truncate text-[13px] font-medium text-[#313233]">{name}</span>
                    <span className="truncate text-[11px] text-[#9B9C9D]">{email}</span>
                </div>
            </button>
            {open && (
                <div className="absolute bottom-full left-0 mb-1 w-full rounded-lg border border-[#E2E2E2] bg-white p-1 shadow-md">
                    <Link
                        href="/admin/logout"
                        method="post"
                        as="button"
                        className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-[13px] text-[#6B6C6D] hover:bg-[#F8F8F8]"
                        onClick={() => setOpen(false)}
                    >
                        <LogOut className="size-4" />
                        Sair
                    </Link>
                </div>
            )}
        </div>
    );
}

export default function AdminLayout({ children, breadcrumbs }: AdminLayoutProps) {
    const page = usePage<SharedProps>();
    const { auth, flash, app } = page.props;
    const url = page.url;

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

    const env = envConfig[app.environment] ?? envConfig.local;

    return (
        <div className="flex h-screen overflow-hidden">
            {/* Sidebar — w-[240px], padding 16px 12px, border-r */}
            <aside className="flex w-[240px] shrink-0 flex-col border-r border-[#E2E2E2] bg-white px-3 py-4">
                {/* Header — padding 8px 8px 16px 8px, gap-2 */}
                <div className="flex items-center gap-2 px-2 pb-4 pt-2">
                    <span className="text-lg font-bold text-primary">emPet</span>
                    <span
                        className="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                        style={{ backgroundColor: env.bg, color: env.text }}
                    >
                        {env.label}
                    </span>
                </div>

                {/* Menu label */}
                <span className="px-3 text-[11px] font-medium text-[#9B9C9D]">Menu</span>

                {/* Menu items — gap-0.5 (2px), pt-2 (8px) */}
                <nav className="mt-2 flex flex-col gap-0.5">
                    {navigation.map((item) => {
                        const pathname = url.split('?')[0];
                        const isActive = item.href === '/admin'
                            ? pathname === '/admin' || pathname === '/admin/'
                            : pathname.startsWith(item.href);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`flex h-9 items-center gap-2 rounded-lg px-3 text-[13px] ${
                                    isActive
                                        ? 'bg-primary font-medium text-white'
                                        : 'text-[#6B6C6D] hover:bg-[#F8F8F8]'
                                }`}
                            >
                                <item.icon className="size-4" />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>

                {/* Spacer */}
                <div className="flex-1" />

                {/* Footer separator + user */}
                <UserMenu initials={initials} name={auth.user?.name} email={auth.user?.email} />
            </aside>

            {/* Main area */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Header — h-12 (48px), px-4 (16px), gap-2 (8px), border-b */}
                <header className="flex h-12 shrink-0 items-center gap-2 border-b border-[#E2E2E2] bg-white px-4">
                    <PanelLeft className="size-[18px] text-[#6B6C6D]" />
                    <div className="h-4 w-px bg-[#E2E2E2]" />
                    {breadcrumbs && breadcrumbs.length > 0 && (
                        <div className="flex items-center gap-1.5">
                            {breadcrumbs.map((crumb, i) => (
                                <span key={crumb.label} className="flex items-center gap-1.5">
                                    {i > 0 && <span className="text-[13px] text-[#9B9C9D]">/</span>}
                                    {crumb.href ? (
                                        <Link href={crumb.href} className="text-[13px] text-[#9B9C9D] hover:text-[#313233]">
                                            {crumb.label}
                                        </Link>
                                    ) : (
                                        <span className="text-[13px] text-[#6B6C6D]">{crumb.label}</span>
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </header>

                {/* Content — padding 24px, gap 24px */}
                <main className="flex-1 overflow-y-auto bg-[#F8F8F8] p-6">
                    <div className="flex flex-col gap-6">
                        {children}
                    </div>
                </main>
            </div>

            <Toaster richColors position="top-right" />
        </div>
    );
}
