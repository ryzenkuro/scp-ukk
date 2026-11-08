import React, { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    Search,
    KeyRound,
    Power,
    CheckCircle2,
    Clock,
    UserPlus,
    Trash2,
    AlertTriangle,
    ArrowRightLeft,
    ShieldAlert,
    Eye,
    EyeOff,
    X,
    ChevronRight,
    Check,
    Info,
    Lock,
    User as UserIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface RoleItem {
    id: number;
    name: string;
    label: string;
}

interface UserAccountItem {
    id: number;
    role_id: number;
    username: string;
    fullname: string | null;
    email: string | null;
    phone: string | null;
    nip?: string | null;
    is_active: boolean;
    must_change_credentials: boolean;
    role?: RoleItem;
    student?: {
        nis: string;
        name: string;
        kelas: string;
    };
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface IndexProps {
    users: PaginatedData<UserAccountItem>;
    roles: RoleItem[];
    currentWaka?: UserAccountItem;
    filters: {
        search?: string;
        role_id?: string;
        status?: string;
    };
}

export default function AkunRoleIndex({ users, roles, filters, currentWaka }: IndexProps) {
    const { auth } = usePage<any>().props;

    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.role_id || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');

    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [toggleItem, setToggleItem] = useState<UserAccountItem | null>(null);
    const [resetItem, setResetItem] = useState<UserAccountItem | null>(null);
    const [deleteItem, setDeleteItem] = useState<UserAccountItem | null>(null);

    // Ganti Waka state
    const [isGantiWakaOpen, setIsGantiWakaOpen] = useState(false);
    const [gantiWakaStep, setGantiWakaStep] = useState<'confirm' | 'form'>('confirm');
    const [confirmedWarning, setConfirmedWarning] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [passwordConfirm, setPasswordConfirm] = useState('');
    const [clientError, setClientError] = useState('');

    const activeWaka = currentWaka || users.data.find(u => u.role_id === 1 || u.role?.name === 'waka') || {
        id: 1,
        fullname: 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
        username: 'admin',
        email: 'waka@smkn1bantul.sch.id',
        phone: '081234567890',
        nip: '-',
        role_id: 1,
        is_active: true,
        must_change_credentials: false,
    };

    const gantiWakaForm = useForm({
        fullname: '',
        nip: '',
        username: activeWaka?.username || 'admin',
        email: activeWaka?.email || 'waka@smkn1bantul.sch.id',
        phone: activeWaka?.phone || '',
        password: '',
        must_change_credentials: false,
    });

    const handleOpenGantiWaka = () => {
        setGantiWakaStep('confirm');
        setConfirmedWarning(false);
        setPasswordConfirm('');
        setClientError('');
        gantiWakaForm.reset();
        gantiWakaForm.setData({
            fullname: '',
            nip: activeWaka?.nip || '',
            username: activeWaka?.username || 'admin',
            email: activeWaka?.email || 'waka@smkn1bantul.sch.id',
            phone: activeWaka?.phone || '',
            password: '',
            must_change_credentials: false,
        });
        setIsGantiWakaOpen(true);
    };

    const handleCloseGantiWaka = () => {
        setIsGantiWakaOpen(false);
        setTimeout(() => {
            setGantiWakaStep('confirm');
            setConfirmedWarning(false);
            setClientError('');
            gantiWakaForm.reset();
        }, 650);
    };

    const handleProceedToForm = () => {
        if (!confirmedWarning) return;
        setGantiWakaStep('form');
    };

    const submitGantiWaka = (e: React.FormEvent) => {
        e.preventDefault();
        setClientError('');

        if (!gantiWakaForm.data.password || gantiWakaForm.data.password.length < 6) {
            setClientError('Kata sandi baru minimal 6 karakter.');
            return;
        }

        if (gantiWakaForm.data.password !== passwordConfirm) {
            setClientError('Konfirmasi kata sandi tidak cocok.');
            return;
        }

        gantiWakaForm.post('/waka/akun/ganti-waka', {
            onSuccess: () => {
                handleCloseGantiWaka();
            },
        });
    };

    const createForm = useForm({
        fullname: '',
        username: '',
        role_id: roles[0]?.id?.toString() || '2',
        nip: '',
        phone: '',
        email: '',
        password: '',
        must_change_credentials: true,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/akun', {
            search: searchQuery,
            role_id: selectedRole,
            status: selectedStatus,
        }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        const nextFilters = {
            search: searchQuery,
            role_id: selectedRole,
            status: selectedStatus,
            [key]: value,
        };

        if (key === 'role_id') setSelectedRole(value);
        if (key === 'status') setSelectedStatus(value);

        router.get('/waka/akun', nextFilters, { preserveState: true });
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/akun', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const submitToggleActive = () => {
        if (!toggleItem) return;
        if (
            toggleItem.username === 'admin' ||
            toggleItem.role?.name === 'waka' ||
            toggleItem.role_id === 1 ||
            toggleItem.id === auth?.user?.id
        ) {
            setToggleItem(null);
            return;
        }
        router.post(`/waka/akun/${toggleItem.id}/toggle-active`, {}, {
            onSuccess: () => setToggleItem(null),
        });
    };

    const submitResetCredentials = () => {
        if (!resetItem) return;
        router.post(`/waka/akun/${resetItem.id}/reset-credentials`, {}, {
            onSuccess: () => setResetItem(null),
        });
    };

    const submitDelete = () => {
        if (!deleteItem) return;
        if (
            deleteItem.username === 'admin' ||
            deleteItem.role?.name === 'waka' ||
            deleteItem.role_id === 1 ||
            deleteItem.id === auth?.user?.id
        ) {
            setDeleteItem(null);
            return;
        }
        router.delete(`/waka/akun/${deleteItem.id}`, {
            onSuccess: () => setDeleteItem(null),
        });
    };

    return (
        <AppLayout title="Kelola Akun & Hak Akses Role">
            <Head title="Kelola Akun & Role" />

            {/* Kontainer Utama Layout Isi Kelola Akun (Merapat ke Tengah lalu Hilang saat Ganti Waka dibuka) */}
            <div 
                className={cn(
                    "space-y-6 will-change-transform",
                    isGantiWakaOpen ? "layout-merapat-tengah-keluar" : "layout-merapat-tengah-masuk"
                )}
            >
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Manajemen Akun & Hak Akses Pengguna
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Pantau akun siswa (NIS), orang tua (ortu_NIS), guru, dan status aktivasi mandiri awal.
                            </p>
                        </div>
                        <Button onClick={() => setIsCreateOpen(true)} className="gap-2 shrink-0">
                            <UserPlus className="h-4 w-4" />
                            Tambah Akun
                        </Button>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            <div className="md:col-span-2 relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari username, nama lengkap, atau email..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>

                            <div>
                                <select
                                    value={selectedRole}
                                    onChange={(e) => handleFilterChange('role_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Role</option>
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.id.toString()}>{r.label}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table Akun */}
                <Card className="overflow-hidden">
                    {/* Toolbar Atas Tabel (Posisi Tombol Ganti Waka sesuai kotak merah gambar 1) */}
                    <div className="px-6 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border bg-card">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-semibold text-foreground">Daftar Akun Pengguna</span>
                            <Badge variant="outline" className="text-xs font-mono font-normal">
                                Total: {users.total}
                            </Badge>
                        </div>

                        <div className="flex items-center gap-2 self-end sm:self-auto">
                            <Button
                                type="button"
                                onClick={handleOpenGantiWaka}
                                className="bg-amber-600 hover:bg-amber-700 text-white font-medium shadow-sm transition-all gap-2 h-9 px-4 rounded-md"
                            >
                                <ArrowRightLeft className="h-4 w-4" />
                                Ganti Waka
                            </Button>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Username Akun</TableHead>
                                    <TableHead>Nama Pemilik</TableHead>
                                    <TableHead>Peran (Role)</TableHead>
                                    <TableHead className="text-center">Status Aktivasi</TableHead>
                                    <TableHead className="text-center">Status Akun</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data akun ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.data.map((user, idx) => {
                                        const isProtectedAdmin =
                                            user.username === 'admin' ||
                                            user.role?.name === 'waka' ||
                                            user.role_id === 1 ||
                                            user.id === auth?.user?.id;

                                        return (
                                            <TableRow key={user.id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {(users.current_page - 1) * 20 + idx + 1}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs font-semibold">
                                                    {user.username}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {user.fullname || '-'}
                                                    {user.student && (
                                                        <span className="block text-xs text-muted-foreground font-normal">
                                                            Kelas: {user.student.kelas}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="capitalize">
                                                        {user.role?.label || 'Pengguna'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {user.must_change_credentials ? (
                                                        <Badge variant="destructive" className="gap-1 text-xs">
                                                            <Clock className="h-3 w-3" /> Belum Aktivasi
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary" className="gap-1 text-xs font-medium">
                                                            <CheckCircle2 className="h-3 w-3" /> Sudah Aktif
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge variant={user.is_active ? 'default' : 'secondary'}>
                                                        {user.is_active ? 'Aktif' : 'Nonaktif'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setResetItem(user)}
                                                            className="h-8 w-8 p-0"
                                                            title="Reset Password ke smkn1bantul2026 & Wajib Aktivasi"
                                                        >
                                                            <KeyRound className="h-3.5 w-3.5" />
                                                        </Button>

                                                        {isProtectedAdmin ? (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                disabled
                                                                className="h-8 w-8 p-0 text-muted-foreground/30 cursor-not-allowed hover:bg-transparent"
                                                                title="Akun Administrator tidak dapat dinonaktifkan demi stabilitas sistem"
                                                            >
                                                                <Power className="h-3.5 w-3.5" />
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setToggleItem(user)}
                                                                className={`h-8 w-8 p-0 ${user.is_active ? 'text-destructive hover:text-destructive' : 'text-emerald-600 hover:text-emerald-700'}`}
                                                                title={user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                                                            >
                                                                <Power className="h-3.5 w-3.5" />
                                                            </Button>
                                                        )}

                                                        {!isProtectedAdmin && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setDeleteItem(user)}
                                                                className="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                                title="Hapus Akun"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {users.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4">
                            <span className="text-xs text-muted-foreground">
                                Total {users.total} akun terdaftar (Halaman {users.current_page} dari {users.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users.prev_page_url}
                                    onClick={() => users.prev_page_url && router.get(users.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users.next_page_url}
                                    onClick={() => users.next_page_url && router.get(users.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Tambah Akun Baru */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <UserPlus className="h-5 w-5 text-primary" />
                            Tambah Akun Pengguna Baru
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submitCreate} className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Nama Lengkap Pemilik <span className="text-destructive">*</span></label>
                            <Input
                                placeholder="Contoh: Drs. Bambang Santoso / Ahmad Zaki"
                                value={createForm.data.fullname}
                                onChange={(e) => createForm.setData('fullname', e.target.value)}
                                className="mt-1"
                                required
                            />
                            {createForm.errors.fullname && (
                                <p className="text-xs text-destructive mt-1">{createForm.errors.fullname}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-sm font-medium">Username Login <span className="text-destructive">*</span></label>
                                <Input
                                    placeholder="Contoh: bambang.s / 202611005"
                                    value={createForm.data.username}
                                    onChange={(e) => createForm.setData('username', e.target.value)}
                                    className="mt-1 font-mono text-sm"
                                    required
                                />
                                {createForm.errors.username && (
                                    <p className="text-xs text-destructive mt-1">{createForm.errors.username}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Peran (Role) <span className="text-destructive">*</span></label>
                                <select
                                    value={createForm.data.role_id}
                                    onChange={(e) => createForm.setData('role_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm mt-1"
                                    required
                                >
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.id.toString()}>
                                            {r.label}
                                        </option>
                                    ))}
                                </select>
                                {createForm.errors.role_id && (
                                    <p className="text-xs text-destructive mt-1">{createForm.errors.role_id}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-sm font-medium">NIP / Identitas (Opsional)</label>
                                <Input
                                    placeholder="NIP / No Identitas"
                                    value={createForm.data.nip}
                                    onChange={(e) => createForm.setData('nip', e.target.value)}
                                    className="mt-1"
                                />
                                {createForm.errors.nip && (
                                    <p className="text-xs text-destructive mt-1">{createForm.errors.nip}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium">No. Telepon / HP</label>
                                <Input
                                    placeholder="08xxxxxxxxxx"
                                    value={createForm.data.phone}
                                    onChange={(e) => createForm.setData('phone', e.target.value)}
                                    className="mt-1"
                                />
                                {createForm.errors.phone && (
                                    <p className="text-xs text-destructive mt-1">{createForm.errors.phone}</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-medium">Email (Opsional)</label>
                            <Input
                                type="email"
                                placeholder="pengguna@sekolah.sch.id"
                                value={createForm.data.email}
                                onChange={(e) => createForm.setData('email', e.target.value)}
                                className="mt-1"
                            />
                            {createForm.errors.email && (
                                <p className="text-xs text-destructive mt-1">{createForm.errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label className="text-sm font-medium">Kata Sandi Awal</label>
                            <Input
                                type="password"
                                placeholder="Kosongkan untuk default: smkn1bantul2026"
                                value={createForm.data.password}
                                onChange={(e) => createForm.setData('password', e.target.value)}
                                className="mt-1"
                            />
                            <p className="text-xs text-muted-foreground mt-1">
                                Jika dikosongkan, kata sandi otomatis <code>smkn1bantul2026</code>.
                            </p>
                            {createForm.errors.password && (
                                <p className="text-xs text-destructive mt-1">{createForm.errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-1">
                            <input
                                type="checkbox"
                                id="must_change_credentials"
                                checked={createForm.data.must_change_credentials}
                                onChange={(e) => createForm.setData('must_change_credentials', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <label htmlFor="must_change_credentials" className="text-xs text-muted-foreground cursor-pointer select-none">
                                Wajibkan aktivasi mandiri (ganti kredensial) saat pertama kali login
                            </label>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Akun'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Hapus Akun */}
            <Dialog open={!!deleteItem} onOpenChange={(open) => !open && setDeleteItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Hapus Akun Pengguna
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus akun <strong className="text-foreground">{deleteItem?.username}</strong> ({deleteItem?.fullname || 'Pengguna'})?
                    </p>
                    <p className="text-xs text-muted-foreground bg-destructive/10 text-destructive p-2.5 rounded border border-destructive/20">
                        Akun ini akan dihapus secara permanen dari basis data sistem dan hak aksesnya akan dicabut seketika.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteItem(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={submitDelete}>
                            Ya, Hapus Akun
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Toggle Status Aktif */}
            <Dialog open={!!toggleItem} onOpenChange={(open) => !open && setToggleItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Power className="h-5 w-5" />
                            {toggleItem?.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {toggleItem?.is_active
                            ? `Apakah Anda yakin ingin menonaktifkan akun ${toggleItem?.username}? Pengguna ini tidak akan dapat login.`
                            : `Apakah Anda ingin mengaktifkan kembali akun ${toggleItem?.username}?`}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setToggleItem(null)}>
                            Batal
                        </Button>
                        <Button
                            variant={toggleItem?.is_active ? 'destructive' : 'default'}
                            onClick={submitToggleActive}
                        >
                            {toggleItem?.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Credentials */}
            <Dialog open={!!resetItem} onOpenChange={(open) => !open && setResetItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <KeyRound className="h-5 w-5" />
                            Reset Kredensial Akun
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Setel ulang kata sandi akun <strong className="text-foreground">{resetItem?.username}</strong> ke <code className="font-bold text-foreground">smkn1bantul2026</code> dan wajibkan pengguna membuat kredensial baru saat login berikutnya?
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetItem(null)}>
                            Batal
                        </Button>
                        <Button onClick={submitResetCredentials}>
                            Ya, Reset Kredensial
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* 2D Sliding Door Animation Layer (Menutupi seluruh area main content sesuai kotak merah gambar 2) */}
            <div 
                className={cn(
                    "fixed top-0 bottom-0 right-0 left-0 md:left-64 z-50 overflow-hidden transition-all duration-300",
                    isGantiWakaOpen ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none invisible"
                )}
            >
                {/* Dark Backdrop */}
                <div 
                    className={cn(
                        "absolute inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity duration-500",
                        isGantiWakaOpen ? "opacity-100" : "opacity-0"
                    )}
                    onClick={handleCloseGantiWaka}
                />

                {/* 2D Sliding Door Panel (Meluncur datar 2D dari samping kanan ke kiri menutupi area konten utama) */}
                <div 
                    className={cn(
                        "sliding-door-panel absolute inset-0 bg-background border-l border-border shadow-2xl flex flex-col overflow-y-auto",
                        isGantiWakaOpen ? "sliding-door-closed" : "sliding-door-open"
                    )}
                >
                    {/* Header Pintu Geser Layer */}
                    <div className="flex items-center justify-between border-b border-border bg-card px-8 py-4 shrink-0 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                <ArrowRightLeft className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h2 className="text-lg font-bold tracking-tight text-foreground">
                                        Prosedur Pergantian Pejabat Waka Kurikulum
                                    </h2>
                                    <Badge className="bg-amber-500/10 text-amber-600 border-amber-500/30 text-[10px]">
                                        Tindakan Khusus
                                    </Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Alih wewenang Administrator Utama dan pemutakhiran akun resmi Waka Kurikulum
                                </p>
                            </div>
                        </div>

                        <Button 
                            variant="ghost" 
                            size="sm" 
                            onClick={handleCloseGantiWaka}
                            className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground hover:bg-muted rounded-full"
                            title="Tutup & Geser Kembali"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>

                    {/* Step Navigator */}
                    <div className="border-b border-border/70 bg-muted/40 px-8 py-3 shrink-0">
                        <div className="flex items-center gap-2 text-xs">
                            <button
                                type="button"
                                onClick={() => setGantiWakaStep('confirm')}
                                className={cn(
                                    "flex items-center gap-2 px-3 py-1 rounded-full font-medium transition",
                                    gantiWakaStep === 'confirm' 
                                        ? "bg-amber-600 text-white shadow-sm" 
                                        : "bg-muted text-muted-foreground hover:text-foreground"
                                )}
                            >
                                <span className="flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] font-bold">1</span>
                                <span>Peringatan & Konfirmasi</span>
                            </button>
                            
                            <ChevronRight className="h-3.5 w-3.5 text-muted-foreground" />
                            
                            <div className={cn(
                                "flex items-center gap-2 px-3 py-1 rounded-full font-medium transition",
                                gantiWakaStep === 'form' 
                                    ? "bg-amber-600 text-white shadow-sm" 
                                    : "bg-muted text-muted-foreground"
                            )}>
                                <span className="flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] font-bold">2</span>
                                <span>Isi Data Akun Pejabat Baru</span>
                            </div>

                            <ChevronRight className="h-3.5 w-3.5 text-muted-foreground" />

                            <div className="flex items-center gap-2 px-3 py-1 rounded-full font-medium text-muted-foreground">
                                <span className="flex h-4 w-4 items-center justify-center rounded-full bg-muted text-[10px] font-bold">3</span>
                                <span>Sistem Memperbarui Data</span>
                            </div>
                        </div>
                    </div>

                    {/* Content Body */}
                    <div className="flex-1 p-8 overflow-y-auto bg-muted/20">
                        {gantiWakaStep === 'confirm' ? (
                            /* Layar 1: Peringatan Konfirmasi Terlebih Dahulu (Animasi Meluncur ke Samping Kiri) */
                            <div key="confirm-step" className="max-w-3xl mx-auto space-y-6 animate-slide-left-enter">
                                {/* Alert Hero */}
                                <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-6 shadow-sm">
                                    <div className="flex items-start gap-4">
                                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-600">
                                            <ShieldAlert className="h-6 w-6" />
                                        </div>
                                        <div className="space-y-1.5">
                                            <h3 className="text-base font-bold text-foreground">
                                                PERINGATAN RESMI: PENGALIHAN WEWENANG WAKA KURIKULUM
                                            </h3>
                                            <p className="text-sm text-muted-foreground leading-relaxed">
                                                Waka Kurikulum merupakan pemegang peran <strong>Administrator Utama</strong> dalam sistem. 
                                                Mengganti data pejabat Waka akan mengalihkan hak kelola data akun sekolah, sinkronisasi jadwal, 
                                                dan kontrol kenaikan kelas kepada pejabat baru.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Profil Waka Saat Ini */}
                                <Card>
                                    <CardHeader className="pb-3 border-b border-border/60">
                                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                            <UserIcon className="h-4 w-4 text-muted-foreground" />
                                            Data Pejabat Waka Kurikulum yang Terdaftar Saat Ini
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-xs text-muted-foreground block">Nama Lengkap Saat Ini</span>
                                            <span className="font-semibold text-foreground">{activeWaka.fullname || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-xs text-muted-foreground block">Username Login</span>
                                            <span className="font-mono font-semibold text-foreground">{activeWaka.username}</span>
                                        </div>
                                        <div>
                                            <span className="text-xs text-muted-foreground block">Alamat Email</span>
                                            <span className="text-foreground">{activeWaka.email || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-xs text-muted-foreground block">Status Akun</span>
                                            <Badge variant="default" className="text-xs mt-0.5">
                                                Aktif (Administrator Utama)
                                            </Badge>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Poin Konsekuensi */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="rounded-lg border border-border bg-card p-4 space-y-2">
                                        <div className="flex items-center gap-2 text-amber-600 font-semibold text-xs">
                                            <Lock className="h-4 w-4" />
                                            Kredensial Login
                                        </div>
                                        <p className="text-xs text-muted-foreground leading-relaxed">
                                            Kata sandi dan identitas baru akan diterapkan. Kredensial pejabat lama akan digantikan seketika.
                                        </p>
                                    </div>

                                    <div className="rounded-lg border border-border bg-card p-4 space-y-2">
                                        <div className="flex items-center gap-2 text-emerald-600 font-semibold text-xs">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Keamanan Arsip Data
                                        </div>
                                        <p className="text-xs text-muted-foreground leading-relaxed">
                                            Seluruh riwayat presensi, jurnal guru, dan data master sekolah tetap terjaga utuh tanpa terhapus.
                                        </p>
                                    </div>

                                    <div className="rounded-lg border border-border bg-card p-4 space-y-2">
                                        <div className="flex items-center gap-2 text-primary font-semibold text-xs">
                                            <Info className="h-4 w-4" />
                                            Otoritas Penuh
                                        </div>
                                        <p className="text-xs text-muted-foreground leading-relaxed">
                                            Pejabat baru yang terdaftar akan memiliki otoritas penuh terhadap sistem penjaminan mutu kurikulum.
                                        </p>
                                    </div>
                                </div>

                                {/* Checkbox Persetujuan Konfirmasi */}
                                <div className="rounded-lg border border-border bg-card p-4 shadow-sm flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="confirmedWarning"
                                        checked={confirmedWarning}
                                        onChange={(e) => setConfirmedWarning(e.target.checked)}
                                        className="h-4 w-4 mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500 cursor-pointer"
                                    />
                                    <label htmlFor="confirmedWarning" className="text-xs text-foreground cursor-pointer select-none leading-relaxed">
                                        <strong>Saya mengerti dan menyetujui seluruh konsekuensi di atas.</strong> Saya menyatakan bahwa pergantian pejabat Waka Kurikulum ini dilakukan atas dasar serah terima jabatan resmi dan saya berwenang memperbarui data ini.
                                    </label>
                                </div>

                                {/* Tombol Navigasi Konfirmasi */}
                                <div className="flex items-center justify-between pt-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleCloseGantiWaka}
                                        className="gap-2"
                                    >
                                        Batal & Geser Kembali
                                    </Button>

                                    <Button
                                        type="button"
                                        disabled={!confirmedWarning}
                                        onClick={handleProceedToForm}
                                        className="bg-amber-600 hover:bg-amber-700 text-white gap-2 font-medium shadow-sm transition disabled:opacity-50"
                                    >
                                        Saya Mengerti & Lanjutkan
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            /* Layar 2: Formulir Pengisian Data Akun Pejabat Waka Baru (Animasi Meluncur ke Samping Kiri) */
                            <div key="form-step" className="max-w-2xl mx-auto animate-slide-left-enter">
                                <Card className="shadow-md">
                                    <CardHeader className="border-b border-border/60 pb-4">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                                                <UserPlus className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-base font-bold">
                                                    Isi Data Akun Pejabat Waka Kurikulum Baru
                                                </CardTitle>
                                                <p className="text-xs text-muted-foreground mt-0.5">
                                                    Masukkan identitas dan kredensial login baru. Sistem akan memperbarui akun resmi Waka.
                                                </p>
                                            </div>
                                        </div>
                                    </CardHeader>

                                    <form onSubmit={submitGantiWaka}>
                                        <CardContent className="space-y-4 pt-6">
                                            {/* Error Message Box */}
                                            {(clientError || Object.keys(gantiWakaForm.errors).length > 0) && (
                                                <div className="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-xs text-destructive space-y-1">
                                                    {clientError && <p className="font-semibold">{clientError}</p>}
                                                    {Object.entries(gantiWakaForm.errors).map(([field, err]) => (
                                                        <p key={field}>• {err}</p>
                                                    ))}
                                                </div>
                                            )}

                                            <div>
                                                <label className="text-xs font-semibold text-foreground">
                                                    Nama Lengkap Pejabat Baru (+ Gelar) <span className="text-destructive">*</span>
                                                </label>
                                                <Input
                                                    placeholder="Contoh: Dra. Hj. Siti Nurhaliza, M.Pd"
                                                    value={gantiWakaForm.data.fullname}
                                                    onChange={(e) => gantiWakaForm.setData('fullname', e.target.value)}
                                                    className="mt-1"
                                                    required
                                                />
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        NIP / No. Identitas Pegawai (Opsional)
                                                    </label>
                                                    <Input
                                                        placeholder="19800215 200501 2 003"
                                                        value={gantiWakaForm.data.nip}
                                                        onChange={(e) => gantiWakaForm.setData('nip', e.target.value)}
                                                        className="mt-1 font-mono text-sm"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        Username Akun Login <span className="text-destructive">*</span>
                                                    </label>
                                                    <Input
                                                        placeholder="admin"
                                                        value={gantiWakaForm.data.username}
                                                        onChange={(e) => gantiWakaForm.setData('username', e.target.value)}
                                                        className="mt-1 font-mono text-sm"
                                                        required
                                                    />
                                                    <p className="text-[11px] text-muted-foreground mt-0.5">
                                                        Username untuk login ke sistem
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        Email Resmi Sekolah <span className="text-destructive">*</span>
                                                    </label>
                                                    <Input
                                                        type="email"
                                                        placeholder="waka@smkn1bantul.sch.id"
                                                        value={gantiWakaForm.data.email}
                                                        onChange={(e) => gantiWakaForm.setData('email', e.target.value)}
                                                        className="mt-1"
                                                        required
                                                    />
                                                </div>

                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        No. Telepon / WhatsApp
                                                    </label>
                                                    <Input
                                                        placeholder="081234567899"
                                                        value={gantiWakaForm.data.phone}
                                                        onChange={(e) => gantiWakaForm.setData('phone', e.target.value)}
                                                        className="mt-1"
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        Kata Sandi Baru <span className="text-destructive">*</span>
                                                    </label>
                                                    <div className="relative mt-1">
                                                        <Input
                                                            type={showPassword ? 'text' : 'password'}
                                                            placeholder="Minimal 6 karakter"
                                                            value={gantiWakaForm.data.password}
                                                            onChange={(e) => gantiWakaForm.setData('password', e.target.value)}
                                                            className="pr-10"
                                                            required
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => setShowPassword(!showPassword)}
                                                            className="absolute right-3 top-2.5 text-muted-foreground hover:text-foreground"
                                                        >
                                                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label className="text-xs font-semibold text-foreground">
                                                        Ulangi Kata Sandi Baru <span className="text-destructive">*</span>
                                                    </label>
                                                    <div className="relative mt-1">
                                                        <Input
                                                            type={showPassword ? 'text' : 'password'}
                                                            placeholder="Ketik ulang kata sandi"
                                                            value={passwordConfirm}
                                                            onChange={(e) => setPasswordConfirm(e.target.value)}
                                                            className="pr-10"
                                                            required
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center space-x-2 pt-2">
                                                <input
                                                    type="checkbox"
                                                    id="waka_must_change_credentials"
                                                    checked={gantiWakaForm.data.must_change_credentials}
                                                    onChange={(e) => gantiWakaForm.setData('must_change_credentials', e.target.checked)}
                                                    className="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 cursor-pointer"
                                                />
                                                <label htmlFor="waka_must_change_credentials" className="text-xs text-muted-foreground cursor-pointer select-none">
                                                    Wajibkan pejabat baru mengubah kredensial (kata sandi) saat pertama kali login
                                                </label>
                                            </div>
                                        </CardContent>

                                        <div className="flex items-center justify-between border-t border-border px-6 py-4 bg-muted/30">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => setGantiWakaStep('confirm')}
                                            >
                                                Kembali ke Peringatan
                                            </Button>

                                            <Button
                                                type="submit"
                                                disabled={gantiWakaForm.processing}
                                                className="bg-amber-600 hover:bg-amber-700 text-white gap-2 font-medium shadow-sm transition"
                                            >
                                                {gantiWakaForm.processing ? (
                                                    <>
                                                        <Clock className="h-4 w-4 animate-spin" />
                                                        Memperbarui Sistem...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Check className="h-4 w-4" />
                                                        Simpan & Terapkan Perubahan Waka
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    </form>
                                </Card>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
