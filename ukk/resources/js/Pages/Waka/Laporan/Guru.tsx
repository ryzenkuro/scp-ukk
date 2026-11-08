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
  Users, 
  Calendar, 
  BookOpen, 
  CheckCircle2, 
  AlertCircle, 
  ArrowRight,
  Filter,
  FileText
} from 'lucide-react';

interface RekapGuruItem {
  id: number;
  username: string;
  fullname: string;
  nip: string;
  total_jadwal: number;
  total_jurnal: number;
  persentase: number;
  status_lengkap: boolean;
}

interface GuruProps {
  rekapGuru: RekapGuruItem[];
  period: string;
  referenceDate: string;
  reportStart: string;
  reportEnd: string;
  summary: {
    total_guru: number;
    total_jadwal: number;
    total_jurnal: number;
    active_cycle: number;
  };
}

export default function GuruReport({
  rekapGuru = [],
  period = 'bulan',
  referenceDate,
  reportStart,
  reportEnd,
  summary,
}: GuruProps) {
  const [currentPeriod, setCurrentPeriod] = useState(period);
  const [currentDate, setCurrentDate] = useState(referenceDate);

  const handleFilter = () => {
    router.get('/waka/laporan/guru', {
      period: currentPeriod,
      date: currentDate,
    }, { preserveState: true });
  };

  return (
    <AppLayout title="Audit Kinerja Jurnal Guru">
      <Head title="Audit Kinerja Guru - Waka" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Rekapitulasi Kinerja & Audit Jurnal Guru</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Pantau kepatuhan pengisian jurnal KBM dan kehadiran mengajar seluruh tenaga pendidik sekolah.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Link href="/waka/jadwal/export-pdf" target="_blank">
              <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                <FileText className="h-4 w-4" />
                <span>Cetak Jadwal Pelajaran (A4)</span>
              </Button>
            </Link>
          </div>
        </div>

        {/* Summary KPIs */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <Card className="p-4">
            <span className="text-xs text-muted-foreground font-medium">Total Guru Pengajar</span>
            <p className="text-2xl font-bold mt-1">{summary.total_guru}</p>
            <p className="text-[11px] text-muted-foreground mt-0.5">Pendidik aktif terdata</p>
          </Card>
          <Card className="p-4">
            <span className="text-xs text-muted-foreground font-medium">Jadwal Mengajar (Putaran {summary.active_cycle})</span>
            <p className="text-2xl font-bold mt-1 text-primary">{summary.total_jadwal}</p>
            <p className="text-[11px] text-muted-foreground mt-0.5">Sesi KBM terdistribusi</p>
          </Card>
          <Card className="p-4">
            <span className="text-xs text-muted-foreground font-medium">Total Jurnal Ditulis</span>
            <p className="text-2xl font-bold mt-1 text-emerald-600">{summary.total_jurnal}</p>
            <p className="text-[11px] text-muted-foreground mt-0.5">Periode {period}</p>
          </Card>
          <Card className="p-4">
            <span className="text-xs text-muted-foreground font-medium">Rentang Laporan</span>
            <p className="text-sm font-semibold mt-2">{reportStart} s/d {reportEnd}</p>
            <p className="text-[11px] text-muted-foreground mt-0.5 capitalize">Filter: {period}</p>
          </Card>
        </div>

        {/* Filter Bar */}
        <Card className="p-4">
          <div className="flex flex-col sm:flex-row items-center gap-3">
            <div className="w-full sm:w-48">
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Periode Audit</label>
              <select
                value={currentPeriod}
                onChange={(e) => setCurrentPeriod(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="hari">Harian</option>
                <option value="minggu">Mingguan</option>
                <option value="bulan">Bulanan</option>
                <option value="all">Semua Waktu</option>
              </select>
            </div>

            <div className="w-full sm:w-48">
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Tanggal Patokan</label>
              <Input
                type="date"
                value={currentDate}
                onChange={(e) => setCurrentDate(e.target.value)}
                className="h-9 text-xs"
              />
            </div>

            <div className="flex items-end w-full sm:w-auto pt-5 sm:pt-0">
              <Button onClick={handleFilter} size="sm" className="h-9 gap-1.5 text-xs w-full sm:w-auto">
                <Filter className="h-3.5 w-3.5" />
                <span>Terapkan Filter</span>
              </Button>
            </div>
          </div>
        </Card>

        {/* Table of Teachers */}
        <Card>
          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12">No</TableHead>
                  <TableHead>Nama Guru / Tenaga Pendidik</TableHead>
                  <TableHead>Username / Kontak</TableHead>
                  <TableHead className="text-center">Jadwal Mengajar</TableHead>
                  <TableHead className="text-center">Jurnal Ditulis</TableHead>
                  <TableHead className="text-center">Kepatuhan (%)</TableHead>
                  <TableHead className="text-center">Status</TableHead>
                  <TableHead className="text-right">Aksi Drill-Down</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rekapGuru.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                      Belum ada data tenaga pendidik terdaftar.
                    </TableCell>
                  </TableRow>
                ) : (
                  rekapGuru.map((guru, idx) => (
                    <TableRow key={guru.id}>
                      <TableCell className="text-xs text-muted-foreground">{idx + 1}</TableCell>
                      <TableCell>
                        <Link 
                          href={`/waka/laporan/guru/${guru.id}`}
                          className="font-bold text-sm text-primary hover:underline"
                        >
                          {guru.fullname}
                        </Link>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground font-mono">
                        {guru.username} {guru.nip !== '-' ? `• ${guru.nip}` : ''}
                      </TableCell>
                      <TableCell className="text-center font-medium">
                        {guru.total_jadwal} sesi
                      </TableCell>
                      <TableCell className="text-center font-bold text-emerald-600">
                        {guru.total_jurnal} jurnal
                      </TableCell>
                      <TableCell className="text-center font-mono text-xs">
                        {guru.persentase}%
                      </TableCell>
                      <TableCell className="text-center">
                        {guru.status_lengkap ? (
                          <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-xs">
                            <CheckCircle2 className="mr-1 h-3 w-3" /> Lengkap
                          </Badge>
                        ) : (
                          <Badge variant="outline" className="text-amber-600 border-amber-300 bg-amber-50 text-xs">
                            <AlertCircle className="mr-1 h-3 w-3" /> Belum Lengkap
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <Link href={`/waka/laporan/guru/${guru.id}`}>
                          <Button variant="secondary" size="sm" className="gap-1 text-xs h-7">
                            <span>Lihat Jurnal</span>
                            <ArrowRight className="h-3 w-3" />
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
    </AppLayout>
  );
}

