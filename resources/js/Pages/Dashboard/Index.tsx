import { usePage } from '@inertiajs/react';

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        } | null;
    };
    app: {
        name: string;
        environment: string;
    };
}

export default function DashboardIndex() {
    const { auth, app } = usePage<SharedProps>().props;

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <div className="text-center">
                <h1 className="text-2xl font-bold text-gray-900">
                    {app.name} — Admin Dashboard
                </h1>
                <p className="mt-2 text-gray-600">
                    Inertia + React funcionando.
                </p>
                {auth.user && (
                    <p className="mt-1 text-sm text-gray-500">
                        Logado como: {auth.user.name} ({auth.user.role})
                    </p>
                )}
                <span className="mt-4 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 uppercase">
                    {app.environment}
                </span>
            </div>
        </div>
    );
}
