import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
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
    Plus,
    Clock,
    Edit,
    Trash2,
    FileSpreadsheet,
    Download,
    Upload,
    AlertTriangle,
} from 'lucide-react';

interface TimeSlot {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_break: boolean;
    order: number;
}

interface Props {
    timeSlots: TimeSlot[];
    activeCycle?: number;
}

export default function JamPelajaranIndex({ timeSlots, activeCycle = 1 }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [isResetAllOpen, setIsResetAllOpen] = useState(false);
    const [editingSlot, setEditingSlot] = useState<TimeSlot | null>(null);
    const [deletingSlot, setDeletingSlot] = useState<TimeSlot | null>(null);

    // Form Tambah
    const createForm = useForm({
        name: '',
        start_time: '',
        end_time: '',
        is_break: false,
        order: (timeSlots.length + 1) || 1,
    });

    // Form Edit
    const editForm = useForm({
        name: '',
        start_time: '',
        end_time: '',
        is_break: false,
        order: 1,
    });

    // Form Hapus 1 Slot
    const deleteForm = useForm({});

    // Form Import
    const importForm = useForm<{ file: File | null; replace_existing: boolean }>({
        file: null,
        replace_existing: false,
    });

    // Form Reset Semua
    const resetAllForm = useForm({});

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/jam-pelajaran', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    const handleEditOpen = (slot: TimeSlot) => {
        setEditingSlot(slot);
        editForm.setData({
            name: slot.name,
            start_time: slot.start_time.substring(0, 5),
            end_time: slot.end_time.substring(0, 5),
            is_break: slot.is_break,
            order: slot.order,
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingSlot) return;
        editForm.put(`/waka/jam-pelajaran/${editingSlot.id}`, {
            onSuccess: () => {
                setEditingSlot(null);
            },
        });
    };

    const handleDeleteSubmit = () => {
        if (!deletingSlot) return;
        deleteForm.delete(`/waka/jam-pelajaran/${deletingSlot.id}`, {
            onSuccess: () => setDeletingSlot(null),
        });
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        importForm.post('/waka/jam-pelajaran/import', {
            onSuccess: () => {
                setIsImportOpen(false);
                importForm.reset();
            },
        });
    };

    const submitResetAll = () => {
        resetAllForm.delete('/waka/jam-pelajaran/reset-semua', {
            onSuccess: () => setIsResetAllOpen(false),
        });
    };

    const formatTime = (timeStr: string) => {
        return timeStr ? timeStr.substring(0, 5) : '';
    };

    return (
        <AppLayout title="Jam Pembelajaran Sekolah">
            <Head title="Master Jam Pembelajaran" />

            <div className="space-y-6">
                {/* Header Action Row */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight">Master Jam Pelajaran (Putaran {activeCycle})</h2>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Konfigurasi struktur durasi jam belajar dan waktu istirahat resmi sekolah SMK N 1 Bantul (Siklus Aktif: Putaran {activeCycle}).
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setIsImportOpen(true)}
                            className="gap-2"
                        >
                            <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                            Import Jam Pelajaran
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => setIsResetAllOpen(true)}
                            className="gap-2 text-destructive border-destructive/30 hover:bg-destructive/10"
                        >
                            <Trash2 className="h-4 w-4" />
                            Reset Semua Jam Pelajaran
                        </Button>

                        <Button onClick={() => setIsCreateOpen(true)} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Tambah Jam Pelajaran
                        </Button>
                    </div>
                </div>

                {/* Table Card (Pure Raw Shadcn Table) */}
                <Card>
                    <CardHeader className="py-4">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <Clock className="h-4 w-4 text-muted-foreground" /> Struktur Sesi Jam Belajar
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[80px]">Urutan</TableHead>
                                    <TableHead>Nama Jam / Sesi</TableHead>
                                    <TableHead>Waktu Pelaksanaan</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead className="text-right w-[120px]">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {timeSlots.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                                            Belum ada jam pembelajaran yang dikonfigurasi. Klik tombol tambah atau import berkas Excel di atas.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    timeSlots.map((slot) => (
                                        <TableRow key={slot.id}>
                                            <TableCell className="font-semibold text-muted-foreground">
                                                #{slot.order}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {slot.name}
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-mono text-xs">
                                                    {formatTime(slot.start_time)} - {formatTime(slot.end_time)} WIB
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {slot.is_break ? (
                                                    <Badge variant="outline" className="border-border bg-muted">
                                                        Istirahat
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        Jam KBM
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleEditOpen(slot)}
                                                        title="Edit"
                                                    >
                                                        <Edit className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => setDeletingSlot(slot)}
                                                        className="hover:text-destructive"
                                                        title="Hapus"
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
                    </CardContent>
                </Card>
            </div>

            {/* Modal Tambah Jam */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Jam Pembelajaran</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-4 pt-2">
                        <div className="space-y-2">
                            <Label htmlFor="create_name">Nama Jam / Sesi *</Label>
                            <Input
                                id="create_name"
                                placeholder="Contoh: Jam Ke-1 atau Istirahat Pagi"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                required
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-destructive">{createForm.errors.name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="create_start">Jam Mulai (WIB) *</Label>
                                <Input
                                    id="create_start"
                                    type="time"
                                    value={createForm.data.start_time}
                                    onChange={(e) => createForm.setData('start_time', e.target.value)}
                                    required
                                />
                                {createForm.errors.start_time && (
                                    <p className="text-xs text-destructive">{createForm.errors.start_time}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="create_end">Jam Selesai (WIB) *</Label>
                                <Input
                                    id="create_end"
                                    type="time"
                                    value={createForm.data.end_time}
                                    onChange={(e) => createForm.setData('end_time', e.target.value)}
                                    required
                                />
                                {createForm.errors.end_time && (
                                    <p className="text-xs text-destructive">{createForm.errors.end_time}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 items-center">
                            <div className="space-y-2">
                                <Label htmlFor="create_order">Nomor Urutan *</Label>
                                <Input
                                    id="create_order"
                                    type="number"
                                    min="1"
                                    value={createForm.data.order}
                                    onChange={(e) => createForm.setData('order', parseInt(e.target.value) || 1)}
                                    required
                                />
                            </div>

                            <div className="flex items-center space-x-2 pt-6">
                                <label className="flex items-center space-x-2 cursor-pointer text-sm">
                                    <input
                                        type="checkbox"
                                        checked={createForm.data.is_break}
                                        onChange={(e) => createForm.setData('is_break', e.target.checked)}
                                        className="rounded border-border text-primary focus:ring-primary h-4 w-4"
                                    />
                                    <span>Tandai sebagai Istirahat</span>
                                </label>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Jam'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Jam */}
            <Dialog open={editingSlot !== null} onOpenChange={(open) => !open && setEditingSlot(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Jam Pembelajaran</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleEditSubmit} className="space-y-4 pt-2">
                        <div className="space-y-2">
                            <Label htmlFor="edit_name">Nama Jam / Sesi *</Label>
                            <Input
                                id="edit_name"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                required
                            />
                            {editForm.errors.name && (
                                <p className="text-xs text-destructive">{editForm.errors.name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="edit_start">Jam Mulai (WIB) *</Label>
                                <Input
                                    id="edit_start"
                                    type="time"
                                    value={editForm.data.start_time}
                                    onChange={(e) => editForm.setData('start_time', e.target.value)}
                                    required
                                />
                                {editForm.errors.start_time && (
                                    <p className="text-xs text-destructive">{editForm.errors.start_time}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit_end">Jam Selesai (WIB) *</Label>
                                <Input
                                    id="edit_end"
                                    type="time"
                                    value={editForm.data.end_time}
                                    onChange={(e) => editForm.setData('end_time', e.target.value)}
                                    required
                                />
                                {editForm.errors.end_time && (
                                    <p className="text-xs text-destructive">{editForm.errors.end_time}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 items-center">
                            <div className="space-y-2">
                                <Label htmlFor="edit_order">Nomor Urutan *</Label>
                                <Input
                                    id="edit_order"
                                    type="number"
                                    min="1"
                                    value={editForm.data.order}
                                    onChange={(e) => editForm.setData('order', parseInt(e.target.value) || 1)}
                                    required
                                />
                            </div>

                            <div className="flex items-center space-x-2 pt-6">
                                <label className="flex items-center space-x-2 cursor-pointer text-sm">
                                    <input
                                        type="checkbox"
                                        checked={editForm.data.is_break}
                                        onChange={(e) => editForm.setData('is_break', e.target.checked)}
                                        className="rounded border-border text-primary focus:ring-primary h-4 w-4"
                                    />
                                    <span>Tandai sebagai Istirahat</span>
                                </label>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setEditingSlot(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui Jam'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Import Jam Pembelajaran */}
            <Dialog open={isImportOpen} onOpenChange={setIsImportOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                            Import Master Jam Pembelajaran (Excel / CSV)
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submitImport} className="space-y-4">
                        <p className="text-xs text-muted-foreground">
                            Unggah berkas spreadsheet (.xlsx, .xls, .csv). Kolom yang dibutuhkan: Urutan, Nama Jam / Sesi, Jam Mulai, Jam Selesai, dan Tipe (Jam KBM / Istirahat).
                        </p>

                        <div className="flex items-center gap-2 text-xs">
                            <a
                                href="/waka/jam-pelajaran/template"
                                className="inline-flex items-center gap-1 text-primary hover:underline"
                            >
                                <Download className="h-3.5 w-3.5" />
                                Download Template Kosong
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a
                                href="/waka/jam-pelajaran/contoh"
                                className="inline-flex items-center gap-1 text-primary hover:underline"
                            >
                                <Download className="h-3.5 w-3.5" />
                                Download Contoh 7 Sesi
                            </a>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="import-jam-file" className="text-xs">Pilih Berkas Excel/CSV</Label>
                            <Input
                                id="import-jam-file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) => importForm.setData('file', e.target.files ? e.target.files[0] : null)}
                                required
                            />
                            {importForm.errors.file && (
                                <p className="text-xs text-destructive">{importForm.errors.file}</p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-1">
                            <input
                                type="checkbox"
                                id="replace_existing_jam"
                                checked={importForm.data.replace_existing}
                                onChange={(e) => importForm.setData('replace_existing', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <label htmlFor="replace_existing_jam" className="text-xs text-muted-foreground cursor-pointer select-none">
                                Kosongkan master jam pembelajaran lama sebelum mengimpor (Replace All)
                            </label>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsImportOpen(false)}>
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={importForm.processing || !importForm.data.file}
                                className="gap-2"
                            >
                                <Upload className="h-4 w-4" />
                                {importForm.processing ? 'Mengimpor...' : 'Mulai Import Jam'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Semua Jam Pembelajaran */}
            <Dialog open={isResetAllOpen} onOpenChange={setIsResetAllOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Reset Seluruh Master Jam Pembelajaran
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm text-muted-foreground pt-1">
                        <p>
                            Apakah Anda yakin ingin mengosongkan seluruh data master jam pembelajaran ({timeSlots.length} sesi) secara keseluruhan dari 0?
                        </p>
                        <p className="text-xs bg-muted p-3 rounded-lg border border-border">
                            Tindakan ini ditujukan untuk persiapan tahun ajaran atau kurikulum baru. Menghapus sesi jam pembelajaran ini <strong>tidak akan menghapus data jadwal pelajaran siswa</strong> yang sudah terjadwal.
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
                            {resetAllForm.processing ? 'Mengosongkan...' : 'Ya, Kosongkan Seluruh Jam'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Hapus 1 Slot */}
            <Dialog open={deletingSlot !== null} onOpenChange={(open) => !open && setDeletingSlot(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Jam Pembelajaran</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus jam pembelajaran <strong>{deletingSlot?.name}</strong>?
                    </p>
                    <p className="text-xs text-muted-foreground mt-2 bg-muted p-2.5 rounded border border-border">
                        Penghapusan hanya berlaku pada Master Jam Pembelajaran ini saja dan tidak merubah atau menghapus data jadwal pelajaran yang sudah ada.
                    </p>
                    <DialogFooter className="pt-2">
                        <Button type="button" variant="outline" onClick={() => setDeletingSlot(null)}>
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDeleteSubmit}
                            disabled={deleteForm.processing}
                        >
                            {deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
