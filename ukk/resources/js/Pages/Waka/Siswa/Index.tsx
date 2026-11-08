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
    UserPlus,
    ArrowUpCircle,
    Search,
    Edit2,
    Trash2,
    AlertTriangle,
    GraduationCap,
    CheckCircle2,
    FileSpreadsheet,
    Upload,
    Download,
    AlertCircle
} from 'lucide-react';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
    jurusan: string;
    angkatan: number;
    gender: 'L' | 'P';
    alamat: string | null;
    phone: string | null;
    email: string | null;
    status: 'aktif' | 'lulus' | 'mutasi' | 'keluar';
    user?: {
        username: string;
        is_active: boolean;
        must_change_credentials: boolean;
    };
    parent_user?: {
        username: string;
        is_active: boolean;
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
    siswas: PaginatedData<SiswaItem>;
    filters: {
        search?: string;
        kelas?: string;
        status?: string;
        angkatan?: string;
    };
    kelasList: string[];
    angkatanList: number[];
}

export default function SiswaIndex({ siswas, filters, kelasList, angkatanList }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedKelas, setSelectedKelas] = useState(filters.kelas || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'aktif');
    const [selectedAngkatan, setSelectedAngkatan] = useState(filters.angkatan || 'all');

    // Modal Create / Edit
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editItem, setEditItem] = useState<SiswaItem | null>(null);

    // Modal Naik Kelas / Kelulusan
    const [isNaikKelasOpen, setIsNaikKelasOpen] = useState(false);

    // Modal Delete
    const [deleteItem, setDeleteItem] = useState<SiswaItem | null>(null);

    // Modal Import Excel & Hapus per Kelas
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [isHapusKelasOpen, setIsHapusKelasOpen] = useState(false);

    const importForm = useForm<{ file: File | null }>({
        file: null,
    });

    const hapusKelasForm = useForm({
        kelas: kelasList[0] || '',
        force: false,
    });

    // Form Tambah Siswa
    const createForm = useForm({
        nis: '',
        name: '',
        kelas: kelasList[0] || 'X RPL 1',
        jurusan: 'RPL',
        angkatan: new Date().getFullYear(),
        gender: 'L',
        alamat: '',
        phone: '',
        email: '',
    });

    // Form Edit Siswa
    const editForm = useForm({
        name: '',
        kelas: '',
        jurusan: 'RPL',
        angkatan: new Date().getFullYear(),
        gender: 'L',
        alamat: '',
        phone: '',
        email: '',
        status: 'aktif',
    });

    // Form Naik Kelas / Kelulusan
    const naikKelasForm = useForm({
        angkatan: angkatanList[0] || new Date().getFullYear(),
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/siswa', {
            search: searchQuery,
            kelas: selectedKelas,
            status: selectedStatus,
            angkatan: selectedAngkatan,
        }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        const nextFilters = {
            search: searchQuery,
            kelas: selectedKelas,
            status: selectedStatus,
            angkatan: selectedAngkatan,
            [key]: value,
        };

        if (key === 'kelas') setSelectedKelas(value);
        if (key === 'status') setSelectedStatus(value);
        if (key === 'angkatan') setSelectedAngkatan(value);

        router.get('/waka/siswa', nextFilters, { preserveState: true });
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/siswa', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const openEdit = (siswa: SiswaItem) => {
        setEditItem(siswa);
        editForm.setData({
            name: siswa.name,
            kelas: siswa.kelas,
            jurusan: siswa.jurusan || 'RPL',
            angkatan: siswa.angkatan,
            gender: siswa.gender,
            alamat: siswa.alamat || '',
            phone: siswa.phone || '',
            email: siswa.email || '',
            status: siswa.status,
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editItem) return;
        editForm.put(`/waka/siswa/${editItem.id}`, {
            onSuccess: () => {
                setEditItem(null);
            },
        });
    };

    const submitDelete = () => {
        if (!deleteItem) return;
        router.delete(`/waka/siswa/${deleteItem.id}`, {
            onSuccess: () => setDeleteItem(null),
        });
    };

    const submitNaikKelas = (e: React.FormEvent) => {
        e.preventDefault();
        naikKelasForm.post('/waka/siswa/naik-kelas', {
            onSuccess: () => {
                setIsNaikKelasOpen(false);
            },
        });
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!importForm.data.file) return;
        importForm.post('/waka/siswa/import', {
            onSuccess: () => {
                setIsImportOpen(false);
                importForm.reset();
            },
        });
    };

    const submitHapusKelas = (e: React.FormEvent) => {
        e.preventDefault();
        hapusKelasForm.delete('/waka/siswa/hapus-per-kelas', {
            onSuccess: () => {
                setIsHapusKelasOpen(false);
            },
        });
    };

    return (
        <AppLayout title="Data Master Siswa & Kelas">
            <Head title="Data Siswa" />

            <div className="space-y-6">
                {/* Header Actions Card */}
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Pengelolaan Siswa & Kenaikan Kelas
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Kelola data murid, akun login, kenaikan kelas massal, dan alur kelulusan ke data alumni.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            {/* Tombol Import Excel */}
                            <Button
                                variant="outline"
                                onClick={() => setIsImportOpen(true)}
                                className="gap-2"
                            >
                                <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                                Import Excel
                            </Button>

                            {/* Tombol Hapus per Kelas */}
                            <Button
                                variant="outline"
                                onClick={() => setIsHapusKelasOpen(true)}
                                className="gap-2 text-destructive border-destructive/30 hover:bg-destructive/10"
                            >
                                <Trash2 className="h-4 w-4" />
                                Hapus per Kelas
                            </Button>

                            {/* Tombol Kenaikan Kelas & Kelulusan per Angkatan */}
                            <Button
                                variant="outline"
                                onClick={() => setIsNaikKelasOpen(true)}
                                className="gap-2"
                            >
                                <ArrowUpCircle className="h-4 w-4 text-emerald-600" />
                                Naik Kelas / Kelulusan
                            </Button>

                            {/* Tombol Tambah Siswa */}
                            <Button
                                onClick={() => setIsCreateOpen(true)}
                                className="gap-2"
                            >
                                <UserPlus className="h-4 w-4" />
                                Tambah Siswa Baru
                            </Button>
                        </div>
                    </CardHeader>

                    {/* Filter & Search Bar */}
                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                            <div className="md:col-span-2 relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama atau NIS siswa..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>

                            <div>
                                <select
                                    value={selectedKelas}
                                    onChange={(e) => handleFilterChange('kelas', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="all">Semua Kelas</option>
                                    {kelasList.map((k) => (
                                        <option key={k} value={k}>{k}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <select
                                    value={selectedAngkatan}
                                    onChange={(e) => handleFilterChange('angkatan', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="all">Semua Angkatan</option>
                                    {angkatanList.map((a) => (
                                        <option key={a} value={a}>Angkatan {a}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="lulus">Lulus (Alumni)</option>
                                    <option value="mutasi">Mutasi</option>
                                    <option value="keluar">Keluar</option>
                                </select>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table Siswa */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead>Nama Siswa</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Angkatan</TableHead>
                                    <TableHead className="text-center">Gender</TableHead>
                                    <TableHead className="text-center">Status</TableHead>
                                    <TableHead>Akun Siswa / Ortu</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {siswas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data siswa ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    siswas.data.map((siswa, idx) => (
                                        <TableRow key={siswa.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {(siswas.current_page - 1) * 20 + idx + 1}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs font-semibold">
                                                {siswa.nis}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {siswa.name}
                                                {siswa.phone && (
                                                    <span className="block text-xs text-muted-foreground font-normal">
                                                        HP: {siswa.phone}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="font-medium">
                                                    {siswa.kelas}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {siswa.angkatan}
                                            </TableCell>
                                            <TableCell className="text-center text-xs">
                                                {siswa.gender === 'L' ? 'Laki-laki' : 'Perempuan'}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge
                                                    variant={
                                                        siswa.status === 'aktif'
                                                            ? 'default'
                                                            : siswa.status === 'lulus'
                                                            ? 'secondary'
                                                            : 'destructive'
                                                    }
                                                    className="capitalize"
                                                >
                                                    {siswa.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1 text-xs">
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="text-muted-foreground">Siswa:</span>
                                                        <code className="bg-muted px-1 py-0.5 rounded font-mono text-[11px]">
                                                            {siswa.nis}
                                                        </code>
                                                    </div>
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="text-muted-foreground">Ortu:</span>
                                                        <code className="bg-muted px-1 py-0.5 rounded font-mono text-[11px]">
                                                            ortu_{siswa.nis}
                                                        </code>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEdit(siswa)}
                                                        className="h-8 w-8 p-0"
                                                        title="Edit Siswa"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setDeleteItem(siswa)}
                                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                        title="Hapus Siswa"
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

                    {/* Pagination */}
                    {siswas.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4">
                            <span className="text-xs text-muted-foreground">
                                Total {siswas.total} siswa (Halaman {siswas.current_page} dari {siswas.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!siswas.prev_page_url}
                                    onClick={() => siswas.prev_page_url && router.get(siswas.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!siswas.next_page_url}
                                    onClick={() => siswas.next_page_url && router.get(siswas.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Tambah Siswa */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Tambah Data Siswa Baru</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="nis">NIS (Nomor Induk Siswa) *</Label>
                                <Input
                                    id="nis"
                                    value={createForm.data.nis}
                                    onChange={(e) => createForm.setData('nis', e.target.value)}
                                    placeholder="Contoh: 202611005"
                                    required
                                />
                                {createForm.errors.nis && (
                                    <p className="text-xs text-destructive">{createForm.errors.nis}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="angkatan">Tahun Angkatan *</Label>
                                <Input
                                    id="angkatan"
                                    type="number"
                                    value={createForm.data.angkatan}
                                    onChange={(e) => createForm.setData('angkatan', parseInt(e.target.value) || 2026)}
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="name">Nama Lengkap Siswa *</Label>
                            <Input
                                id="name"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                placeholder="Nama lengkap siswa"
                                required
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-destructive">{createForm.errors.name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="kelas">Kelas *</Label>
                                <select
                                    id="kelas"
                                    value={createForm.data.kelas}
                                    onChange={(e) => createForm.setData('kelas', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {kelasList.map((k) => (
                                        <option key={k} value={k}>{k}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="gender">Jenis Kelamin *</Label>
                                <select
                                    id="gender"
                                    value={createForm.data.gender}
                                    onChange={(e) => createForm.setData('gender', e.target.value as 'L' | 'P')}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="L">Laki-laki (L)</option>
                                    <option value="P">Perempuan (P)</option>
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="phone">Nomor Telepon / WhatsApp</Label>
                                <Input
                                    id="phone"
                                    value={createForm.data.phone}
                                    onChange={(e) => createForm.setData('phone', e.target.value)}
                                    placeholder="08123456789"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="email">Email Siswa</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={createForm.data.email}
                                    onChange={(e) => createForm.setData('email', e.target.value)}
                                    placeholder="siswa@sekolah.sch.id"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="alamat">Alamat Tinggal</Label>
                            <Input
                                id="alamat"
                                value={createForm.data.alamat}
                                onChange={(e) => createForm.setData('alamat', e.target.value)}
                                placeholder="Alamat domisili siswa"
                            />
                        </div>

                        <div className="rounded-lg border border-border bg-muted/30 p-3 text-xs text-muted-foreground">
                            Akun login siswa (<code className="font-semibold text-foreground">NIS</code>) dan orang tua (<code className="font-semibold text-foreground">ortu_NIS</code>) akan otomatis dibuat dengan kata sandi awal <code className="font-semibold text-foreground">smkn1bantul2026</code>.
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                Simpan Siswa & Buat Akun
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Siswa */}
            <Dialog open={!!editItem} onOpenChange={(open) => !open && setEditItem(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit Data Siswa: {editItem?.name}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_name">Nama Lengkap Siswa *</Label>
                            <Input
                                id="edit_name"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_kelas">Kelas *</Label>
                                <select
                                    id="edit_kelas"
                                    value={editForm.data.kelas}
                                    onChange={(e) => editForm.setData('kelas', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {kelasList.map((k) => (
                                        <option key={k} value={k}>{k}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_status">Status Siswa *</Label>
                                <select
                                    id="edit_status"
                                    value={editForm.data.status}
                                    onChange={(e) => editForm.setData('status', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="lulus">Lulus</option>
                                    <option value="mutasi">Mutasi</option>
                                    <option value="keluar">Keluar</option>
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_phone">No. Telepon / WhatsApp</Label>
                                <Input
                                    id="edit_phone"
                                    value={editForm.data.phone}
                                    onChange={(e) => editForm.setData('phone', e.target.value)}
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_email">Email</Label>
                                <Input
                                    id="edit_email"
                                    value={editForm.data.email}
                                    onChange={(e) => editForm.setData('email', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_alamat">Alamat Tinggal</Label>
                            <Input
                                id="edit_alamat"
                                value={editForm.data.alamat}
                                onChange={(e) => editForm.setData('alamat', e.target.value)}
                            />
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

            {/* Modal Naik Kelas per Angkatan & Naik Kelulusan untuk Kelas XII (Revisi Fitur Waka #2) */}
            <Dialog open={isNaikKelasOpen} onOpenChange={setIsNaikKelasOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ArrowUpCircle className="h-5 w-5 text-emerald-600" />
                            Kenaikan Kelas & Kelulusan per Angkatan
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitNaikKelas} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="angkatan_naik">Pilih Angkatan yang Akan Diproses:</Label>
                            <select
                                id="angkatan_naik"
                                value={naikKelasForm.data.angkatan}
                                onChange={(e) => naikKelasForm.setData('angkatan', parseInt(e.target.value))}
                                className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm"
                            >
                                {angkatanList.map((a) => (
                                    <option key={a} value={a}>Angkatan {a}</option>
                                ))}
                            </select>
                        </div>

                        {/* Aturan Spesifik UKK */}
                        <div className="rounded-lg border border-border bg-muted/40 p-3 space-y-2 text-xs">
                            <span className="font-semibold text-foreground block">
                                Mekanisme Kenaikan Kelas & Naik Kelulusan:
                            </span>
                            <ul className="list-disc pl-4 space-y-1 text-muted-foreground">
                                <li>
                                    <strong className="text-foreground">Kelas X</strong> naik ke <strong className="text-foreground">Kelas XI</strong> (misal X RPL 1 &rarr; XI RPL 1).
                                </li>
                                <li>
                                    <strong className="text-foreground">Kelas XI</strong> naik ke <strong className="text-foreground">Kelas XII</strong> (misal XI RPL 1 &rarr; XII RPL 1).
                                </li>
                                <li>
                                    <strong className="text-foreground">Khusus Kelas XII</strong>: Bukan naik kelas, melainkan <span className="font-bold text-foreground">Naik Kelulusan</span>.
                                    <ul className="list-circle pl-4 mt-0.5 space-y-0.5">
                                        <li>Status siswa diubah menjadi <strong>Lulus</strong>.</li>
                                        <li>Data disalin otomatis ke <strong>Arsip Data Alumni</strong>.</li>
                                        <li>Akun login siswa dinonaktifkan secara otomatis.</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsNaikKelasOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={naikKelasForm.processing} className="gap-2">
                                <CheckCircle2 className="h-4 w-4" />
                                Proses Kenaikan & Kelulusan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Delete Confirmation */}
            <Dialog open={!!deleteItem} onOpenChange={(open) => !open && setDeleteItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Konfirmasi Hapus Siswa
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus data siswa <strong className="text-foreground">{deleteItem?.name}</strong> (NIS: {deleteItem?.nis})? Akun login siswa dan orang tua yang tertaut juga akan dihapus.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteItem(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={submitDelete}>
                            Ya, Hapus Data
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Import Excel */}
            <Dialog open={isImportOpen} onOpenChange={setIsImportOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                            Import Data Siswa dari Excel
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitImport} className="space-y-4">
                        <p className="text-xs text-muted-foreground">
                            Unggah berkas spreadsheet berisi data siswa (.xlsx, .xls, .csv). Format mendukung template baku maupun buku absensi daftar hadir.
                        </p>

                        <div className="flex items-center gap-2 text-xs">
                            <a href="/waka/siswa/template" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Download Template Kosong
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a href="/waka/siswa/contoh" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Download Contoh 5 Siswa
                            </a>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="import-file" className="text-xs">Pilih Berkas Excel/CSV</Label>
                            <Input
                                id="import-file"
                                type="file"
                                accept=".xlsx,.xls,.csv,.txt"
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
                                Mulai Import Siswa
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Hapus per Kelas */}
            <Dialog open={isHapusKelasOpen} onOpenChange={setIsHapusKelasOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <Trash2 className="h-5 w-5" />
                            Hapus Data Siswa per Kelas
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitHapusKelas} className="space-y-4">
                        <p className="text-xs text-muted-foreground">
                            Hapus massal data siswa untuk satu rombongan belajar tertentu. Berguna untuk pembersihan tahunan atau rotasi kelas.
                        </p>

                        <div className="space-y-1.5">
                            <Label htmlFor="hapus-kelas-select" className="text-xs">Pilih Kelas yang Ingin Dikosongkan</Label>
                            <select
                                id="hapus-kelas-select"
                                value={hapusKelasForm.data.kelas}
                                onChange={(e) => hapusKelasForm.setData('kelas', e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm"
                                required
                            >
                                <option value="">-- Pilih Kelas --</option>
                                <option value="ALL" className="font-semibold text-destructive">-- SEMUA KELAS (Kosongkan Seluruh Siswa) --</option>
                                {kelasList.map((k) => (
                                    <option key={k} value={k}>{k}</option>
                                ))}
                            </select>
                            {hapusKelasForm.errors.kelas && (
                                <p className="text-xs text-destructive">{hapusKelasForm.errors.kelas}</p>
                            )}
                        </div>

                        <div className="flex items-start gap-2 pt-2 border-t">
                            <input
                                id="force-delete-checkbox"
                                type="checkbox"
                                checked={hapusKelasForm.data.force}
                                onChange={(e) => hapusKelasForm.setData('force', e.target.checked)}
                                className="mt-1 rounded border-input"
                            />
                            <Label htmlFor="force-delete-checkbox" className="text-xs font-normal leading-relaxed text-muted-foreground">
                                Hapus beserta data terkait (akun login siswa & orang tua, riwayat presensi, dan izin yang tertaut).
                            </Label>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsHapusKelasOpen(false)}>
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={hapusKelasForm.processing || !hapusKelasForm.data.kelas}
                                className="gap-2"
                            >
                                <Trash2 className="h-4 w-4" />
                                Hapus Siswa Kelas
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

