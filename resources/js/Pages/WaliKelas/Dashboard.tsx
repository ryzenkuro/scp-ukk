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
  Users, 
  Clock, 
  CheckCircle2, 
  Calendar, 
  ArrowRight,
  BookOpen,
  ClipboardList,
  FileText,
  Eye
} from 'lucide-react';
import { formatDateRangeIndo } from '@/lib/utils';
import { LeaveDetailModal, LeaveDetailData } from '@/Components/modals/LeaveDetailModal';

interface LeaveItem {
  id: number;
  type: string;
  start_date: string;
  end_date: string;
  reason: string;
  student?: {
    name: string;
    nis: string;
    kelas: string;
  };
}

interface DashboardProps {
  classNames: string[];
  stats: {
    total_siswa: number;
    pending_izin: number;
    approved_izin: number;
  };
  config: {
    academic_year: string;
    active_cycle: number;
  };
  recentLeaves: LeaveItem[];
}

export default function Dashboard({ classNames = [], stats, config, recentLeaves = [] }: DashboardProps) {
  const [selectedLeave, setSelectedLeave] = useState<LeaveDetailData | null>(null);

  return (
    <AppLayout title="Dashboard Wali Kelas">
      <Head title="Dashboard Wali Kelas" />

      <div className="space-y-6">
        {/* Header Greeting */}
        <Card className="p-4 sm:p-6">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Ruang Kerja Wali Kelas
              </span>
              <h2 className="text-2xl font-bold tracking-tight mt-1">
                Selamat Datang, Bapak/Ibu Wali Kelas
              </h2>
              <p className="text-sm text-muted-foreground mt-0.5">
                Kelola rombel binaan Anda, verifikasi surat izin siswa, dan pantau rekap presensi kelas.
              </p>
              <div className="mt-3 flex flex-wrap items-center gap-2">
                <span className="text-xs text-muted-foreground font-medium">Kelas Binaan:</span>
                {classNames.length === 0 ? (
                  <Badge variant="outline" className="text-xs text-amber-600 border-amber-300">
                    Belum ditugaskan kelas binaan (Hubungi Waka Kurikulum)
                  </Badge>
                ) : (
                  classNames.map((c) => (
                    <Badge key={c} variant="default" className="text-xs">
                      {c}
                    </Badge>
                  ))
                )}
              </div>
            </div>
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-2">
              <Badge variant="outline" className="text-xs">
                Siklus Putaran {config.active_cycle}
              </Badge>
              <Badge variant="secondary" className="text-xs">
                Tahun Ajaran {config.academic_year}
              </Badge>
            </div>
          </div>
        </Card>

        {/* Quick Stats Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Total Siswa Binaan
              </CardTitle>
              <Users className="h-4 w-4 text-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total_siswa}</div>
              <p className="text-xs text-muted-foreground mt-1">
                Siswa aktif terdaftar di rombel
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Izin Menunggu Verifikasi
              </CardTitle>
              <Clock className="h-4 w-4 text-amber-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.pending_izin}</div>
              <p className="text-xs text-muted-foreground mt-1">
                Pengajuan dari siswa kelas Anda
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Izin Telah Disetujui
              </CardTitle>
              <CheckCircle2 className="h-4 w-4 text-emerald-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.approved_izin}</div>
              <p className="text-xs text-muted-foreground mt-1">
                Riwayat izin terverifikasi
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Action Shortcuts */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link href="/wali-kelas/siswa" className="block">
            <Card className="p-4 hover:border-primary/50 transition cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                  <Users className="h-5 w-5" />
                </div>
                <div>
                  <h4 className="text-sm font-semibold">Data Siswa Binaan</h4>
                  <p className="text-xs text-muted-foreground mt-0.5">Daftar siswa dan status akun kelas</p>
                </div>
              </div>
            </Card>
          </Link>

          <Link href="/wali-kelas/izin" className="block">
            <Card className="p-4 hover:border-primary/50 transition cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                  <ClipboardList className="h-5 w-5" />
                </div>
                <div>
                  <h4 className="text-sm font-semibold">Verifikasi Izin Rombel</h4>
                  <p className="text-xs text-muted-foreground mt-0.5">Setujui atau tolak izin siswa</p>
                </div>
              </div>
            </Card>
          </Link>

          <Link href="/wali-kelas/presensi" className="block">
            <Card className="p-4 hover:border-primary/50 transition cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                  <FileText className="h-5 w-5" />
                </div>
                <div>
                  <h4 className="text-sm font-semibold">Rekap Presensi Rombel</h4>
                  <p className="text-xs text-muted-foreground mt-0.5">Pantau persentase kehadiran siswa</p>
                </div>
              </div>
            </Card>
          </Link>
        </div>

        {/* Recent Leave Requests Preview */}
        <Card className="p-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
              <h3 className="text-lg font-semibold tracking-tight">Pengajuan Izin Terbaru Rombel</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Surat izin siswa dari kelas {classNames.join(', ') || 'binaan Anda'}.
              </p>
            </div>
            <Link href="/wali-kelas/izin">
              <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                <span>Lihat Semua Izin</span>
                <ArrowRight className="h-3.5 w-3.5" />
              </Button>
            </Link>
          </div>

          <div className="rounded-md border border-border overflow-x-auto w-full touch-pan-x">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead>Kelas</TableHead>
                  <TableHead>Tipe</TableHead>
                  <TableHead>Tanggal Izin</TableHead>
                  <TableHead>Alasan</TableHead>
                  <TableHead className="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {recentLeaves.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                      Belum ada permohonan izin baru dari siswa di rombel binaan Anda.
                    </TableCell>
                  </TableRow>
                ) : (
                  recentLeaves.map((leave) => (
                    <TableRow key={leave.id}>
                      <TableCell className="font-medium">
                        {leave.student?.name ?? `Siswa #${leave.id}`}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{leave.student?.kelas ?? '-'}</Badge>
                      </TableCell>
                      <TableCell>
                        <Badge variant={leave.type === 'sakit' ? 'secondary' : 'outline'} className="capitalize">
                          {leave.type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                        {formatDateRangeIndo(leave.start_date, leave.end_date)}
                      </TableCell>
                      <TableCell>
                        <button
                          type="button"
                          onClick={() => setSelectedLeave(leave)}
                          className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 transition-colors whitespace-nowrap"
                        >
                          <Eye className="h-3.5 w-3.5" />
                          <span>Lihat Detail</span>
                        </button>
                      </TableCell>
                      <TableCell className="text-right">
                        <Link href="/wali-kelas/izin">
                          <Button size="sm" variant="secondary" className="text-xs">
                            Verifikasi
                          </Button>
                        </Link>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </Card>
      </div>

      <LeaveDetailModal
        isOpen={!!selectedLeave}
        onClose={() => setSelectedLeave(null)}
        leave={selectedLeave}
      />
    </AppLayout>
  );
}

