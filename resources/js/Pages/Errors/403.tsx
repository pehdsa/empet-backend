import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';

export default function Forbidden() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-muted/40">
            <div className="text-center">
                <h1 className="text-6xl font-bold text-muted-foreground">403</h1>
                <p className="mt-2 text-lg text-muted-foreground">
                    Acesso não autorizado
                </p>
                <Button asChild className="mt-6">
                    <Link href="/admin/login">Voltar ao login</Link>
                </Button>
            </div>
        </div>
    );
}
