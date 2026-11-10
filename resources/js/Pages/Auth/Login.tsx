import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { School } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword?: boolean;
}

export default function Login({ status }: LoginProps) {
    const { flash } = usePage<any>().props;
    const successMessage = flash?.success || flash?.status || status;

    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <section className="bg-muted/40 min-h-screen flex items-center justify-center px-4 py-12">
            <Head title="Masuk ke Sistem" />

            {/* Pure Raw Shadcnblocks Login1 Block */}
            <div className="border border-border bg-card text-card-foreground flex w-full max-w-sm flex-col items-center gap-y-6 rounded-xl p-8 shadow-sm">
                <div className="flex flex-col items-center gap-y-2 text-center">
                    {/* Logo */}
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <School className="h-6 w-6" />
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight">SMK N 1 BANTUL</h1>
                    <p className="text-xs text-muted-foreground">
                        Sistem Presensi & Agenda Pembelajaran UKK
                    </p>
                </div>

                {successMessage && (
                    <div className="w-full rounded-md bg-emerald-500/10 border border-emerald-500/20 p-3 text-xs text-emerald-700 dark:text-emerald-400 font-medium text-center">
                        {successMessage}
                    </div>
                )}

                {/* Form Input */}
                <form className="w-full space-y-4" onSubmit={submit}>
                    <div className="space-y-1.5">
                        <Label htmlFor="username">NIS / NIP / Username</Label>
                        <Input
                            id="username"
                            type="text"
                            value={data.username}
                            placeholder="Contoh: 202611001 atau waka"
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => setData('username', e.target.value)}
                            required
                        />
                        {errors.username && (
                            <p className="text-xs text-destructive">{errors.username}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="password">Kata Sandi</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            placeholder="••••••••••••"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        {errors.password && (
                            <p className="text-xs text-destructive">{errors.password}</p>
                        )}
                    </div>

                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="rounded border-input text-primary shadow-sm focus:ring-ring"
                            />
                            <span>Ingat saya</span>
                        </label>
                        <Link
                            href="/forgot-password"
                            className="text-primary hover:underline font-medium"
                        >
                            *Lupa sandi?
                        </Link>
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? 'Memproses Masuk...' : 'Masuk ke Aplikasi'}
                    </Button>
                </form>

                <div className="text-center text-[11px] text-muted-foreground">
                    &copy; 2026 SMK Negeri 1 Bantul &bull; Uji Kompetensi Keahlian RPL
                </div>
            </div>
        </section>
    );
}
