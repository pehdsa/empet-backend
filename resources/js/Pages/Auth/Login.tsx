import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/admin/login');
    }

    return (
        <GuestLayout>
            <Card>
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl font-bold text-primary">
                        Admin
                    </CardTitle>
                    <p className="text-sm text-muted-foreground">
                        Faça login para acessar o painel
                    </p>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="email">E-mail</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                autoFocus
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">{errors.email}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Senha</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">{errors.password}</p>
                            )}
                        </div>

                        <Button type="submit" className="w-full" disabled={processing}>
                            {processing ? 'Entrando...' : 'Entrar'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </GuestLayout>
    );
}
