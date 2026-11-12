import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
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
  FileText, 
  Calendar, 
  Filter, 
  Printer, 
  CheckCircle2, 
  AlertCircle 
} from 'lucide-react';

interface RekapItem {
  id: number;
  nis: string;
  name: string;
  gender: string;
  hadir: number;
  sakit: number;
  izin: number;
  alpa: number;
  total: number;
  persentase: number;
}

interface PresensiProps {
  classNames: string[];
  selectedClass: string;
  rekapData: RekapItem[];
  summary: {
    total_students: number;
    is_single_day?: boolean;
    total_sessions?: number;
    headcount_hadir?: number;
    headcount_sakit?: number;
    headcount_izin?: number;
    headcount_alpa?: number;
    total_hadir: number;
    total_sakit: number;
    total_izin: number;
    total_alpa: number;
    class_attendance_rate?: number;
  };
  filters: {
    kelas: string;
    start_date: string;
    end_date: string;
  };
}

export default function Presensi({
  classNames = [],
  selectedClass,
  rekapData = [],
  summary,
  filters,
}: PresensiProps) {
  const [kelas, setKelas] = useState(filters.kelas || selectedClass);
  const [startDate, setStartDate] = useState(filters.start_date || '');
  const [endDate, setEndDate] = useState(filters.end_date || '');

  const handleFilter = () => {
    router.get('/wali-kelas/presensi', {
      kelas,
      start_date: startDate,
      end_date: endDate,
    }, { preserveState: true });
  };

  const applyPreset = (preset: 'today' | 'this_month' | 'semester' | 'all') => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    const todayStr = `${y}-${m}-${d}`;

    let s = '';
    let e = '';

    if (preset === 'today') {
      s = todayStr;
      e = todayStr;
    } else if (preset === 'this_month') {
      const lastDay = new Date(y, now.getMonth() + 1, 0).getDate();
      s = `${y}-${m}-01`;
      e = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
    } else if (preset === 'semester') {
      const monthNum = now.getMonth() + 1;
      if (monthNum >= 7) {
        s = `${y}-07-01`;
        e = `${y}-12-31`;
      } else {
        s = `${y}-01-01`;
        e = `${y}-06-30`;
      }
    } else if (preset === 'all') {
      s = '';
      e = '';
    }

    setStartDate(s);
    setEndDate(e);

    router.get('/wali-kelas/presensi', {
      kelas,
      start_date: s,
      end_date: e,
    }, { preserveState: true });
  };

  const handlePrint = () => {
    window.print();
  };

  const isSingleDay = summary.is_single_day || (startDate && endDate && startDate === endDate);

  return (
    <AppLayout title="Rekap Presensi Rombel">
      <Head title="Rekap Presensi Rombel - Wali Kelas" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Rekapitulasi Kehadiran Siswa Rombel</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Pantau tingkat kehadiran, izin, dan alpa siswa kelas {selectedClass || classNames.join(', ')}.
            </p>
          </div>
          <Button onClick={handlePrint} variant="outline" size="sm" className="gap-1.5 text-xs">
            <Printer className="h-4 w-4" />
            <span>Cetak Rekap</span>
          </Button>
        </div>

        {/* Filter Card */}
        <Card className="p-4 space-y-3">
          {/* Tombol Pintas Filter */}
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-xs font-semibold text-muted-foreground mr-1">Filter Cepat:</span>
            <Button
              type="button"
              size="sm"
              variant={startDate === endDate && startDate ? "default" : "outline"}
              onClick={() => applyPreset('today')}
              className="h-7 text-xs px-3 rounded-full"
            >
              Hari Ini
            </Button>
            <Button
              type="button"
              size="sm"
              variant={startDate && endDate && startDate !== endDate && !startDate.endsWith('-07-01') && !startDate.endsWith('-01-01') ? "default" : "outline"}
              onClick={() => applyPreset('this_month')}
              className="h-7 text-xs px-3 rounded-full"
            >
              Bulan Ini
            </Button>
            <Button
              type="button"
              size="sm"
              variant={startDate && (startDate.endsWith('-07-01') || startDate.endsWith('-01-01')) ? "default" : "outline"}
              onClick={() => applyPreset('semester')}
              className="h-7 text-xs px-3 rounded-full"
            >
              Semester Ini
            </Button>
            <Button
              type="button"
              size="sm"
              variant={!startDate && !endDate ? "default" : "outline"}
              onClick={() => applyPreset('all')}
              className="h-7 text-xs px-3 rounded-full"
            >
              Semua Riwayat
            </Button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-1 border-t border-border/50">
            <div>
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Kelas Binaan</label>
              <select
                value={kelas}
                onChange={(e) => setKelas(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                {classNames.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Tanggal Mulai</label>
              <Input
                type="date"
                value={startDate}
                onChange={(e) => {
                  const val = e.target.value;
                  setStartDate(val);
                  if (endDate && val && endDate < val) {
                    setEndDate(val);
                  }
                }}
                className="h-9 text-xs"
              />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Tanggal Selesai</label>
              <Input
                type="date"
                value={endDate}
                min={startDate || undefined}
                onChange={(e) => {
                  const val = e.target.value;
                  const minAllowed = startDate;
                  if (minAllowed && val && val < minAllowed) {
                    setEndDate(minAllowed);
                  } else {
                    setEndDate(val);
                  }
                }}
                className="h-9 text-xs"
              />
            </div>
            <div className="flex items-end">
              <Button onClick={handleFilter} size="sm" className="h-9 w-full gap-1 text-xs">
                <Filter className="h-3.5 w-3.5" />
                <span>Terapkan Filter</span>
              </Button>
            </div>
          </div>
        </Card>

        {/* Summary Cards (Adaptif: Mode 1 Hari vs Mode Periode KBM) */}
        {isSingleDay ? (
          <div className="grid grid-cols-2 sm:grid-cols-6 gap-3">
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Total Siswa</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_students}</p>
              <span className="text-[10px] text-muted-foreground block">Kelas {selectedClass}</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Siswa Hadir (H)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.headcount_hadir ?? summary.total_hadir}</p>
              <span className="text-[10px] text-muted-foreground block">Siswa Mengikuti</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Siswa Sakit (S)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.headcount_sakit ?? summary.total_sakit}</p>
              <span className="text-[10px] text-muted-foreground block">Surat Dokter</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Siswa Izin (I)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.headcount_izin ?? summary.total_izin}</p>
              <span className="text-[10px] text-muted-foreground block">Izin Disetujui</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Siswa Alpa (A)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.headcount_alpa ?? summary.total_alpa}</p>
              <span className="text-[10px] text-muted-foreground block">Tanpa Keterangan</span>
            </Card>
            <Card className="p-3 text-center col-span-2 sm:col-span-1 bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Kehadiran Kelas</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.class_attendance_rate ?? 100}%</p>
              <span className="text-[10px] text-muted-foreground block">Tingkat Disiplin</span>
            </Card>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-6 gap-3">
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Total Siswa</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_students}</p>
              <span className="text-[10px] text-muted-foreground block">Siswa Terdaftar</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Pertemuan KBM</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_sessions ?? 0}</p>
              <span className="text-[10px] text-muted-foreground block">Sesi Terlaksana</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Total Sakit (S)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_sakit}</p>
              <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Total Izin (I)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_izin}</p>
              <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
            </Card>
            <Card className="p-3 text-center bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Total Alpa (A)</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.total_alpa}</p>
              <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
            </Card>
            <Card className="p-3 text-center col-span-2 sm:col-span-1 bg-muted/20">
              <span className="text-[11px] text-muted-foreground font-medium block">Rata-rata Kehadiran</span>
              <p className="text-xl font-bold mt-0.5 text-foreground">{summary.class_attendance_rate ?? 100}%</p>
              <span className="text-[10px] text-muted-foreground block">Kehadiran Rombel</span>
            </Card>
          </div>
        )}

        {/* Recap Table */}
        <Card>
          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12">No</TableHead>
                  <TableHead>NIS</TableHead>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead>Gender</TableHead>
                  <TableHead className="text-center text-emerald-600 font-semibold">Hadir</TableHead>
                  <TableHead className="text-center text-amber-600 font-semibold">Sakit</TableHead>
                  <TableHead className="text-center text-sky-600 font-semibold">Izin</TableHead>
                  <TableHead className="text-center text-destructive font-semibold">Alpa</TableHead>
                  <TableHead className="text-center">Total KBM</TableHead>
                  <TableHead className="text-right">Persentase</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rekapData.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={10} className="h-24 text-center text-muted-foreground">
                      Belum ada data presensi untuk kelas binaan ini.
                    </TableCell>
                  </TableRow>
                ) : (
                  rekapData.map((row, idx) => (
                    <TableRow key={row.id}>
                      <TableCell className="text-xs text-muted-foreground">{idx + 1}</TableCell>
                      <TableCell className="font-mono text-xs text-muted-foreground">{row.nis}</TableCell>
                      <TableCell className="font-semibold text-sm">{row.name}</TableCell>
                      <TableCell className="text-xs">{row.gender}</TableCell>
                      <TableCell className="text-center font-medium text-emerald-600">{row.hadir}</TableCell>
                      <TableCell className="text-center font-medium text-amber-600">{row.sakit}</TableCell>
                      <TableCell className="text-center font-medium text-sky-600">{row.izin}</TableCell>
                      <TableCell className="text-center font-medium text-destructive">{row.alpa}</TableCell>
                      <TableCell className="text-center text-xs font-semibold">{row.total}</TableCell>
                      <TableCell className="text-right">
                        <Badge 
                          variant={row.persentase >= 80 ? 'secondary' : 'destructive'} 
                          className="font-mono text-xs"
                        >
                          {row.persentase}%
                        </Badge>
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
