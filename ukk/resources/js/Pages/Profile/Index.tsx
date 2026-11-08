import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { User, KeyRound } from 'lucide-react';

export default function Profile() {
    const { auth } = usePage<any>().props;
    const user = auth?.user;

    const profileForm = useForm({
        fullname: user?.fullname || '',
        email: user?.email || '',
        phone: user?.phone || '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updateProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.patch('/profile');
    };

    const updatePassword = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.put('/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <AppLayout title="Profil Pengguna">
            <Head title="Profil Akun" />

            <div className="max-w-4xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <User className="h-5 w-5" /> Informasi Data Diri
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={updateProfile} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="fullname">Nama Lengkap</Label>
                                <Input
                                    id="fullname"
                                    value={profileForm.data.fullname}
                                    onChange={(e) => profileForm.setData('fullname', e.target.value)}
                                    required
                                />
                                {profileForm.errors.fullname && (
                                    <p className="text-xs text-destructive">{profileForm.errors.fullname}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={profileForm.data.email}
                                        onChange={(e) => profileForm.setData('email', e.target.value)}
                                    />
                                    {profileForm.errors.email && (
                                        <p className="text-xs text-destructive">{profileForm.errors.email}</p>
                                    )}
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="phone">Nomor Telepon / WhatsApp</Label>
                                    <Input
                                        id="phone"
                                        value={profileForm.data.phone}
                                        onChange={(e) => profileForm.setData('phone', e.target.value)}
                                    />
                                    {profileForm.errors.phone && (
                                        <p className="text-xs text-destructive">{profileForm.errors.phone}</p>
                                    )}
                                </div>
                            </div>

                            <Button type="submit" disabled={profileForm.processing}>
                                {profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Profil'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <KeyRound className="h-5 w-5" /> Perbarui Kata Sandi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={updatePassword} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="current_password">Kata Sandi Saat Ini</Label>
                                <Input
                                    id="current_password"
                                    type="password"
                                    value={passwordForm.data.current_password}
                                    onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                    required
                                />
                                {passwordForm.errors.current_password && (
                                    <p className="text-xs text-destructive">{passwordForm.errors.current_password}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="password">Kata Sandi Baru</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={passwordForm.data.password}
                                        onChange={(e) => passwordForm.setData('password', e.target.value)}
                                        required
                                    />
                                    {passwordForm.errors.password && (
                                        <p className="text-xs text-destructive">{passwordForm.errors.password}</p>
                                    )}
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="password_confirmation">Ulangi Kata Sandi Baru</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={passwordForm.data.password_confirmation}
                                        onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                        required
                                    />
                                    {passwordForm.errors.password_confirmation && (
                                        <p className="text-xs text-destructive">{passwordForm.errors.password_confirmation}</p>
                                    )}
                                </div>
                            </div>

                            <Button type="submit" disabled={passwordForm.processing}>
                                {passwordForm.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
