import type { ReactNode } from 'react';

interface GuestLayoutProps {
    children: ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-[#F8F8F8]">
            <div className="w-full max-w-[400px] px-4">
                {children}
            </div>
        </div>
    );
}
