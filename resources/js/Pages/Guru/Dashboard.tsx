import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
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
    Calendar,
    BookOpen,
    CheckCircle2,
    Users,
    Clock,
    PlusCircle,
    ArrowRight,
    ClipboardList,
    Printer,
    Eye
} from 'lucide-react';
import { formatDateIndo } from '@/lib/utils';
import { JournalDetailModal, JournalDetailData } from '@/Components/modals/JournalDetailModal';

interface ScheduleItem {
    id: number;
    day_of_week: string;
    cycle_number: number;
    subject: string;
    class_name: string;
    room_name: string | null;
    start_time: string;
    end_time: string;
    start_slot?: { name: string };
    end_slot?: { name: string };
    is_filled_today?: boolean;
    journal_id?: number | null;
    time_status?: 'upcoming' | 'ongoing' | 'grace_period' | 'missed' | 'filled';
    remaining_grace_minutes?: number | null;
    can_edit?: boolean;
}

interface AttendanceItem {
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
}

interface JournalItem {
    id: number;
    date: string;
    topic: string;
    notes: string | null;
    can_edit?: boolean;
    schedule?: {
        subject: string;
        class_name: string;
    };
    attendances?: AttendanceItem[];
}

interface DashboardProps {
    auth: any;
    todaySchedules: ScheduleItem[];
    activeSchedule?: ScheduleItem | null;
    recentJournals: JournalItem[];
    currentDay: string;
    stats: {
        total_journals: number;
        total_schedules: number;
        total_students: number;
    };
    config: {
        active_cycle: number;
        academic_year: string;
    };
}

export default function GuruDashboard({
    auth,
    todaySchedules = [],
    activeSchedule = null,
    recentJournals = [],
    currentDay = 'Senin',
    stats,
    config,
}: DashboardProps) {
    const user = auth?.user;
    const [selectedDetailJournal, setSelectedDetailJournal] = useState<JournalDetailData | null>(null);

    const countStatus = (attendances: AttendanceItem[] = [], status: string) => {
        return attendances.filter(a => a.status === status).length;
    };

    return (
        <AppLayout title="Portal Guru - Agenda & Jurnal KBM">
            <Head title="Dashboard Guru" />

            <div className="space-y-6">
                {/* Greeting Card */}
                <Card className="p-4 sm:p-6">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Portal Tenaga Pendidik
                            </span>
                            <h2 className="text-2xl font-bold tracking-tight mt-1">
                                Selamat Datang, {user?.fullname || user?.username}!
                            </h2>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Pantau jadwal mengajar hari {currentDay}, isi materi jurnal KBM, dan kelola presensi siswa secara terpusat.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <a href="/guru/jadwal/export-pdf" target="_blank" rel="noreferrer">
                                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                                    <Printer className="h-4 w-4" />
                                    <span>Cetak<span className="hidden sm:inline"> Jadwal (A4)</span></span>
                                </Button>
                            </a>
                            <Badge variant="default" className="text-xs">
                                Siklus Putaran {config?.active_cycle ?? 1}
                            </Badge>
                            <Badge variant="outline" className="text-xs">
                                {config?.academic_year || '2026/2027'}
                            </Badge>
                        </div>
                    </div>
                </Card>

                {/* 3 Kartu Metrik Mengajar Guru */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Jurnal KBM Terisi</CardTitle>
                            <BookOpen className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_journals ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Total sesi pertemuan diajar</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Jadwal Mengajar Terdaftar</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_schedules ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Sesi KBM Putaran 1 & 2</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Murid Diajar</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_students ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Siswa aktif di kelas Anda</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Jadwal Mengajar Hari Ini */}
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-lg font-bold tracking-tight flex items-center gap-2">
                                <Clock className="h-5 w-5 text-muted-foreground" />
                                Jadwal Mengajar Hari Ini ({currentDay})
                            </CardTitle>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                KBM yang aktif sesuai Siklus Putaran {config?.active_cycle ?? 1}.
                            </p>
                        </div>
                        {activeSchedule ? (
                            <Link href={`/guru/journal/create?schedule_id=${activeSchedule.id}`}>
                                <Button size="sm" className="gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                                    <PlusCircle className="h-4 w-4" />
                                    Isi Jurnal KBM
                                </Button>
                            </Link>
                        ) : (
                            <Button size="sm" disabled className="gap-1.5 text-xs opacity-60 cursor-not-allowed bg-muted text-muted-foreground border border-border" title="Menunggu jadwal KBM aktif berikutnya">
                                <Clock className="h-4 w-4" />
                                Isi Jurnal KBM
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        {todaySchedules.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Tidak ada jadwal mengajar untuk Anda pada hari {currentDay} (Putaran {config?.active_cycle ?? 1}).
                            </div>
                        ) : (
                            <div className="overflow-x-auto w-full touch-pan-x">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Waktu</TableHead>
                                            <TableHead>Mata Pelajaran</TableHead>
                                            <TableHead>Kelas</TableHead>
                                            <TableHead>Ruangan</TableHead>
                                            <TableHead className="text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {todaySchedules.map((schedule) => {
                                            const isOngoing = schedule.time_status === 'ongoing';
                                            const isGrace = schedule.time_status === 'grace_period';
                                            const isFilled = schedule.time_status === 'filled';
                                            const isMissed = schedule.time_status === 'missed';
                                            const isUpcoming = schedule.time_status === 'upcoming';

                                            return (
                                                <TableRow
                                                    key={schedule.id}
                                                    className={isOngoing ? 'bg-emerald-50/70 dark:bg-emerald-950/30' : ''}
                                                >
                                                    <TableCell className="font-semibold text-xs whitespace-nowrap">
                                                        <span className="font-mono">{schedule.start_time.substring(0, 5)} - {schedule.end_time.substring(0, 5)}</span>
                                                        {schedule.start_slot && schedule.end_slot && (
                                                            <span className="block text-[11px] font-normal text-muted-foreground">
                                                                {schedule.start_slot.name} s.d. {schedule.end_slot.name}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="font-medium text-foreground block">
                                                            {schedule.subject}
                                                        </span>
                                                        <div className="flex items-center gap-1.5 mt-0.5">
                                                            <span className="text-[11px] text-muted-foreground font-mono">
                                                                Putaran {schedule.cycle_number}
                                                            </span>
                                                            {isOngoing && (
                                                                <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                                    (Berlangsung)
                                                                </span>
                                                            )}
                                                            {isGrace && (
                                                                <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                                                    Toleransi (Sisa {schedule.remaining_grace_minutes} mnt)
                                                                </span>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {schedule.class_name}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-xs text-muted-foreground">
                                                        {schedule.room_name || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {isFilled ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-300 text-[11px]">
                                                                    <CheckCircle2 className="h-3 w-3 mr-1 text-emerald-600" />
                                                                    Sudah Terisi
                                                                </Badge>
                                                                {schedule.journal_id && schedule.can_edit && (
                                                                    <Link href={`/guru/journal/${schedule.journal_id}/edit`}>
                                                                        <Button size="sm" variant="ghost" className="h-7 text-xs px-2 text-primary font-semibold">
                                                                            Edit
                                                                        </Button>
                                                                    </Link>
                                                                )}
                                                            </div>
                                                        ) : isOngoing || isGrace ? (
                                                            <Link href={`/guru/journal/create?schedule_id=${schedule.id}`}>
                                                                <Button size="sm" className="gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                                                                    <ClipboardList className="h-3.5 w-3.5" />
                                                                    Isi Jurnal
                                                                </Button>
                                                            </Link>
                                                        ) : isMissed ? (
                                                            <span className="text-xs text-destructive font-medium italic">
                                                                Batas Waktu Usai
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground italic">
                                                                Mendatang
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Riwayat Jurnal Terbaru */}
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-lg font-bold tracking-tight flex items-center gap-2">
                                <BookOpen className="h-5 w-5 text-muted-foreground" />
                                Riwayat Jurnal Pembelajaran Terbaru
                            </CardTitle>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Catatan materi dan rekapitulasi kehadiran pertemuan KBM sebelumnya.
                            </p>
                        </div>
                        <Link href="/guru/journal">
                            <Button variant="ghost" size="sm" className="gap-1.5 text-xs">
                                Lihat Semua Jurnal <ArrowRight className="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent className="pt-0">
                        {recentJournals.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Belum ada jurnal KBM yang pernah diisi. Klik tombol "Isi Jurnal KBM" untuk memulai.
                            </div>
                        ) : (
                            <div className="overflow-x-auto w-full touch-pan-x">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tanggal</TableHead>
                                            <TableHead>Mata Pelajaran & Kelas</TableHead>
                                            <TableHead className="text-center">Detail Jurnal & Presensi</TableHead>
                                            <TableHead className="text-right">Tindakan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentJournals.map((j) => (
                                            <TableRow key={j.id}>
                                                <TableCell className="text-xs whitespace-nowrap font-medium">
                                                    {formatDateIndo(j.date)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium text-foreground block">
                                                        {j.schedule?.subject || 'Mapel'}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {j.schedule?.class_name || 'Kelas'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setSelectedDetailJournal(j as unknown as JournalDetailData)}
                                                        className="h-8 gap-1.5 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border-slate-200 dark:bg-slate-900 dark:border-slate-800 text-foreground shadow-2xs"
                                                    >
                                                        <Eye className="h-3.5 w-3.5 text-muted-foreground" />
                                                        Lihat Detail
                                                    </Button>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {j.can_edit ? (
                                                        <Link href={`/guru/journal/${j.id}/edit`}>
                                                            <Button size="sm" variant="ghost" className="h-8 text-xs text-primary font-semibold">
                                                                Edit
                                                            </Button>
                                                        </Link>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground italic px-2">
                                                            Terkunci
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <JournalDetailModal
                isOpen={!!selectedDetailJournal}
                onClose={() => setSelectedDetailJournal(null)}
                journal={selectedDetailJournal}
                canEdit={selectedDetailJournal?.can_edit ?? false}
            />
        </AppLayout>
    );
}
