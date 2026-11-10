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
    UserCheck,
    UserPlus,
    Search,
    Edit2,
    Trash2,
    KeyRound,
    AlertTriangle,
    ShieldCheck,
    FileSpreadsheet,
    Upload,
    Download
} from 'lucide-react';

interface GuruItem {
    id: number;
    role_id: number;
    username: string;
    fullname: string;
    email: string | null;
    phone: string | null;
    is_active: boolean;
    must_change_credentials: boolean;
    class_id?: number | null;
    school_class?: { id: number; name: string };
    homeroom_assignments?: { class_name: string }[];
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from?: number;
    to?: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface IndexProps {
    gurus: PaginatedData<GuruItem>;
    totalTeachers: number;
    classes?: { id: number; name: string; grade: string; jurusan: string }[];
    filters: {
        search?: string;
        per_page?: string;
    };
}

export default function GuruIndex({ gurus, totalTeachers, classes = [], filters }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(filters.per_page || '20');

    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editItem, setEditItem] = useState<GuruItem | null>(null);
    const [deleteItem, setDeleteItem] = useState<GuruItem | null>(null);
    const [resetItem, setResetItem] = useState<GuruItem | null>(null);
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [isResetAllOpen, setIsResetAllOpen] = useState(false);

    const importForm = useForm<{ file: File | null }>({
        file: null,
    });

    const resetAllForm = useForm({});
    const submitResetAll = () => {
        resetAllForm.delete('/waka/guru/reset-semua', {
            onSuccess: () => setIsResetAllOpen(false),
        });
    };

    const createForm = useForm({
        username: '',
        fullname: '',
        email: '',
        phone: '',
        role_id: '2', // 2: Guru
    });

    const editForm = useForm({
        fullname: '',
        email: '',
        phone: '',
        role_id: '2',
        class_id: '',
        is_active: true,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/guru', { search: searchQuery, per_page: perPage }, { preserveState: true });
    };

    const handlePerPageChange = (val: string) => {
        setPerPage(val);
        router.get('/waka/guru', { search: searchQuery, per_page: val }, { preserveState: true });
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/guru', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!importForm.data.file) return;
        importForm.post('/waka/guru/import', {
            onSuccess: () => {
                setIsImportOpen(false);
                importForm.reset();
            },
        });
    };

    const openEdit = (guru: GuruItem) => {
        setEditItem(guru);
        editForm.setData({
            fullname: guru.fullname,
            email: guru.email || '',
            phone: guru.phone || '',
            role_id: guru.role_id ? guru.role_id.toString() : '2',
            class_id: guru.class_id ? guru.class_id.toString() : (guru.school_class?.id ? guru.school_class.id.toString() : ''),
            is_active: guru.is_active,
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editItem) return;
        editForm.put(`/waka/guru/${editItem.id}`, {
            onSuccess: () => setEditItem(null),
        });
    };

    const submitDelete = () => {
        if (!deleteItem) return;
        router.delete(`/waka/guru/${deleteItem.id}`, {
            onSuccess: () => setDeleteItem(null),
        });
    };

    const submitReset = () => {
        if (!resetItem) return;
        router.post(`/waka/guru/${resetItem.id}/reset-password`, {}, {
            onSuccess: () => setResetItem(null),
        });
    };

    return (
        <AppLayout title="Data Master Tenaga Guru">
            <Head title="Data Guru" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Pengelolaan Tenaga Pendidik (Guru)
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Kelola data pendidik, jadwal mengajar, dan akun akses portal KBM.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setIsImportOpen(true)}
                                className="gap-2"
                            >
                                <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                                Import Berkas
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setIsResetAllOpen(true)}
                                disabled={gurus.total === 0}
                                className="gap-2 text-destructive border-destructive/30 hover:bg-destructive/10"
                            >
                                <Trash2 className="h-4 w-4" />
                                Reset Semua Guru
                            </Button>
                            <Button onClick={() => setIsCreateOpen(true)} className="gap-2">
                                <UserPlus className="h-4 w-4" />
                                Tambah Guru Baru
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="max-w-md relative">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama, NIP/username, atau email guru..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </form>
                    </CardContent>
                </Card>

                {/* Table Guru */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Username / NIP</TableHead>
                                    <TableHead>Nama Lengkap Guru</TableHead>
                                    <TableHead>Peran</TableHead>
                                    <TableHead>Kontak & Email</TableHead>
                                    <TableHead className="text-center">Status Akun</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {gurus.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data tenaga pendidik ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    gurus.data.map((guru, idx) => (
                                        <TableRow key={guru.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {(gurus.current_page - 1) * 20 + idx + 1}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs font-semibold">
                                                {guru.username}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {guru.fullname}
                                            </TableCell>
                                            <TableCell>
                                                {guru.role_id === 5 ? (
                                                    <Badge variant="default" className="text-xs bg-indigo-600 hover:bg-indigo-700">
                                                        Wali Kelas {guru.school_class?.name || (guru.homeroom_assignments?.[0]?.class_name ? `• ${guru.homeroom_assignments[0].class_name}` : '')}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-xs">
                                                        Guru Pengampu
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                <div>{guru.email || '-'}</div>
                                                {guru.phone && <div>HP: {guru.phone}</div>}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {!guru.is_active ? (
                                                    <Badge variant="destructive" className="text-xs">
                                                        Nonaktif
                                                    </Badge>
                                                ) : guru.must_change_credentials ? (
                                                    <Badge variant="outline" className="bg-amber-100/70 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-300 text-xs">
                                                        Perlu Aktivasi
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary" className="bg-emerald-100/70 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-300 text-xs">
                                                        Aktif
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setResetItem(guru)}
                                                        className="h-8 w-8 p-0"
                                                        title="Reset Password ke Default"
                                                    >
                                                        <KeyRound className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEdit(guru)}
                                                        className="h-8 w-8 p-0"
                                                        title="Edit Guru"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setDeleteItem(guru)}
                                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                        title="Hapus Guru"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination Bar */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-border px-6 py-4">
                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                            <span>
                                Menampilkan {gurus.from || 0} - {gurus.to || 0} dari {gurus.total} tenaga pendidik
                            </span>
                            <div className="flex items-center gap-1.5">
                                <span>Tampilkan:</span>
                                <select
                                    value={perPage}
                                    onChange={(e) => handlePerPageChange(e.target.value)}
                                    className="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="20">20 / hal</option>
                                    <option value="50">50 / hal</option>
                                    <option value="100">100 / hal</option>
                                    <option value="all">Semua ({gurus.total})</option>
                                </select>
                            </div>
                        </div>

                        {gurus.last_page > 1 && (
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!gurus.prev_page_url}
                                    onClick={() => gurus.prev_page_url && router.get(gurus.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <span className="text-xs text-muted-foreground px-2">
                                    Halaman {gurus.current_page} dari {gurus.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!gurus.next_page_url}
                                    onClick={() => gurus.next_page_url && router.get(gurus.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        )}
                    </div>
                </Card>
            </div>

            {/* Modal Tambah Guru */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Tambah Tenaga Pendidik Baru</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="guru_username">Username / NIP *</Label>
                            <Input
                                id="guru_username"
                                value={createForm.data.username}
                                onChange={(e) => createForm.setData('username', e.target.value)}
                                placeholder="Contoh: 198501012010011001 atau budi_rpl"
                                required
                            />
                            {createForm.errors.username && (
                                <p className="text-xs text-destructive">{createForm.errors.username}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="guru_fullname">Nama Lengkap & Gelar *</Label>
                            <Input
                                id="guru_fullname"
                                value={createForm.data.fullname}
                                onChange={(e) => createForm.setData('fullname', e.target.value)}
                                placeholder="Contoh: Budi Santoso, S.Kom"
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="guru_role">Penugasan Peran *</Label>
                            <select
                                id="guru_role"
                                value={createForm.data.role_id}
                                onChange={(e) => createForm.setData('role_id', e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="2">Guru Pengampu</option>
                                <option value="5">Wali Kelas</option>
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="guru_phone">No. WhatsApp</Label>
                                <Input
                                    id="guru_phone"
                                    value={createForm.data.phone}
                                    onChange={(e) => createForm.setData('phone', e.target.value)}
                                    placeholder="08123456789"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="guru_email">Email Resmi</Label>
                                <Input
                                    id="guru_email"
                                    type="email"
                                    value={createForm.data.email}
                                    onChange={(e) => createForm.setData('email', e.target.value)}
                                    placeholder="guru@smkn1bantul.sch.id"
                                />
                            </div>
                        </div>

                        <div className="rounded-lg border border-border bg-muted/30 p-3 text-xs text-muted-foreground">
                            Akun login akan otomatis aktif dengan kata sandi bawaan <code className="font-semibold text-foreground">smkn1bantul2026</code>. Guru akan diminta mengganti kredensial saat pertama kali masuk.
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                Daftarkan Guru
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Guru */}
            <Dialog open={!!editItem} onOpenChange={(open) => !open && setEditItem(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Data Guru: {editItem?.fullname}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_guru_fullname">Nama Lengkap & Gelar *</Label>
                            <Input
                                id="edit_guru_fullname"
                                value={editForm.data.fullname}
                                onChange={(e) => editForm.setData('fullname', e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_guru_phone">No. HP / WhatsApp</Label>
                            <Input
                                id="edit_guru_phone"
                                value={editForm.data.phone}
                                onChange={(e) => editForm.setData('phone', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_guru_email">Email</Label>
                            <Input
                                id="edit_guru_email"
                                type="email"
                                value={editForm.data.email}
                                onChange={(e) => editForm.setData('email', e.target.value)}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_guru_role">Penugasan / Peran</Label>
                                <select
                                    id="edit_guru_role"
                                    value={editForm.data.role_id}
                                    onChange={(e) => editForm.setData('role_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm"
                                >
                                    <option value="2">Guru Pengampu</option>
                                    <option value="5">Wali Kelas</option>
                                </select>
                            </div>
                            {editForm.data.role_id === '5' && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_guru_class">Kelas Binaan</Label>
                                    <select
                                        id="edit_guru_class"
                                        value={editForm.data.class_id}
                                        onChange={(e) => editForm.setData('class_id', e.target.value)}
                                        className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm"
                                    >
                                        <option value="">-- Pilih Kelas --</option>
                                        {classes?.map((c) => (
                                            <option key={c.id} value={c.id.toString()}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                        </div>
                        <DialogFooter>
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

            {/* Modal Reset Password Confirmation */}
            <Dialog open={!!resetItem} onOpenChange={(open) => !open && setResetItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <KeyRound className="h-5 w-5" />
                            Reset Sandi Guru
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Setel ulang kata sandi akun <strong className="text-foreground">{resetItem?.fullname}</strong> ke default: <code className="font-bold text-foreground">smkn1bantul2026</code>?
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetItem(null)}>
                            Batal
                        </Button>
                        <Button onClick={submitReset}>
                            Ya, Reset Sandi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Delete Guru */}
            <Dialog open={!!deleteItem} onOpenChange={(open) => !open && setDeleteItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Konfirmasi Hapus Guru
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus data pendidik <strong className="text-foreground">{deleteItem?.fullname}</strong>? Akun login yang tertaut akan terhapus.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteItem(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={submitDelete}>
                            Ya, Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Import Excel / PDF / Markdown Guru */}
            <Dialog open={isImportOpen} onOpenChange={setIsImportOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                            Import Data Guru (Excel / PDF / Markdown)
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitImport} className="space-y-4">
                        <p className="text-xs text-muted-foreground">
                            Unggah berkas Excel (.xlsx, .xls), dokumen PDF jadwal FET, atau berkas tabel Markdown (.md). Sistem otomatis menstandarkan username <code className="font-semibold text-foreground">guru.xx</code> dan mengatur status Perlu Aktivasi.
                        </p>

                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            <a href="/waka/guru/master-md" className="inline-flex items-center gap-1 font-semibold text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Master Markdown (.md)
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a href="/waka/guru/template" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Template Excel
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a href="/waka/guru/contoh" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Contoh 5 Guru
                            </a>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="import-guru-file" className="text-xs">Pilih Berkas (.xlsx, .xls, .pdf, .md)</Label>
                            <Input
                                id="import-guru-file"
                                type="file"
                                accept=".xlsx,.xls,.pdf,.md,.txt"
                                onChange={(e) => importForm.setData('file', e.target.files ? e.target.files[0] : null)}
                                required
                            />
                            {importForm.errors.file && (
                                <p className="text-xs text-destructive">{importForm.errors.file}</p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsImportOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={importForm.processing || !importForm.data.file} className="gap-2">
                                <Upload className="h-4 w-4" />
                                Mulai Import Guru
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Semua Guru */}
            <Dialog open={isResetAllOpen} onOpenChange={setIsResetAllOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Reset Seluruh Data Tenaga Guru
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm text-muted-foreground pt-1">
                        <p>
                            Apakah Anda yakin ingin mengosongkan seluruh data guru ({gurus.total} guru) secara keseluruhan dari 0?
                        </p>
                        <p className="text-xs bg-muted p-3 rounded-lg border border-border">
                            Tindakan ini akan menghapus akun pendidik non-admin dan penugasan wali kelas untuk persiapan tahun ajaran atau angkatan baru. Akun Waka/Administrator login tidak akan terhapus. Setelah data dikosongkan, Anda dapat langsung mengimpor berkas data guru baru.
                        </p>
                    </div>
                    <DialogFooter className="pt-2">
                        <Button type="button" variant="outline" onClick={() => setIsResetAllOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={submitResetAll}
                            disabled={resetAllForm.processing}
                        >
                            {resetAllForm.processing ? 'Mengosongkan...' : 'Ya, Kosongkan Seluruh Data Guru'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

