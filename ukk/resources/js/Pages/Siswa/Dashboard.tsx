import React from 'react';
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
    Clock,
    Users,
    ClipboardList,
    CheckCircle2,
    AlertCircle,
    ArrowRight,
    GraduationCap,
    Send
} from 'lucide-react';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
    angkatan: number;
    gender: string;
    status: string;
}

interface ScheduleItem {
    id: number;
    day_of_week: string;
    subject: string;
    start_time: string;
    end_time: string;
    room_name: string | null;
    teacher?: { fullname: string };
    start_slot?: { name: string };
    end_slot?: { name: string };
}

interface LeaveItem {
    id: number;
    type: 'sakit' | 'izin';
    start_date: string;
    end_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
}

interface DashboardProps {
    student: SiswaItem | null;
    todaySchedules: ScheduleItem[];
    recentLeaves: LeaveItem[];
    currentDay: string;
    stats: {
        hadir: number;
        sakit: number;
        izin: number;
        alpa: number;
        total_kbm: number;
        rate: number;
    };
    config: {
        active_cycle: number;
        academic_year: string;
    };
}

export default function SiswaDashboard({
    student,
    todaySchedules = [],
    recentLeaves = [],
    currentDay = 'Senin',
    stats,
    config,
}: DashboardProps) {
    return (
        <AppLayout title="Portal Mandiri Siswa & Orang Tua">
            <Head title="Dashboard Siswa" />

            <div className="space-y-6">
                {/* Kartu Profil Siswa */}
                <Card className="p-6">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Portal Akademik Murid & Orang Tua
                            </span>
                            <h2 className="text-2xl font-bold tracking-tight mt-1">
                                {student ? student.name : 'Siswa'}
                            </h2>
                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground mt-1">
                                <span>NIS: <strong className="text-foreground">{student?.nis || '-'}</strong></span>
                                <span>&bull;</span>
                                <span>Kelas: <strong className="text-foreground">{student?.kelas || '-'}</strong></span>
                                <span>&bull;</span>
                                <span>Angkatan: <strong className="text-foreground">{student?.angkatan || '-'}</strong></span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Badge variant="default" className="text-xs">
                                Siklus Putaran {config?.active_cycle ?? 1}
                            </Badge>
                            <Badge variant="outline" className="text-xs">
                                TP {config?.academic_year || '2026/2027'}
                            </Badge>
                        </div>
                    </div>
                </Card>

                {/* 4 Kartu Metrik Kehadiran Pribadi */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium">Hadir (H)</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.hadir ?? 0}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Pertemuan diikuti</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium">Sakit (S)</CardTitle>
                            <AlertCircle className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.sakit ?? 0}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Surat dokter</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium">Izin (I)</CardTitle>
                            <ClipboardList className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats?.izin ?? 0}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Disetujui sekolah</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-xs font-medium">Persentase Kehadiran</CardTitle>
                            <GraduationCap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-foreground">
                                {stats?.rate ?? 100}%
                            </div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">
                                Dari {stats?.total_kbm ?? 0} sesi KBM
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Jadwal Pelajaran Kelas Hari Ini */}
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-lg font-bold tracking-tight flex items-center gap-2">
                                <Clock className="h-5 w-5 text-muted-foreground" />
                                Jadwal Kelas Hari Ini ({currentDay})
                            </CardTitle>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Pelajaran aktif kelas {student?.kelas} pada Siklus Putaran {config?.active_cycle ?? 1}.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link href="/siswa/izin">
                                <Button size="sm" variant="outline" className="gap-1.5 text-xs">
                                    <Send className="h-3.5 w-3.5" />
                                    Ajukan Izin
                                </Button>
                            </Link>
                            <Link href="/siswa/jadwal">
                                <Button size="sm" className="gap-1.5 text-xs">
                                    <Calendar className="h-3.5 w-3.5" />
                                    Jadwal Lengkap
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-0">
                        {todaySchedules.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Tidak ada jadwal KBM untuk kelas {student?.kelas} pada hari {currentDay}.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Waktu</TableHead>
                                            <TableHead>Mata Pelajaran</TableHead>
                                            <TableHead>Guru Pengampu</TableHead>
                                            <TableHead>Ruangan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {todaySchedules.map((schedule) => (
                                            <TableRow key={schedule.id}>
                                                <TableCell className="font-semibold text-xs">
                                                    {schedule.start_time.substring(0, 5)} - {schedule.end_time.substring(0, 5)}
                                                    {schedule.start_slot && schedule.end_slot && (
                                                        <span className="block text-[11px] font-normal text-muted-foreground">
                                                            {schedule.start_slot.name} s.d. {schedule.end_slot.name}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-medium text-foreground">
                                                    {schedule.subject}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {schedule.teacher?.fullname || '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground">
                                                    <Badge variant="outline">{schedule.room_name || '-'}</Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Riwayat Pengajuan Izin Siswa */}
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-lg font-bold tracking-tight flex items-center gap-2">
                                <ClipboardList className="h-5 w-5 text-muted-foreground" />
                                Riwayat Pengajuan Izin Terakhir
                            </CardTitle>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Status permohonan izin sakit atau izin keperluan keluarga.
                            </p>
                        </div>
                        <Link href="/siswa/izin">
                            <Button variant="ghost" size="sm" className="gap-1.5 text-xs">
                                Lihat Semua Izin <ArrowRight className="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent className="pt-0">
                        {recentLeaves.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                                Belum ada permohonan izin yang pernah diajukan.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Jenis Izin</TableHead>
                                            <TableHead>Rentang Tanggal</TableHead>
                                            <TableHead>Alasan / Keperluan</TableHead>
                                            <TableHead className="text-right">Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentLeaves.map((leave) => (
                                            <TableRow key={leave.id}>
                                                <TableCell>
                                                    <Badge variant={leave.type === 'sakit' ? 'secondary' : 'outline'} className="capitalize">
                                                        {leave.type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs font-mono">
                                                    {leave.start_date} s.d. {leave.end_date}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {leave.reason}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Badge
                                                        variant={
                                                            leave.status === 'approved'
                                                                ? 'default'
                                                                : leave.status === 'rejected'
                                                                ? 'destructive'
                                                                : 'secondary'
                                                        }
                                                        className="capitalize text-xs"
                                                    >
                                                        {leave.status === 'approved' ? 'Disetujui' : leave.status === 'rejected' ? 'Ditolak' : 'Menunggu'}
                                                    </Badge>
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
        </AppLayout>
    );
}
