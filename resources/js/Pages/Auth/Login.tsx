import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import GuestLayout from '@/Layouts/GuestLayout';
import { Input } from '@/components/ui/input';

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
            <div className="w-full rounded-xl bg-white p-8 shadow-[0_2px_12px_#00000012]">
                {/* Logo + Title — gap-6 (24px) between sections */}
                <div className="flex flex-col items-center gap-6">
                    {/* Logo */}
                    <span className="text-[28px] font-bold text-primary">
                        emPet
                    </span>

                    {/* Title + Subtitle — gap-1 (4px) */}
                    <div className="flex flex-col items-center gap-1">
                        <h1 className="text-xl font-semibold text-[#313233]">
                            Admin
                        </h1>
                        <p className="text-sm text-[#6B6C6D]">
                            Faça login para acessar o painel
                        </p>
                    </div>

                    {/* Form — gap-4 (16px) between fields */}
                    <form onSubmit={handleSubmit} className="flex w-full flex-col gap-4">
                        {/* Email — gap-1.5 (6px) between label and input */}
                        <div className="flex flex-col gap-1.5">
                            <label htmlFor="email" className="text-sm font-medium text-[#313233]">
                                E-mail
                            </label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="admin@empet.com"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                autoFocus
                                className="h-10 rounded-lg border-[#E2E2E2] px-3 text-sm"
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">{errors.email}</p>
                            )}
                        </div>

                        {/* Password — gap-1.5 (6px) */}
                        <div className="flex flex-col gap-1.5">
                            <label htmlFor="password" className="text-sm font-medium text-[#313233]">
                                Senha
                            </label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="••••••••"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                className="h-10 rounded-lg border-[#E2E2E2] px-3 text-sm"
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">{errors.password}</p>
                            )}
                        </div>

                        {/* Submit button — h-11 (44px) */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="h-11 w-full rounded-lg bg-primary text-sm font-semibold text-white transition-colors hover:bg-[#CC8000] disabled:opacity-50"
                        >
                            {processing ? 'Entrando...' : 'Entrar'}
                        </button>
                    </form>
                </div>
            </div>
        </GuestLayout>
    );
}
