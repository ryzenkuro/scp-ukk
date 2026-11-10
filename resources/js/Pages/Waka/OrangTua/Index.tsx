import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
    Users,
    Search,
    Edit2,
    KeyRound,
    CheckCircle2,
    Clock,
    ShieldAlert,
    Download
} from 'lucide-react';

interface OrangTuaItem {
    id: number;
    username: string;
    fullname: string;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    must_change_credentials: boolean;
    student?: {
        id: number;
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
    orangTuas: PaginatedData<OrangTuaItem>;
    filters: {
        search?: string;
    };
    stats: {
        total: number;
        activated: number;
        pending: number;
    };
}

export default function OrangTuaIndex({ orangTuas, filters, stats }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [editItem, setEditItem] = useState<OrangTuaItem | null>(null);
    const [resetItem, setResetItem] = useState<OrangTuaItem | null>(null);

    const editForm = useForm({
        fullname: '',
        phone: '',
        email: '',
        is_active: true,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/orang-tua', { search: searchQuery }, { preserveState: true });
    };

    const openEdit = (ot: OrangTuaItem) => {
        setEditItem(ot);
        editForm.setData({
            fullname: ot.fullname,
            phone: ot.phone || '',
            email: ot.email || '',
            is_active: ot.is_active,
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editItem) return;
        editForm.put(`/waka/orang-tua/${editItem.id}`, {
            onSuccess: () => setEditItem(null),
        });
    };

    const submitReset = () => {
        if (!resetItem) return;
        router.post(`/waka/orang-tua/${resetItem.id}/reset-password`, {}, {
            onSuccess: () => setResetItem(null),
        });
    };

    return (
        <AppLayout title="Data Master Orang Tua Siswa">
            <Head title="Data Orang Tua" />

            <div className="space-y-6">
                {/* 3 Kartu Ringkasan Metrik Status Aktivasi */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Akun Orang Tua</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                            <p className="text-xs text-muted-foreground mt-1">Terhubung otomatis dengan data siswa</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Sudah Aktivasi Mandiri</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {stats.activated}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Kredensial baru disetel mandiri</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Belum Aktivasi</CardTitle>
                            <Clock className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {stats.pending}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Default: ortu_[NIS] / smkn1bantul2026</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter & Panduan Pola Akun */}
                <Card>
                    <CardHeader className="pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Pengelolaan Akun Orang Tua Murid
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Akun orang tua dibuat terpisah secara default dengan username <code className="font-semibold text-foreground">ortu_[NIS]</code> dan kata sandi awal <code className="font-semibold text-foreground">smkn1bantul2026</code>. Orang tua dapat melakukan aktivasi mandiri saat login pertama kali.
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="max-w-md relative">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama orang tua, siswa, atau NIS..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </form>
                    </CardContent>
                </Card>

                {/* Table Orang Tua */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Username Akun</TableHead>
                                    <TableHead>Nama Orang Tua</TableHead>
                                    <TableHead>Siswa Terkait</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Kontak Telepon</TableHead>
                                    <TableHead className="text-center">Status Akun</TableHead>
                                    <TableHead className="text-center">Status Aktivasi</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orangTuas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data orang tua murid ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    orangTuas.data.map((ot, idx) => (
                                        <TableRow key={ot.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {(orangTuas.current_page - 1) * 20 + idx + 1}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs font-semibold">
                                                {ot.username}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {ot.fullname}
                                            </TableCell>
                                            <TableCell>
                                                {ot.student ? (
                                                    <div>
                                                        <span className="font-medium">{ot.student.name}</span>
                                                        <span className="block text-xs text-muted-foreground">NIS: {ot.student.nis}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground italic">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {ot.student?.kelas ? (
                                                    <Badge variant="outline">{ot.student.kelas}</Badge>
                                                ) : '-'}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {ot.phone || '-'}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={ot.is_active ? 'secondary' : 'destructive'}>
                                                    {ot.is_active ? 'Aktif' : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {!ot.must_change_credentials ? (
                                                    <Badge variant="secondary" className="gap-1 bg-emerald-50 text-emerald-700 border-emerald-200 text-xs">
                                                        <CheckCircle2 className="h-3 w-3" /> Sudah Aktivasi
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="gap-1 bg-amber-50 text-amber-700 border-amber-200 text-xs">
                                                        <Clock className="h-3 w-3" /> Belum Aktivasi
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setResetItem(ot)}
                                                        className="h-8 w-8 p-0"
                                                        title="Reset Kata Sandi ke smkn1bantul2026 & Wajib Aktivasi"
                                                    >
                                                        <KeyRound className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEdit(ot)}
                                                        className="h-8 w-8 p-0"
                                                        title="Edit Data Kontak"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {orangTuas.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4">
                            <span className="text-xs text-muted-foreground">
                                Total {orangTuas.total} data orang tua (Halaman {orangTuas.current_page} dari {orangTuas.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!orangTuas.prev_page_url}
                                    onClick={() => orangTuas.prev_page_url && router.get(orangTuas.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!orangTuas.next_page_url}
                                    onClick={() => orangTuas.next_page_url && router.get(orangTuas.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Edit Orang Tua */}
            <Dialog open={!!editItem} onOpenChange={(open) => !open && setEditItem(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Data Orang Tua</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="fullname">Nama Lengkap Orang Tua</Label>
                            <Input
                                id="fullname"
                                value={editForm.data.fullname}
                                onChange={(e) => editForm.setData('fullname', e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="phone">Nomor Telepon / WhatsApp</Label>
                            <Input
                                id="phone"
                                value={editForm.data.phone}
                                placeholder="08xxxxxxxx"
                                onChange={(e) => editForm.setData('phone', e.target.value)}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="email">Email Pemulihan</Label>
                            <Input
                                id="email"
                                type="email"
                                value={editForm.data.email}
                                placeholder="ortu@email.com"
                                onChange={(e) => editForm.setData('email', e.target.value)}
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={editForm.data.is_active}
                                onChange={(e) => editForm.setData('is_active', e.target.checked)}
                                className="rounded border-input text-primary focus:ring-ring"
                            />
                            <Label htmlFor="is_active" className="text-xs cursor-pointer font-normal">
                                Akun aktif (dapat login ke sistem)
                            </Label>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setEditItem(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Password */}
            <Dialog open={!!resetItem} onOpenChange={(open) => !open && setResetItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ShieldAlert className="h-5 w-5 text-amber-500" />
                            Reset Kata Sandi
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2 text-sm text-muted-foreground pt-1">
                        <p>
                            Apakah Anda yakin ingin mereset kata sandi akun <strong className="text-foreground">{resetItem?.username}</strong>?
                        </p>
                        <p className="text-xs bg-muted p-2.5 rounded border border-border">
                            Kata sandi akan di-reset ke <code className="font-bold text-foreground">smkn1bantul2026</code> dan akun akan ditandai wajib melakukan aktivasi mandiri kembali saat login berikutnya.
                        </p>
                    </div>
                    <DialogFooter className="pt-2">
                        <Button variant="outline" onClick={() => setResetItem(null)}>
                            Batal
                        </Button>
                        <Button onClick={submitReset}>
                            Ya, Reset Sandi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
