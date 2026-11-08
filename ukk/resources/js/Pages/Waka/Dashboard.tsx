import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from '@/Components/ui/table';
import { 
    Users, 
    GraduationCap, 
    Calendar, 
    CalendarDays,
    RefreshCw, 
    RotateCw,
    Lock, 
    Unlock, 
    CheckCircle,
    UserCheck,
    Clock,
    ArrowRight,
    AlertTriangle,
    AlertCircle
} from 'lucide-react';

interface ScheduleItem {
    id: number;
    day_of_week: string;
    subject: string;
    class_name: string;
    room_name: string;
    start_time: string;
    end_time: string;
    teacher?: {
        fullname: string;
    };
    room?: {
        name: string;
    };
}

interface DashboardProps {
    stats?: {
        total_siswa: number;
        total_guru: number;
        total_alumni: number;
        total_jadwal: number;
    };
    config?: {
        active_cycle: number;
        is_scheduled_locked: boolean;
        academic_year: string;
        needs_year_update?: boolean;
        real_academic_year?: string;
    };
    todaySchedules?: ScheduleItem[];
    attendanceSummary?: {
        hadir: number;
        sakit: number;
        izin: number;
        alpa: number;
    };
    currentDay?: string;
}

export default function Dashboard({ 
    stats, 
    config, 
    todaySchedules = [], 
    attendanceSummary = { hadir: 0, sakit: 0, izin: 0, alpa: 0 },
    currentDay = 'Senin' 
}: DashboardProps) {
    const cycleForm = useForm({});
    const lockForm = useForm({});
    const advanceYearForm = useForm({});

    const [isAdvanceYearOpen, setIsAdvanceYearOpen] = useState(false);

    const toggleCycle = () => {
        cycleForm.post('/waka/config/toggle-cycle');
    };

    const toggleLock = () => {
        lockForm.post('/waka/config/toggle-lock');
    };

    const activeCycle = config?.active_cycle ?? 1;
    const isLocked = config?.is_scheduled_locked ?? false;
    const currentAcademicYear = config?.academic_year || '2026/2027';
    const needsYearUpdate = config?.needs_year_update ?? false;

    const getNextAcademicYear = (currentYear: string) => {
        const match = currentYear.match(/(\d{4})\/(\d{4})/);
        if (match) {
            const start = parseInt(match[1]) + 1;
            const end = parseInt(match[2]) + 1;
            return `${start}/${end}`;
        }
        return '2027/2028';
    };

    const nextAcademicYear = getNextAcademicYear(currentAcademicYear);

    const handleAdvanceYear = (e: React.FormEvent) => {
        e.preventDefault();
        advanceYearForm.post('/waka/config/advance-academic-year', {
            preserveScroll: true,
            onSuccess: () => {
                setIsAdvanceYearOpen(false);
            },
        });
    };

    const formatTime = (timeStr: string) => {
        return timeStr ? timeStr.substring(0, 5) : '';
    };

    return (
        <AppLayout title="Dashboard Waka Kurikulum" showYearBadge={false}>
            <Head title="Dashboard Waka" />

            <div className="space-y-6">
                {/* Greeting Card with Integrated Academic Year & Advance Controller */}
                <Card className="p-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        {/* Sisi Kiri: Sapaan Administrator */}
                        <div className="space-y-1">
                            <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Panel Administrator
                            </span>
                            <h2 className="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                                Selamat Datang di Sistem UKK
                            </h2>
                            <p className="text-sm text-muted-foreground max-w-xl">
                                Pantau operasional presensi, siklus pembelajaran, dan data master sekolah secara terpusat.
                            </p>
                        </div>

                        {/* Sisi Kanan: Tahun Ajaran Aktif (Skala Besar & Kontras Jelas) */}
                        <div className="flex items-center gap-4 rounded-xl border-2 border-border/80 bg-muted/30 p-4 shrink-0">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <CalendarDays className="h-8 w-8" />
                            </div>
                            <div className="space-y-1">
                                <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground block">
                                    Tahun Ajaran Aktif
                                </span>
                                <div className="text-2xl md:text-3xl font-black tracking-tight text-foreground">
                                    {currentAcademicYear}
                                </div>
                                {needsYearUpdate ? (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => setIsAdvanceYearOpen(true)}
                                        className="h-8 gap-1.5 text-xs font-semibold bg-red-600 hover:bg-red-700 text-white border-red-600 shadow-sm cursor-pointer transition-all animate-pulse"
                                        title="Waktunya pergantian tahun ajaran baru! Klik untuk memajukan tahun ajaran."
                                    >
                                        <RotateCw className="h-3.5 w-3.5" />
                                        <span>Ganti ke {nextAcademicYear}</span>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={true}
                                        className="h-8 gap-1.5 text-xs font-medium bg-muted/60 text-muted-foreground border-border cursor-not-allowed opacity-60"
                                        title="Tahun ajaran aktif saat ini masih berjalan. Tombol akan aktif berwarna merah saat memasuki periode tahun ajaran baru."
                                    >
                                        <RotateCw className="h-3.5 w-3.5" />
                                        <span>Ganti ke {nextAcademicYear}</span>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Unified Kontrol Putaran & Proteksi Jadwal Bar - Skala Besar & Ramah Pengguna Senior */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6 rounded-2xl border-2 border-border bg-card p-5 md:p-6 shadow-sm">
                    {/* Sisi Putaran */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 flex-1">
                        <div className="flex items-center gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <RefreshCw className="h-6 w-6" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <span className="text-sm font-semibold text-muted-foreground">Siklus Pembelajaran:</span>
                                    <Badge variant="default" className="text-sm px-3 py-0.5 font-bold">
                                        Putaran Ke-{activeCycle}
                                    </Badge>
                                </div>
                                <p className="text-xs md:text-sm text-muted-foreground mt-1">
                                    KBM sekolah saat ini berjalan pada siklus blok Putaran {activeCycle}.
                                </p>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            onClick={toggleCycle}
                            disabled={cycleForm.processing}
                            className="shrink-0 h-11 px-4 gap-2 text-sm font-semibold shadow-sm hover:bg-accent"
                        >
                            <RefreshCw className={`h-4 w-4 ${cycleForm.processing ? 'animate-spin' : ''}`} />
                            Ganti ke Putaran {activeCycle === 1 ? 2 : 1}
                        </Button>
                    </div>

                    {/* Pemisah Vertikal pada Layar Desktop */}
                    <div className="hidden lg:block w-px self-stretch bg-border" />

                    {/* Sisi Proteksi Jadwal */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 flex-1">
                        <div className="flex items-center gap-4">
                            <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${isLocked ? 'bg-destructive/10 text-destructive' : 'bg-emerald-500/10 text-emerald-600'}`}>
                                {isLocked ? <Lock className="h-6 w-6" /> : <Unlock className="h-6 w-6" />}
                            </div>
                            <div>
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <span className="text-sm font-semibold text-muted-foreground">Status Penambahan Jadwal:</span>
                                    <Badge 
                                        variant={isLocked ? "destructive" : "secondary"} 
                                        className={`text-sm px-3 py-0.5 font-bold ${!isLocked ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : ''}`}
                                    >
                                        {isLocked ? 'Jadwal Terkunci' : 'Jadwal Terbuka'}
                                    </Badge>
                                </div>
                                <p className="text-xs md:text-sm text-muted-foreground mt-1">
                                    {isLocked ? 'Jadwal terkunci oleh Waka (tidak dapat diubah)' : 'Jadwal terbuka (dapat ditambah/diedit)'}
                                </p>
                            </div>
                        </div>
                        <Button
                            variant={isLocked ? "outline" : "default"}
                            onClick={toggleLock}
                            disabled={lockForm.processing}
                            className="shrink-0 h-11 px-4 gap-2 text-sm font-semibold shadow-sm"
                        >
                            {isLocked ? (
                                <>
                                    <Unlock className="h-4 w-4" /> Buka Kunci
                                </>
                            ) : (
                                <>
                                    <Lock className="h-4 w-4" /> Kunci Jadwal
                                </>
                            )}
                        </Button>
                    </div>
                </div>

                {/* 4 Kartu KPI Ringkas (Format Murni Shadcn Dashboard 01) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Siswa Aktif</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_siswa ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Kelas X, XI, dan XII</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tenaga Pendidik (Guru)</CardTitle>
                            <UserCheck className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_guru ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Guru aktif terdaftar</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Arsip Alumni</CardTitle>
                            <GraduationCap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_alumni ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Data kelulusan tersimpan</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Jadwal Aktif (Putaran {activeCycle})</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.total_jadwal ?? 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Sesi pelajaran aktif</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Bagian Operasional: Jadwal Hari Ini & Ringkasan Presensi */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Tabel Jadwal Hari Ini (2 Kolom) */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold">
                                    Jadwal Pembelajaran Hari Ini ({currentDay})
                                </CardTitle>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Agenda KBM aktif pada Putaran Ke-{activeCycle}
                                </p>
                            </div>
                            <Link href="/waka/jadwal" className="text-xs font-medium text-foreground hover:underline flex items-center gap-1">
                                Kelola Jadwal <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Mata Pelajaran</TableHead>
                                        <TableHead>Kelas</TableHead>
                                        <TableHead>Guru Pengampu</TableHead>
                                        <TableHead>Ruang</TableHead>
                                        <TableHead className="text-right">Jam</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {todaySchedules.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-6 text-muted-foreground text-xs">
                                                Tidak ada jadwal pembelajaran aktif untuk hari ini.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        todaySchedules.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium text-sm">
                                                    {item.subject}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <Badge variant="outline">{item.class_name}</Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground">
                                                    {item.teacher?.fullname || '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground">
                                                    {item.room_name || item.room?.name || '-'}
                                                </TableCell>
                                                <TableCell className="text-right font-mono text-xs">
                                                    {formatTime(item.start_time)} - {formatTime(item.end_time)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Ringkasan Presensi Hari Ini (1 Kolom) */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold">Kehadiran Hari Ini</CardTitle>
                                <p className="text-xs text-muted-foreground mt-0.5">Rekapitulasi absensi siswa</p>
                            </div>
                            <Link href="/waka/laporan/presensi" className="text-xs font-medium text-foreground hover:underline flex items-center gap-1">
                                Laporan <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between p-3 rounded-lg border border-border bg-muted/30">
                                <span className="text-xs font-medium">Hadir</span>
                                <span className="font-bold text-sm text-foreground">{attendanceSummary.hadir} siswa</span>
                            </div>
                            <div className="flex items-center justify-between p-3 rounded-lg border border-border bg-muted/30">
                                <span className="text-xs font-medium">Sakit</span>
                                <span className="font-bold text-sm text-foreground">{attendanceSummary.sakit} siswa</span>
                            </div>
                            <div className="flex items-center justify-between p-3 rounded-lg border border-border bg-muted/30">
                                <span className="text-xs font-medium">Izin</span>
                                <span className="font-bold text-sm text-foreground">{attendanceSummary.izin} siswa</span>
                            </div>
                            <div className="flex items-center justify-between p-3 rounded-lg border border-border bg-muted/30">
                                <span className="text-xs font-medium">Alpa (Tanpa Keterangan)</span>
                                <span className="font-bold text-sm text-foreground">{attendanceSummary.alpa} siswa</span>
                            </div>

                            <div className="pt-2">
                                <Link href="/waka/jam-pelajaran">
                                    <Button variant="outline" size="sm" className="w-full text-xs gap-2">
                                        <Clock className="h-3.5 w-3.5" /> Konfigurasi Jam Belajar
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Modal Dialog Konfirmasi Pergantian Tahun Ajaran */}
            <Dialog open={isAdvanceYearOpen} onOpenChange={setIsAdvanceYearOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">
                                <AlertTriangle className="h-5 w-5" />
                            </div>
                            <DialogTitle className="text-lg font-bold">
                                Konfirmasi Pergantian Tahun Ajaran Baru
                            </DialogTitle>
                        </div>
                        <DialogDescription className="pt-2 text-sm text-muted-foreground leading-relaxed">
                            Apakah Anda yakin ingin memajukan tahun ajaran aktif dari{' '}
                            <span className="font-bold text-foreground">{currentAcademicYear}</span> menjadi{' '}
                            <span className="font-bold text-primary">{nextAcademicYear}</span>?
                        </DialogDescription>
                    </DialogHeader>

                    <div className="rounded-lg border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                        <div className="flex items-start gap-2">
                            <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                            <div className="space-y-1">
                                <p className="font-semibold">Perhatian Sebelum Melanjutkan:</p>
                                <p>
                                    Pastikan proses kelulusan siswa tingkat akhir (XII) ke Arsip Alumni serta kenaikan kelas angkatan sebelumnya telah selesai dilakukan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0 mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsAdvanceYearOpen(false)}
                            disabled={advanceYearForm.processing}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={handleAdvanceYear}
                            disabled={advanceYearForm.processing}
                            className="gap-2"
                        >
                            {advanceYearForm.processing && (
                                <RotateCw className="h-4 w-4 animate-spin" />
                            )}
                            Ya, Majukan ke {nextAcademicYear}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
