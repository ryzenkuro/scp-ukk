import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { ShieldCheck } from 'lucide-react';

export default function ActivateAccount({ initialEmail = '' }: { initialEmail?: string }) {
    const { auth } = usePage<any>().props;
    const user = auth?.user;

    const { data, setData, post, processing, errors } = useForm({
        new_username: user?.username || '',
        email: initialEmail || user?.email || '',
        new_password: '',
        new_password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/auth/activate');
    };

    return (
        <section className="bg-muted/40 min-h-screen flex items-center justify-center px-4 py-12">
            <Head title="Aktivasi Akun Mandiri" />

            <div className="w-full max-w-md">
                <Card className="shadow-sm">
                    <CardHeader className="text-center pb-2">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm mb-2">
                            <ShieldCheck className="h-6 w-6" />
                        </div>
                        <CardTitle className="text-xl font-bold">
                            Aktivasi Akun Mandiri
                        </CardTitle>
                        <CardDescription className="text-xs text-muted-foreground mt-1">
                            Halo <span className="font-semibold text-foreground">{user?.fullname || user?.username}</span>! Karena ini pertama kali masuk dengan kata sandi bawaan, silakan tentukan <span className="font-semibold text-foreground">Username</span> dan <span className="font-semibold text-foreground">Kata Sandi Baru</span> pilihan Anda sendiri.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="pt-4">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="new_username">Username Baru Pilihan Anda</Label>
                                <Input
                                    id="new_username"
                                    type="text"
                                    value={data.new_username}
                                    placeholder="Contoh: budi_santoso atau kuro99"
                                    onChange={(e) => setData('new_username', e.target.value)}
                                    required
                                    autoFocus
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    Digunakan untuk login berikutnya. Huruf kecil, angka, atau garis bawah (_).
                                </p>
                                {errors.new_username && (
                                    <p className="text-xs text-destructive">{errors.new_username}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="email">Email Aktif</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    placeholder="contoh: namaanda@gmail.com"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    Digunakan untuk menerima kode OTP jika Anda lupa kata sandi di kemudian hari.
                                </p>
                                {errors.email && (
                                    <p className="text-xs text-destructive">{errors.email}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="new_password">Kata Sandi Baru</Label>
                                <Input
                                    id="new_password"
                                    type="password"
                                    value={data.new_password}
                                    placeholder="Minimal 8 karakter"
                                    onChange={(e) => setData('new_password', e.target.value)}
                                    required
                                />
                                {errors.new_password && (
                                    <p className="text-xs text-destructive">{errors.new_password}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="new_password_confirmation">Ulangi Kata Sandi Baru</Label>
                                <Input
                                    id="new_password_confirmation"
                                    type="password"
                                    value={data.new_password_confirmation}
                                    placeholder="Konfirmasi kata sandi"
                                    onChange={(e) => setData('new_password_confirmation', e.target.value)}
                                    required
                                />
                                {errors.new_password_confirmation && (
                                    <p className="text-xs text-destructive">{errors.new_password_confirmation}</p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="w-full mt-2"
                                disabled={processing}
                            >
                                {processing ? 'Menyimpan Kredensial...' : 'Aktifkan Akun & Masuk'}
                            </Button>
                        </form>
                    </CardContent>

                    <CardFooter className="justify-center border-t border-border pt-4 text-center text-xs text-muted-foreground">
                        Keamanan Akun Terjamin &bull; SMK N 1 BANTUL
                    </CardFooter>
                </Card>
            </div>
        </section>
    );
}
