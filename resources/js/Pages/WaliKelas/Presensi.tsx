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
    total_hadir: number;
    total_sakit: number;
    total_izin: number;
    total_alpa: number;
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

  const handlePrint = () => {
    window.print();
  };

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

        {/* Summary Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          <Card className="p-3 text-center">
            <span className="text-xs text-muted-foreground font-medium">Total Siswa</span>
            <p className="text-xl font-bold mt-0.5">{summary.total_students}</p>
          </Card>
          <Card className="p-3 text-center">
            <span className="text-xs text-muted-foreground font-medium text-emerald-600">Total Hadir</span>
            <p className="text-xl font-bold mt-0.5 text-emerald-600">{summary.total_hadir}</p>
          </Card>
          <Card className="p-3 text-center">
            <span className="text-xs text-muted-foreground font-medium text-amber-600">Total Sakit</span>
            <p className="text-xl font-bold mt-0.5 text-amber-600">{summary.total_sakit}</p>
          </Card>
          <Card className="p-3 text-center">
            <span className="text-xs text-muted-foreground font-medium text-sky-600">Total Izin</span>
            <p className="text-xl font-bold mt-0.5 text-sky-600">{summary.total_izin}</p>
          </Card>
          <Card className="p-3 text-center col-span-2 sm:col-span-1">
            <span className="text-xs text-muted-foreground font-medium text-destructive">Total Alpa</span>
            <p className="text-xl font-bold mt-0.5 text-destructive">{summary.total_alpa}</p>
          </Card>
        </div>

        {/* Filter Card */}
        <Card className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
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
                onChange={(e) => setStartDate(e.target.value)}
                className="h-9 text-xs"
              />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground mb-1 block">Tanggal Selesai</label>
              <Input
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
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
