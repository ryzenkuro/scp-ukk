import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
    BookOpen,
    PlusCircle,
    Search,
    Edit2,
    Trash2,
    AlertTriangle,
    Calendar,
    Users
} from 'lucide-react';

interface AttendanceItem {
    id: number;
    student_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
}

interface JournalItem {
    id: number;
    schedule_id: number;
    teacher_id: number;
    date: string;
    topic: string;
    notes: string | null;
    schedule?: {
        subject: string;
        class_name: string;
        room_name: string | null;
    };
    attendances?: AttendanceItem[];
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
    journals: PaginatedData<JournalItem>;
    filters: {
        search?: string;
        date?: string;
    };
}

export default function JournalIndex({ journals, filters }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedDate, setSelectedDate] = useState(filters.date || '');
    const [deleteItem, setDeleteItem] = useState<JournalItem | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/guru/journal', {
            search: searchQuery,
            date: selectedDate,
        }, { preserveState: true });
    };

    const handleDateChange = (val: string) => {
        setSelectedDate(val);
        router.get('/guru/journal', {
            search: searchQuery,
            date: val,
        }, { preserveState: true });
    };

    const submitDelete = () => {
        if (!deleteItem) return;
        router.delete(`/guru/journal/${deleteItem.id}`, {
            onSuccess: () => setDeleteItem(null),
        });
    };

    const countStatus = (attendances: AttendanceItem[] = [], status: string) => {
        return attendances.filter(a => a.status === status).length;
    };

    return (
        <AppLayout title="Riwayat Jurnal KBM & Presensi">
            <Head title="Jurnal KBM" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Riwayat Jurnal KBM & Absensi Kelas
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Catatan materi pembelajaran, catatan kendala kelas, dan rekapitulasi kehadiran siswa.
                            </p>
                        </div>
                        <Link href="/guru/journal/create">
                            <Button className="gap-2">
                                <PlusCircle className="h-4 w-4" />
                                Isi Jurnal KBM Baru
                            </Button>
                        </Link>
                    </CardHeader>

                    {/* Filter Bar */}
                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="sm:col-span-2 relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari materi KBM, mata pelajaran, atau kelas..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    value={selectedDate}
                                    onChange={(e) => handleDateChange(e.target.value)}
                                />
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table Jurnal */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Mata Pelajaran & Kelas</TableHead>
                                    <TableHead>Topik / Materi Pembelajaran</TableHead>
                                    <TableHead className="text-center">Presensi Siswa</TableHead>
                                    <TableHead>Catatan KBM</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {journals.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                            Tidak ada riwayat jurnal KBM yang ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    journals.data.map((journal, idx) => {
                                        const hadir = countStatus(journal.attendances, 'hadir');
                                        const sakit = countStatus(journal.attendances, 'sakit');
                                        const izin = countStatus(journal.attendances, 'izin');
                                        const alpa = countStatus(journal.attendances, 'alpa');
                                        const total = (journal.attendances || []).length;

                                        return (
                                            <TableRow key={journal.id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {(journals.current_page - 1) * 15 + idx + 1}
                                                </TableCell>
                                                <TableCell className="text-xs font-mono font-medium whitespace-nowrap">
                                                    {journal.date}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium text-foreground block">
                                                        {journal.schedule?.subject || '-'}
                                                    </span>
                                                    <Badge variant="outline" className="text-xs mt-0.5">
                                                        {journal.schedule?.class_name || '-'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="font-medium max-w-xs">
                                                    {journal.topic}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <div className="flex items-center justify-center gap-1 text-xs font-mono">
                                                        <span className="bg-secondary text-secondary-foreground px-1.5 py-0.5 rounded font-semibold" title="Hadir">
                                                            H:{hadir}
                                                        </span>
                                                        <span className="bg-muted px-1.5 py-0.5 rounded" title="Sakit">
                                                            S:{sakit}
                                                        </span>
                                                        <span className="bg-muted px-1.5 py-0.5 rounded" title="Izin">
                                                            I:{izin}
                                                        </span>
                                                        <span className={`px-1.5 py-0.5 rounded font-semibold ${alpa > 0 ? 'bg-destructive/15 text-destructive' : 'bg-muted'}`} title="Alpa">
                                                            A:{alpa}
                                                        </span>
                                                    </div>
                                                    <span className="text-[10px] text-muted-foreground block mt-0.5">
                                                        Total: {total} murid
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground max-w-xs truncate">
                                                    {journal.notes || '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={`/guru/journal/${journal.id}/edit`}>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 w-8 p-0"
                                                                title="Edit Jurnal & Presensi"
                                                            >
                                                                <Edit2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setDeleteItem(journal)}
                                                            className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                            title="Hapus Jurnal"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
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
                    {journals.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4">
                            <span className="text-xs text-muted-foreground">
                                Total {journals.total} jurnal (Halaman {journals.current_page} dari {journals.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!journals.prev_page_url}
                                    onClick={() => journals.prev_page_url && router.get(journals.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!journals.next_page_url}
                                    onClick={() => journals.next_page_url && router.get(journals.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Delete Confirmation */}
            <Dialog open={!!deleteItem} onOpenChange={(open) => !open && setDeleteItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Konfirmasi Hapus Jurnal KBM
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus jurnal materi <strong className="text-foreground">{deleteItem?.topic}</strong>? Rekap presensi siswa pada pertemuan ini juga akan dibersihkan.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteItem(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={submitDelete}>
                            Ya, Hapus Jurnal
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

