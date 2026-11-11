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
    BookOpen,
    PlusCircle,
    Search,
    Edit2,
    Calendar,
    Users,
    Eye,
    Lock,
} from 'lucide-react';
import { formatDateIndo } from '@/lib/utils';
import { JournalDetailModal, JournalDetailData } from '@/Components/modals/JournalDetailModal';

interface AttendanceItem {
    id: number;
    student_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes?: string | null;
    student?: {
        id: number;
        nis: string;
        name: string;
        gender?: 'L' | 'P';
        kelas?: string;
    };
}

interface JournalItem {
    id: number;
    schedule_id: number;
    teacher_id: number;
    date: string;
    topic: string;
    notes: string | null;
    documentation_path?: string | null;
    documentation_url?: string | null;
    can_edit?: boolean;
    schedule?: {
        id: number;
        subject: string;
        class_name: string;
        room_name?: string | null;
        room?: {
            name: string;
        } | null;
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
    canCreateJournal?: boolean;
    activeScheduleId?: number | null;
    filters: {
        search?: string;
        date?: string;
    };
}

export default function JournalIndex({
    journals,
    canCreateJournal = false,
    activeScheduleId = null,
    filters,
}: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedDate, setSelectedDate] = useState(filters.date || '');
    const [selectedDetailJournal, setSelectedDetailJournal] = useState<JournalItem | null>(null);

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
                        {canCreateJournal ? (
                            <Link href={activeScheduleId ? `/guru/journal/create?schedule_id=${activeScheduleId}` : '/guru/journal/create'}>
                                <Button className="gap-2 bg-primary">
                                    <PlusCircle className="h-4 w-4" />
                                    Isi Jurnal KBM Baru
                                </Button>
                            </Link>
                        ) : (
                            <Button
                                disabled
                                className="gap-2 opacity-50 cursor-not-allowed bg-muted text-muted-foreground border border-border"
                                title="Menunggu sesi KBM aktif berikutnya sesuai jadwal"
                            >
                                <PlusCircle className="h-4 w-4" />
                                Isi Jurnal KBM Baru
                            </Button>
                        )}
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
                                    <TableHead className="text-center">Detail Jurnal & Presensi</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {journals.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-32 text-center text-muted-foreground">
                                            Tidak ada riwayat jurnal KBM yang ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    journals.data.map((journal, idx) => {
                                        return (
                                            <TableRow key={journal.id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {(journals.current_page - 1) * 15 + idx + 1}
                                                </TableCell>
                                                <TableCell className="text-xs font-medium whitespace-nowrap">
                                                    {formatDateIndo(journal.date)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium text-foreground block">
                                                        {journal.schedule?.subject || '-'}
                                                    </span>
                                                    <Badge variant="outline" className="text-xs mt-0.5">
                                                        {journal.schedule?.class_name || '-'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setSelectedDetailJournal(journal)}
                                                        className="h-8 gap-1.5 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border-slate-200 dark:bg-slate-900 dark:border-slate-800 text-foreground shadow-2xs"
                                                    >
                                                        <Eye className="h-3.5 w-3.5 text-muted-foreground" />
                                                        Lihat Detail
                                                    </Button>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        {journal.can_edit ? (
                                                            <Link href={`/guru/journal/${journal.id}/edit`}>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-8 w-8 p-0"
                                                                    title="Edit Jurnal & Presensi"
                                                                >
                                                                    <Edit2 className="h-3.5 w-3.5 text-primary" />
                                                                </Button>
                                                            </Link>
                                                        ) : (
                                                            <span
                                                                className="inline-flex items-center gap-1 text-[11px] font-medium text-muted-foreground bg-muted/60 px-2 py-1 rounded border border-border/70 select-none"
                                                                title="Terkunci: Jam pembelajaran telah berakhir"
                                                            >
                                                                <Lock className="h-3 w-3 text-muted-foreground/70" />
                                                                Terkunci
                                                            </span>
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

            {/* Modal Detail Jurnal & Presensi */}
            <JournalDetailModal
                isOpen={!!selectedDetailJournal}
                onClose={() => setSelectedDetailJournal(null)}
                journal={selectedDetailJournal}
                canEdit={selectedDetailJournal?.can_edit ?? false}
            />
        </AppLayout>
    );
}

