import React, { useState } from 'react';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    User as UserIcon,
    KeyRound,
    Lock,
    Mail,
    Phone,
    Eye,
    EyeOff,
    CheckCircle2,
    AlertTriangle,
    Trash2,
    GraduationCap,
    School,
    Shield,
    Sparkles,
} from 'lucide-react';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

export default function Edit({ mustVerifyEmail, status }: Props) {
    const { auth } = usePage<any>().props;
    const user = auth?.user;

    // Password visibility toggles
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // Delete modal toggle
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    // Form: Profile Information
    const profileForm = useForm({
        fullname: user?.fullname || '',
        email: user?.email || '',
        phone: user?.phone || '',
    });

    // Form: Update Password
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // Form: Delete Account
    const deleteForm = useForm({
        password: '',
    });

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.patch(route('profile.update'), {
            preserveScroll: true,
        });
    };

    const submitPassword = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const submitDelete = (e: React.FormEvent) => {
        e.preventDefault();
        deleteForm.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => setShowDeleteModal(false),
            onError: () => deleteForm.reset('password'),
        });
    };

    // Helper: User Initials
    const getInitials = (name?: string) => {
        if (!name) return 'U';
        const words = name.trim().split(/\s+/);
        if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
        return (words[0][0] + words[1][0]).toUpperCase();
    };

    // Role Metadata
    const roleName = user?.role?.name || (user?.role_id === 1 ? 'waka' : user?.role_id === 2 ? 'guru' : user?.role_id === 3 ? 'siswa' : 'user');
    const roleLabel = user?.role?.label || (
        roleName === 'waka' ? 'Waka Kurikulum / Administrator' :
        roleName === 'guru' ? 'Guru Pengampu' :
        roleName === 'wali_kelas' ? 'Wali Kelas' :
        roleName === 'guru_bk' ? 'Guru BK' :
        roleName === 'siswa' ? 'Siswa / Murid' :
        roleName === 'orang_tua' ? 'Orang Tua Siswa' : 'Pengguna Sekolah'
    );

    const className = user?.schoolClass?.name || user?.student?.kelas || '';
    const nip = user?.nip || '';

    return (
        <AppLayout title="Profil Akun Pengguna">
            <Head title="Profil Akun" />

            <div className="mx-auto max-w-2xl space-y-6 pb-12">
                {/* 1. Header Card: Identity Overview */}
                <Card className="border shadow-sm">
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                            {/* Avatar */}
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-lg ring-2 ring-primary/20">
                                {getInitials(user?.fullname || user?.username)}
                            </div>

                            {/* Details */}
                            <div className="flex-1 min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                    <h2 className="text-lg font-bold tracking-tight text-foreground truncate">
                                        {user?.fullname || user?.username}
                                    </h2>
                                    <Badge variant="secondary" className="font-semibold text-xs">
                                        {roleLabel}
                                    </Badge>
                                </div>

                                {(className || nip) && (
                                    <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-0.5 text-xs text-muted-foreground">
                                        {className && (
                                            <span className="inline-flex items-center gap-1 font-medium text-foreground bg-muted px-2 py-0.5 rounded text-[11px]">
                                                <School className="h-3.5 w-3.5 text-primary" /> Kelas: {className}
                                            </span>
                                        )}
                                        {nip && (
                                            <span className="inline-flex items-center gap-1 bg-muted px-2 py-0.5 rounded text-[11px]">
                                                <Shield className="h-3.5 w-3.5 text-primary" /> NIP: {nip}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 2. Card: Profile Information Form */}
                <Card className="border shadow-sm">
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <UserIcon className="h-4 w-4 text-primary" /> Informasi Data Diri & Kontak
                        </CardTitle>
                        <CardDescription className="text-xs">
                            Perbarui nama lengkap, alamat email, dan nomor WhatsApp aktif Anda.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitProfile} className="space-y-4">
                            {/* Fullname */}
                            <div className="space-y-1.5">
                                <Label htmlFor="fullname" className="text-xs font-semibold">Nama Lengkap</Label>
                                <Input
                                    id="fullname"
                                    type="text"
                                    value={profileForm.data.fullname}
                                    onChange={(e) => profileForm.setData('fullname', e.target.value)}
                                    placeholder="Masukkan nama lengkap Anda"
                                    required
                                    className="h-9 text-sm"
                                />
                                {profileForm.errors.fullname && (
                                    <p className="text-xs text-destructive">{profileForm.errors.fullname}</p>
                                )}
                            </div>

                            {/* Username (Locked) */}
                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="username" className="text-xs font-semibold text-muted-foreground flex items-center gap-1.5">
                                        <Lock className="h-3 w-3" /> Username / ID Akun
                                    </Label>
                                    <span className="text-[11px] text-muted-foreground italic">Tidak dapat diubah</span>
                                </div>
                                <Input
                                    id="username"
                                    type="text"
                                    value={user?.username || ''}
                                    disabled
                                    className="h-9 text-sm bg-muted/50 cursor-not-allowed font-mono text-muted-foreground"
                                />
                            </div>

                            {/* Email */}
                            <div className="space-y-1.5">
                                <Label htmlFor="email" className="text-xs font-semibold flex items-center gap-1.5">
                                    <Mail className="h-3.5 w-3.5 text-muted-foreground" /> Alamat Email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={profileForm.data.email}
                                    onChange={(e) => profileForm.setData('email', e.target.value)}
                                    placeholder="nama@contoh.com"
                                    className="h-9 text-sm"
                                />
                                {profileForm.errors.email && (
                                    <p className="text-xs text-destructive">{profileForm.errors.email}</p>
                                )}

                                {mustVerifyEmail && user?.email_verified_at === null && (
                                    <div className="mt-2 rounded-md bg-amber-500/10 border border-amber-500/20 p-2.5 text-xs text-amber-700 dark:text-amber-300">
                                        <p>
                                            Alamat email Anda belum terverifikasi.{' '}
                                            <Link
                                                href={route('verification.send')}
                                                method="post"
                                                as="button"
                                                className="font-medium underline hover:text-amber-900 dark:hover:text-amber-100"
                                            >
                                                Kirim ulang tautan verifikasi.
                                            </Link>
                                        </p>
                                        {status === 'verification-link-sent' && (
                                            <p className="mt-1 font-semibold text-emerald-600">
                                                Tautan verifikasi baru telah dikirim ke email Anda.
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Phone / WhatsApp */}
                            <div className="space-y-1.5">
                                <Label htmlFor="phone" className="text-xs font-semibold flex items-center gap-1.5">
                                    <Phone className="h-3.5 w-3.5 text-muted-foreground" /> Nomor Telepon / WhatsApp
                                </Label>
                                <Input
                                    id="phone"
                                    type="text"
                                    value={profileForm.data.phone}
                                    onChange={(e) => profileForm.setData('phone', e.target.value)}
                                    placeholder="Contoh: 081234567890"
                                    className="h-9 text-sm"
                                />
                                {profileForm.errors.phone && (
                                    <p className="text-xs text-destructive">{profileForm.errors.phone}</p>
                                )}
                            </div>

                            <div className="pt-2 flex items-center justify-between">
                                <Button
                                    type="submit"
                                    disabled={profileForm.processing}
                                    className="h-9 text-xs font-medium"
                                >
                                    {profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </Button>

                                {profileForm.recentlySuccessful && (
                                    <span className="flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                        <CheckCircle2 className="h-4 w-4" /> Berhasil disimpan
                                    </span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 3. Card: Password Update Form */}
                <Card className="border shadow-sm">
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <KeyRound className="h-4 w-4 text-primary" /> Perbarui Kata Sandi
                        </CardTitle>
                        <CardDescription className="text-xs">
                            Gunakan kombinasi yang aman untuk melindungi akun sekolah Anda.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitPassword} className="space-y-4">
                            {/* Current Password */}
                            <div className="space-y-1.5">
                                <Label htmlFor="current_password" className="text-xs font-semibold">
                                    Kata Sandi Saat Ini
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="current_password"
                                        type={showCurrentPassword ? 'text' : 'password'}
                                        value={passwordForm.data.current_password}
                                        onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                        placeholder="Masukkan sandi lama"
                                        required
                                        className="h-9 pr-9 text-sm"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                        className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground transition"
                                        tabIndex={-1}
                                    >
                                        {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {passwordForm.errors.current_password && (
                                    <p className="text-xs text-destructive">{passwordForm.errors.current_password}</p>
                                )}
                            </div>

                            {/* New Password & Confirmation */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="password" className="text-xs font-semibold">
                                        Kata Sandi Baru
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            id="password"
                                            type={showNewPassword ? 'text' : 'password'}
                                            value={passwordForm.data.password}
                                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                                            placeholder="Minimal 8 karakter"
                                            required
                                            className="h-9 pr-9 text-sm"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowNewPassword(!showNewPassword)}
                                            className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground transition"
                                            tabIndex={-1}
                                        >
                                            {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    {passwordForm.errors.password && (
                                        <p className="text-xs text-destructive">{passwordForm.errors.password}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password_confirmation" className="text-xs font-semibold">
                                        Ulangi Sandi Baru
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            id="password_confirmation"
                                            type={showConfirmPassword ? 'text' : 'password'}
                                            value={passwordForm.data.password_confirmation}
                                            onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                            placeholder="Ulangi kata sandi"
                                            required
                                            className="h-9 pr-9 text-sm"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                            className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground transition"
                                            tabIndex={-1}
                                        >
                                            {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    {passwordForm.errors.password_confirmation && (
                                        <p className="text-xs text-destructive">{passwordForm.errors.password_confirmation}</p>
                                    )}
                                </div>
                            </div>

                            <div className="pt-2 flex items-center justify-between">
                                <Button
                                    type="submit"
                                    disabled={passwordForm.processing}
                                    className="h-9 text-xs font-medium"
                                >
                                    {passwordForm.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi'}
                                </Button>

                                {passwordForm.recentlySuccessful && (
                                    <span className="flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                        <CheckCircle2 className="h-4 w-4" /> Sandi berhasil diperbarui
                                    </span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 4. Subtle Danger Zone: Delete Account Dialog */}
                <Card className="border border-destructive/20 bg-destructive/5 shadow-sm">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold text-destructive flex items-center gap-2">
                                    <AlertTriangle className="h-4 w-4" /> Zona Bahaya Akun
                                </CardTitle>
                                <CardDescription className="text-xs text-muted-foreground mt-0.5">
                                    Penghapusan akun bersifat permanen dan tidak dapat dipulihkan.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setShowDeleteModal(true)}
                                className="h-8 text-xs border-destructive/30 text-destructive hover:bg-destructive/10"
                            >
                                Hapus Akun
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                {/* Dialog: Delete Account Confirmation */}
                <Dialog open={showDeleteModal} onOpenChange={setShowDeleteModal}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-destructive">
                                <Trash2 className="h-5 w-5" /> Konfirmasi Hapus Akun
                            </DialogTitle>
                            <DialogDescription className="text-xs text-muted-foreground">
                                Apakah Anda yakin ingin menghapus akun ini secara permanen? Seluruh riwayat dan data sesi Anda akan dihapus dari sistem. Silakan masukkan kata sandi Anda untuk konfirmasi.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={submitDelete} className="space-y-4 pt-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="delete_password" className="text-xs font-semibold">
                                    Kata Sandi Anda
                                </Label>
                                <Input
                                    id="delete_password"
                                    type="password"
                                    value={deleteForm.data.password}
                                    onChange={(e) => deleteForm.setData('password', e.target.value)}
                                    placeholder="Masukkan kata sandi saat ini"
                                    required
                                    className="h-9 text-sm"
                                    autoFocus
                                />
                                {deleteForm.errors.password && (
                                    <p className="text-xs text-destructive">{deleteForm.errors.password}</p>
                                )}
                            </div>

                            <DialogFooter className="flex gap-2 sm:justify-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setShowDeleteModal(false);
                                        deleteForm.reset();
                                    }}
                                    className="h-9 text-xs"
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    disabled={deleteForm.processing}
                                    className="h-9 text-xs"
                                >
                                    {deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus Akun'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
