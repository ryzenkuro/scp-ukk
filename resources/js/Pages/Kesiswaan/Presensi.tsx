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
  Search,
  Users,
  Percent,
  XCircle,
  Clock
} from 'lucide-react';

interface RekapItem {
  id: number;
  nis: string;
  name: string;
  kelas: string;
  gender: string;
  hadir: number;
  sakit: number;
  izin: number;
  alpa: number;
  total: number;
  persentase: number;
}

interface PresensiProps {
  kelasList: string[];
  selectedClass: string;
  rekapData: RekapItem[];
  summary: {
    total_students: number;
    total_hadir: number;
    total_sakit: number;
    total_izin: number;
    total_alpa: number;
    avg_attendance: number;
  };
  filters: {
    kelas: string;
    start_date: string;
    end_date: string;
    search: string;
  };
}

export default function Presensi({
  kelasList = [],
  selectedClass,
  rekapData = [],
  summary,
  filters,
}: PresensiProps) {
  const [kelas, setKelas] = useState(filters.kelas || selectedClass || kelasList[0] || '');
  const [startDate, setStartDate] = useState(filters.start_date || '');
  const [endDate, setEndDate] = useState(filters.end_date || '');
  const [search, setSearch] = useState(filters.search || '');

  const handleFilter = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    router.get('/bk/presensi', {
      kelas,
      start_date: startDate,
      end_date: endDate,
      search,
    }, { preserveState: true });
  };

  const handleClassChange = (newClass: string) => {
    setKelas(newClass);
    router.get('/bk/presensi', {
      kelas: newClass,
      start_date: startDate,
      end_date: endDate,
      search,
    }, { preserveState: true });
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <AppLayout title="Rekap Presensi Rombel">
      <Head title="Rekap Presensi Rombel - Guru BK" />

      <div className="space-y-6">
        {/* Header & Print Button */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Monitoring Guru BK
              </span>
              <Badge variant="outline" className="text-[10px]">
                Tingkat Sekolah
              </Badge>
            </div>
            <h2 className="text-2xl font-bold tracking-tight mt-1">Rekap Presensi Rombel</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Pantau rekapitulasi kehadiran, sakit, izin, dan alpa siswa per rombongan belajar sekolah.
            </p>
          </div>
          <Button onClick={handlePrint} variant="outline" size="sm" className="gap-2 text-xs shadow-sm">
            <Printer className="h-4 w-4" />
            <span>Cetak Rekap Presensi</span>
          </Button>
        </div>

        {/* Summary Metric Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <Card className="p-3 text-center">
            <span className="text-xs text-muted-foreground block">Total Siswa</span>
            <span className="text-xl font-bold text-foreground mt-1 block">
              {summary.total_students}
            </span>
          </Card>
          <Card className="p-3 text-center border-emerald-500/20 bg-emerald-500/5">
            <span className="text-xs text-emerald-600 block font-medium">Total Hadir (H)</span>
            <span className="text-xl font-bold text-emerald-600 mt-1 block">
              {summary.total_hadir}
            </span>
          </Card>
          <Card className="p-3 text-center border-amber-500/20 bg-amber-500/5">
            <span className="text-xs text-amber-600 block font-medium">Total Sakit (S)</span>
            <span className="text-xl font-bold text-amber-600 mt-1 block">
              {summary.total_sakit}
            </span>
          </Card>
          <Card className="p-3 text-center border-blue-500/20 bg-blue-500/5">
            <span className="text-xs text-blue-600 block font-medium">Total Izin (I)</span>
            <span className="text-xl font-bold text-blue-600 mt-1 block">
              {summary.total_izin}
            </span>
          </Card>
          <Card className="p-3 text-center border-destructive/20 bg-destructive/5">
            <span className="text-xs text-destructive block font-medium">Total Alpa (A)</span>
            <span className="text-xl font-bold text-destructive mt-1 block">
              {summary.total_alpa}
            </span>
          </Card>
          <Card className="p-3 text-center border-primary/20 bg-primary/5">
            <span className="text-xs text-primary block font-medium">Rata-rata Kehadiran</span>
            <span className="text-xl font-bold text-primary mt-1 block">
              {summary.avg_attendance}%
            </span>
          </Card>
        </div>

        {/* Filter Toolbar */}
        <Card className="p-4">
          <form onSubmit={handleFilter} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Pilih Rombel / Kelas
              </label>
              <select
                value={kelas}
                onChange={(e) => handleClassChange(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm font-medium"
              >
                {kelasList.map((k) => (
                  <option key={k} value={k}>
                    Kelas {k}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Mulai Tanggal
              </label>
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
                className="h-9"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Sampai Tanggal
              </label>
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
                className="h-9"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Cari Siswa
              </label>
              <div className="flex gap-2">
                <div className="relative flex-1">
                  <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Nama / NIS..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-8 h-9 text-xs"
                  />
                </div>
                <Button type="submit" size="sm" className="h-9 gap-1 text-xs shrink-0">
                  <Filter className="h-3.5 w-3.5" />
                  <span>Filter</span>
                </Button>
              </div>
            </div>
          </form>
        </Card>

        {/* Tabel Rekapitulasi Presensi */}
        <Card className="overflow-hidden">
          <div className="px-6 py-3.5 border-b border-border bg-card/60 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Users className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm font-semibold">
                Daftar Kehadiran Siswa Kelas {kelas}
              </span>
              <Badge variant="outline" className="text-xs font-normal">
                {rekapData.length} Siswa
              </Badge>
            </div>
          </div>

          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead className="w-28">NIS</TableHead>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead className="w-16 text-center">L/P</TableHead>
                  <TableHead className="w-20 text-center font-bold text-emerald-600">Hadir</TableHead>
                  <TableHead className="w-20 text-center font-bold text-amber-600">Sakit</TableHead>
                  <TableHead className="w-20 text-center font-bold text-blue-600">Izin</TableHead>
                  <TableHead className="w-20 text-center font-bold text-destructive">Alpa</TableHead>
                  <TableHead className="w-24 text-center">Total Sesi</TableHead>
                  <TableHead className="w-28 text-center font-bold">Persentase</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rekapData.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={10} className="h-32 text-center text-muted-foreground">
                      Tidak ada data siswa atau presensi pada filter ini.
                    </TableCell>
                  </TableRow>
                ) : (
                  rekapData.map((row, idx) => (
                    <TableRow key={row.id}>
                      <TableCell className="text-center font-mono text-xs">{idx + 1}</TableCell>
                      <TableCell className="font-mono text-xs">{row.nis}</TableCell>
                      <TableCell className="font-medium text-foreground">{row.name}</TableCell>
                      <TableCell className="text-center text-xs">{row.gender}</TableCell>
                      <TableCell className="text-center font-semibold text-emerald-600">{row.hadir}</TableCell>
                      <TableCell className="text-center font-semibold text-amber-600">{row.sakit}</TableCell>
                      <TableCell className="text-center font-semibold text-blue-600">{row.izin}</TableCell>
                      <TableCell className="text-center font-semibold text-destructive">{row.alpa}</TableCell>
                      <TableCell className="text-center font-mono text-xs text-muted-foreground">{row.total}</TableCell>
                      <TableCell className="text-center">
                        <Badge 
                          variant={row.persentase >= 85 ? 'default' : row.persentase >= 75 ? 'secondary' : 'destructive'}
                          className="font-mono text-xs font-semibold"
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

