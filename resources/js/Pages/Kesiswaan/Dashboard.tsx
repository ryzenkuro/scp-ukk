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
  ClipboardList, 
  CheckCircle2, 
  XCircle, 
  Clock, 
  Calendar, 
  ArrowRight,
  BookOpen,
  FileText,
  Users,
  AlertTriangle,
  GraduationCap,
  Eye
} from 'lucide-react';
import { formatDateRangeIndo } from '@/lib/utils';
import { LeaveDetailModal, LeaveDetailData } from '@/Components/modals/LeaveDetailModal';

interface ScheduleItem {
  id: number;
  day_of_week: string;
  start_time: string;
  end_time: string;
  subject: string;
  school_class?: {
    name: string;
  };
  room?: {
    name: string;
  };
}

interface LeaveItem {
  id: number;
  type: string;
  start_date: string;
  end_date: string;
  reason: string;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  student?: {
    name: string;
    nis: string;
    kelas: string;
  };
  verifier?: {
    fullname?: string;
    username: string;
  };
}

interface DashboardProps {
  stats: {
    total_siswa: number;
    total_rombel: number;
    total_izin: number;
    pending_izin: number;
    approved_izin: number;
    rejected_izin: number;
    today_hadir: number;
    today_sakit: number;
    today_izin: number;
    today_alpa: number;
    total_jadwal_mengajar: number;
  };
  config: {
    active_cycle: number;
    academic_year: string;
  };
  jadwals?: ScheduleItem[];
  recentLeaves?: LeaveItem[];
}

export default function Dashboard({ 
  stats, 
  config, 
  jadwals = [], 
  recentLeaves = [] 
}: DashboardProps) {
  const [selectedDetailLeave, setSelectedDetailLeave] = useState<LeaveDetailData | null>(null);
  const formatTime = (timeStr: string) => {
    return timeStr ? timeStr.substring(0, 5) : '';
  };

  return (
    <AppLayout title="Dashboard Guru BK">
      <Head title="Dashboard Guru BK" />

      <div className="space-y-6">
        {/* Header Greeting & Quick Actions */}
        <Card className="p-6">
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                  Portal Guru BK
                </span>
                <Badge variant="outline" className="text-xs">
                  Siklus Putaran {config?.active_cycle ?? 1}
                </Badge>
                <Badge variant="secondary" className="text-xs">
                  Tahun Ajaran {config?.academic_year ?? '2026/2027'}
                </Badge>
              </div>
              <h2 className="text-2xl font-bold tracking-tight mt-1.5">
                Monitoring Bimbingan Konseling & Ketertiban Rombel
              </h2>
              <p className="text-sm text-muted-foreground mt-0.5 max-w-2xl">
                Pantau kedisiplinan dan kehadiran siswa di seluruh rombel, tinjau rekapitulasi izin yang diproses Wali Kelas, dan kelola agenda jam KBM.
              </p>
            </div>
            
            {/* Quick Action Navigation */}
            <div className="flex flex-wrap items-center gap-2">
              <Link href="/bk/presensi">
                <Button variant="default" size="sm" className="gap-1.5 text-xs">
                  <FileText className="h-4 w-4" />
                  <span>Rekap Presensi Rombel</span>
                </Button>
              </Link>
              <Link href="/bk/izin">
                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                  <ClipboardList className="h-4 w-4" />
                  <span>Rekap Izin Rombel</span>
                </Button>
              </Link>
              <Link href="/guru/journal">
                <Button variant="secondary" size="sm" className="gap-1.5 text-xs">
                  <Clock className="h-4 w-4" />
                  <span>Jurnal & Presensi KBM</span>
                </Button>
              </Link>
            </div>
          </div>
        </Card>

        {/* Row 1: School Overview & Attendance Metrics */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <Card className="p-4 border-l-4 border-l-primary">
            <span className="text-xs text-muted-foreground font-medium block">Total Siswa Aktif</span>
            <span className="text-2xl font-bold mt-1 block">{stats.total_siswa ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">{stats.total_rombel ?? 0} Rombel Terdaftar</span>
          </Card>

          <Card className="p-4 border-l-4 border-l-emerald-500">
            <span className="text-xs text-emerald-600 font-medium block">Hadir Hari Ini</span>
            <span className="text-2xl font-bold text-emerald-600 mt-1 block">{stats.today_hadir ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">Presensi KBM</span>
          </Card>

          <Card className="p-4 border-l-4 border-l-blue-500">
            <span className="text-xs text-blue-600 font-medium block">Sakit Hari Ini</span>
            <span className="text-2xl font-bold text-blue-600 mt-1 block">{stats.today_sakit ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">Izin keterangan sakit</span>
          </Card>

          <Card className="p-4 border-l-4 border-l-amber-500">
            <span className="text-xs text-amber-600 font-medium block">Izin Hari Ini</span>
            <span className="text-2xl font-bold text-amber-600 mt-1 block">{stats.today_izin ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">Izin resmi / dispen</span>
          </Card>

          <Card className="p-4 border-l-4 border-l-rose-500">
            <span className="text-xs text-rose-600 font-medium block">Alpa Hari Ini</span>
            <span className="text-2xl font-bold text-rose-600 mt-1 block">{stats.today_alpa ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">Tanpa keterangan</span>
          </Card>

          <Card className="p-4 border-l-4 border-l-purple-500">
            <span className="text-xs text-purple-600 font-medium block">Jadwal KBM Anda</span>
            <span className="text-2xl font-bold text-purple-600 mt-1 block">{stats.total_jadwal_mengajar ?? 0}</span>
            <span className="text-[11px] text-muted-foreground">Jam ajar terdaftar</span>
          </Card>
        </div>

        {/* Row 2: Status Izin Rombel Ringkasan */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card className="p-4 flex items-center justify-between border-amber-500/20 bg-amber-500/5">
            <div>
              <span className="text-xs text-amber-600 font-medium block">Menunggu Verifikasi Wali Kelas</span>
              <span className="text-2xl font-bold text-amber-600 mt-1 block">{stats.pending_izin ?? 0}</span>
              <span className="text-xs text-muted-foreground mt-0.5 block">Surat izin siswa belum diproses</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/20 text-amber-600">
              <Clock className="h-5 w-5" />
            </div>
          </Card>

          <Card className="p-4 flex items-center justify-between border-emerald-500/20 bg-emerald-500/5">
            <div>
              <span className="text-xs text-emerald-600 font-medium block">Izin Disetujui Wali Kelas</span>
              <span className="text-2xl font-bold text-emerald-600 mt-1 block">{stats.approved_izin ?? 0}</span>
              <span className="text-xs text-muted-foreground mt-0.5 block">Telah diverifikasi resmi</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-600">
              <CheckCircle2 className="h-5 w-5" />
            </div>
          </Card>

          <Card className="p-4 flex items-center justify-between border-destructive/20 bg-destructive/5">
            <div>
              <span className="text-xs text-destructive font-medium block">Izin Ditolak Wali Kelas</span>
              <span className="text-2xl font-bold text-destructive mt-1 block">{stats.rejected_izin ?? 0}</span>
              <span className="text-xs text-muted-foreground mt-0.5 block">Tidak disetujui / tidak sah</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive/20 text-destructive">
              <XCircle className="h-5 w-5" />
            </div>
          </Card>
        </div>

        {/* Row 3: Rekap Izin Siswa Terkini */}
        <Card className="p-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
              <h3 className="text-lg font-semibold tracking-tight">Rekap Permohonan Izin Siswa Terkini</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Monitoring izin dari seluruh rombel sekolah (verifikasi dilakukan oleh masing-masing Wali Kelas).
              </p>
            </div>
            <Link href="/bk/izin">
              <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                <span>Lihat Semua Rekap Izin</span>
                <ArrowRight className="h-3.5 w-3.5" />
              </Button>
            </Link>
          </div>

          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead>Rombel / Kelas</TableHead>
                  <TableHead>Jenis Izin</TableHead>
                  <TableHead>Rentang Tanggal</TableHead>
                  <TableHead>Alasan</TableHead>
                  <TableHead className="text-center">Status Verifikasi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {recentLeaves.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                      Belum ada data permohonan izin siswa yang tercatat.
                    </TableCell>
                  </TableRow>
                ) : (
                  recentLeaves.map((leave) => (
                    <TableRow key={leave.id}>
                      <TableCell className="font-medium">
                        <div>
                          <span>{leave.student?.name ?? `Siswa #${leave.id}`}</span>
                          <span className="block text-[11px] text-muted-foreground font-mono">
                            NIS: {leave.student?.nis ?? '-'}
                          </span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{leave.student?.kelas ?? '-'}</Badge>
                      </TableCell>
                      <TableCell>
                        <Badge 
                          variant={leave.type === 'sakit' ? 'secondary' : 'outline'} 
                          className="capitalize text-xs font-normal"
                        >
                          {leave.type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                        {formatDateRangeIndo(leave.start_date, leave.end_date)}
                      </TableCell>
                      <TableCell>
                        <button
                          type="button"
                          onClick={() => setSelectedDetailLeave(leave)}
                          className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 transition-colors whitespace-nowrap"
                        >
                          <Eye className="h-3.5 w-3.5" />
                          <span>Lihat Detail</span>
                        </button>
                      </TableCell>
                      <TableCell className="text-center">
                        {leave.status === 'approved' ? (
                          <div className="flex flex-col items-center">
                            <Badge className="bg-emerald-600 hover:bg-emerald-700 text-xs">
                              Disetujui
                            </Badge>
                            {leave.verifier && (
                              <span className="text-[10px] text-muted-foreground mt-0.5">
                                oleh {leave.verifier.fullname || leave.verifier.username}
                              </span>
                            )}
                          </div>
                        ) : leave.status === 'rejected' ? (
                          <div className="flex flex-col items-center">
                            <Badge variant="destructive" className="text-xs">
                              Ditolak
                            </Badge>
                            {leave.verifier && (
                              <span className="text-[10px] text-muted-foreground mt-0.5">
                                oleh {leave.verifier.fullname || leave.verifier.username}
                              </span>
                            )}
                          </div>
                        ) : (
                          <Badge variant="outline" className="bg-amber-500/10 text-amber-600 border-amber-300 text-xs">
                            Menunggu Wali Kelas
                          </Badge>
                        )}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </Card>

        {/* Row 4: Jadwal Mengajar Anda (Jika Merangkap Guru) */}
        {jadwals.length > 0 && (
          <Card className="p-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
              <div>
                <h3 className="text-lg font-semibold tracking-tight">Jadwal Mengajar KBM Anda</h3>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Jadwal KBM aktif Anda untuk Putaran {config?.active_cycle ?? 1} Tahun Ajaran {config?.academic_year ?? '2026/2027'}.
                </p>
              </div>
              <Link href="/guru/journal">
                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                  <span>Buka Jurnal KBM</span>
                  <ArrowRight className="h-3.5 w-3.5" />
                </Button>
              </Link>
            </div>

            <div className="rounded-md border border-border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Hari</TableHead>
                    <TableHead>Jam Pelajaran</TableHead>
                    <TableHead>Mata Pelajaran</TableHead>
                    <TableHead>Kelas</TableHead>
                    <TableHead>Ruangan</TableHead>
                    <TableHead className="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {jadwals.map((j) => (
                    <TableRow key={j.id}>
                      <TableCell className="font-medium">{j.day_of_week}</TableCell>
                      <TableCell className="text-xs text-muted-foreground font-mono">
                        {formatTime(j.start_time)} - {formatTime(j.end_time)}
                      </TableCell>
                      <TableCell className="font-medium">{j.subject}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{j.school_class?.name ?? '-'}</Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {j.room?.name ?? '-'}
                      </TableCell>
                      <TableCell className="text-right">
                        <Link href="/guru/journal">
                          <Button size="sm" variant="secondary" className="text-xs">
                            Isi Jurnal
                          </Button>
                        </Link>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </Card>
        )}
      </div>

      <LeaveDetailModal
        isOpen={!!selectedDetailLeave}
        onClose={() => setSelectedDetailLeave(null)}
        leave={selectedDetailLeave}
      />
    </AppLayout>
  );
}
