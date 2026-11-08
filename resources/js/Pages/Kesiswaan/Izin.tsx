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
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/Components/ui/dialog';
import { 
  ClipboardList, 
  Search, 
  Filter, 
  CheckCircle2, 
  XCircle, 
  Clock, 
  ExternalLink,
  FileText,
  Printer,
  Calendar,
  RotateCcw,
  Eye
} from 'lucide-react';

interface LeaveItem {
  id: number;
  type: string;
  start_date: string;
  end_date: string;
  reason: string;
  attachment_path?: string;
  status: 'pending' | 'approved' | 'rejected';
  rejection_reason?: string;
  verified_at?: string;
  student?: {
    id: number;
    name: string;
    nis: string;
    kelas: string;
  };
  verifier?: {
    fullname?: string;
    username: string;
  };
}

interface PaginatedLeaves {
  data: LeaveItem[];
  current_page: number;
  last_page: number;
  total: number;
  links: { url: string | null; label: string; active: boolean }[];
}

interface IzinProps {
  leaves: PaginatedLeaves;
  kelasList: string[];
  stats: {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
  };
  filters: {
    status: string;
    type: string;
    kelas: string;
    start_date: string;
    end_date: string;
    search: string;
  };
}

export default function Izin({ leaves, kelasList = [], stats, filters }: IzinProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
  const [typeFilter, setTypeFilter] = useState(filters.type || 'all');
  const [classFilter, setClassFilter] = useState(filters.kelas || 'all');
  const [startDate, setStartDate] = useState(filters.start_date || '');
  const [endDate, setEndDate] = useState(filters.end_date || '');

  const [previewAttachment, setPreviewAttachment] = useState<string | null>(null);

  const handleFilter = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    router.get('/kesiswaan/izin', {
      search,
      status: statusFilter,
      type: typeFilter,
      kelas: classFilter,
      start_date: startDate,
      end_date: endDate,
    }, { preserveState: true });
  };

  const handleResetFilter = () => {
    setSearch('');
    setStatusFilter('all');
    setTypeFilter('all');
    setClassFilter('all');
    setStartDate('');
    setEndDate('');
    router.get('/kesiswaan/izin', {}, { preserveState: true });
  };

  const handlePrint = () => {
    window.print();
  };

  const getTypeBadge = (type: string) => {
    switch (type.toLowerCase()) {
      case 'sakit':
        return <Badge className="bg-amber-500/10 text-amber-600 border-amber-500/20 text-xs">Sakit</Badge>;
      case 'izin':
        return <Badge className="bg-blue-500/10 text-blue-600 border-blue-500/20 text-xs">Izin</Badge>;
      case 'dispen':
      case 'dispensasi':
        return <Badge className="bg-purple-500/10 text-purple-600 border-purple-500/20 text-xs">Dispensasi</Badge>;
      default:
        return <Badge variant="secondary" className="capitalize text-xs">{type}</Badge>;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'approved':
        return (
          <Badge className="bg-emerald-500/10 text-emerald-600 border-emerald-500/20 gap-1 text-xs">
            <CheckCircle2 className="h-3 w-3" /> Disetujui
          </Badge>
        );
      case 'rejected':
        return (
          <Badge className="bg-destructive/10 text-destructive border-destructive/20 gap-1 text-xs">
            <XCircle className="h-3 w-3" /> Ditolak
          </Badge>
        );
      case 'pending':
      default:
        return (
          <Badge className="bg-amber-500/10 text-amber-600 border-amber-500/20 gap-1 text-xs">
            <Clock className="h-3 w-3" /> Menunggu Wali Kelas
          </Badge>
        );
    }
  };

  return (
    <AppLayout title="Rekap Izin Rombel">
      <Head title="Rekap Izin Rombel - Tim Kesiswaan" />

      <div className="space-y-6">
        {/* Header & Print Button */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Monitoring Kesiswaan
              </span>
              <Badge variant="outline" className="text-[10px]">
                Seluruh Rombel
              </Badge>
            </div>
            <h2 className="text-2xl font-bold tracking-tight mt-1">Rekap Izin Rombel Siswa</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Rekapitulasi dan pemantauan permohonan izin/sakit siswa di seluruh rombongan belajar sekolah.
            </p>
          </div>
          <Button onClick={handlePrint} variant="outline" size="sm" className="gap-2 text-xs shadow-sm">
            <Printer className="h-4 w-4" />
            <span>Cetak Rekap Izin</span>
          </Button>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <Card className="p-4 flex items-center justify-between">
            <div>
              <span className="text-xs text-muted-foreground font-medium block">Total Izin Diajukan</span>
              <span className="text-2xl font-bold text-foreground mt-1 block">{stats.total}</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <ClipboardList className="h-5 w-5" />
            </div>
          </Card>

          <Card className="p-4 flex items-center justify-between border-amber-500/20 bg-amber-500/5">
            <div>
              <span className="text-xs text-amber-600 font-medium block">Menunggu Wali Kelas</span>
              <span className="text-2xl font-bold text-amber-600 mt-1 block">{stats.pending}</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/20 text-amber-600">
              <Clock className="h-5 w-5" />
            </div>
          </Card>

          <Card className="p-4 flex items-center justify-between border-emerald-500/20 bg-emerald-500/5">
            <div>
              <span className="text-xs text-emerald-600 font-medium block">Telah Disetujui</span>
              <span className="text-2xl font-bold text-emerald-600 mt-1 block">{stats.approved}</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-600">
              <CheckCircle2 className="h-5 w-5" />
            </div>
          </Card>

          <Card className="p-4 flex items-center justify-between border-destructive/20 bg-destructive/5">
            <div>
              <span className="text-xs text-destructive font-medium block">Ditolak Wali Kelas</span>
              <span className="text-2xl font-bold text-destructive mt-1 block">{stats.rejected}</span>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive/20 text-destructive">
              <XCircle className="h-5 w-5" />
            </div>
          </Card>
        </div>

        {/* Filter Toolbar */}
        <Card className="p-4">
          <form onSubmit={handleFilter} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Pilih Rombel / Kelas
              </label>
              <select
                value={classFilter}
                onChange={(e) => setClassFilter(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm font-medium"
              >
                <option value="all">Semua Rombel</option>
                {kelasList.map((k) => (
                  <option key={k} value={k}>
                    Kelas {k}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Status Verifikasi
              </label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
              >
                <option value="all">Semua Status</option>
                <option value="pending">Menunggu Wali Kelas</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
              </select>
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Jenis Izin
              </label>
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
              >
                <option value="all">Semua Jenis</option>
                <option value="sakit">Sakit</option>
                <option value="izin">Izin</option>
                <option value="dispen">Dispensasi</option>
              </select>
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Mulai Tanggal
              </label>
              <Input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
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
                onChange={(e) => setEndDate(e.target.value)}
                className="h-9"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-muted-foreground block mb-1">
                Pencarian
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
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm" 
                  onClick={handleResetFilter}
                  className="h-9 w-9 p-0 shrink-0"
                  title="Reset Filter"
                >
                  <RotateCcw className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          </form>
        </Card>

        {/* Tabel Rekap Izin */}
        <Card className="overflow-hidden">
          <div className="px-6 py-3.5 border-b border-border bg-card/60 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <ClipboardList className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm font-semibold">
                Daftar Permohonan Izin Siswa
              </span>
              <Badge variant="outline" className="text-xs font-normal">
                Total: {leaves.total}
              </Badge>
            </div>
          </div>

          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead>Nama Siswa & NIS</TableHead>
                  <TableHead className="w-28">Rombel</TableHead>
                  <TableHead className="w-24 text-center">Jenis</TableHead>
                  <TableHead className="w-36">Periode Izin</TableHead>
                  <TableHead>Alasan / Keterangan</TableHead>
                  <TableHead className="w-24 text-center">Surat Bukti</TableHead>
                  <TableHead className="w-44">Status & Verifikator</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {leaves.data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="h-32 text-center text-muted-foreground">
                      Tidak ada permohonan izin siswa yang ditemukan pada kriteria filter ini.
                    </TableCell>
                  </TableRow>
                ) : (
                  leaves.data.map((leave, idx) => (
                    <TableRow key={leave.id}>
                      <TableCell className="text-center font-mono text-xs">
                        {(leaves.current_page - 1) * 20 + idx + 1}
                      </TableCell>
                      <TableCell>
                        <div className="font-semibold text-foreground">{leave.student?.name || '-'}</div>
                        <div className="font-mono text-xs text-muted-foreground">NIS: {leave.student?.nis || '-'}</div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="font-medium text-xs">
                          {leave.student?.kelas || '-'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center">
                        {getTypeBadge(leave.type)}
                      </TableCell>
                      <TableCell className="text-xs">
                        <div className="font-medium">{leave.start_date}</div>
                        {leave.start_date !== leave.end_date && (
                          <div className="text-muted-foreground">s/d {leave.end_date}</div>
                        )}
                      </TableCell>
                      <TableCell className="text-xs max-w-xs truncate" title={leave.reason}>
                        <p className="truncate">{leave.reason}</p>
                        {leave.rejection_reason && (
                          <p className="text-[11px] text-destructive italic mt-0.5">
                            Alasan ditolak: {leave.rejection_reason}
                          </p>
                        )}
                      </TableCell>
                      <TableCell className="text-center">
                        {leave.attachment_path ? (
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 gap-1 text-xs text-primary"
                            onClick={() => setPreviewAttachment(`/storage/${leave.attachment_path}`)}
                          >
                            <Eye className="h-3.5 w-3.5" />
                            <span>Lihat</span>
                          </Button>
                        ) : (
                          <span className="text-xs text-muted-foreground/60 italic">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="space-y-1">
                          {getStatusBadge(leave.status)}
                          {leave.verifier && (
                            <div className="text-[11px] text-muted-foreground">
                              Oleh: <span className="font-medium text-foreground">{leave.verifier.fullname || leave.verifier.username}</span> (Wali Kelas)
                            </div>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {/* Pagination */}
          {leaves.last_page > 1 && (
            <div className="flex items-center justify-between border-t border-border px-6 py-4">
              <span className="text-xs text-muted-foreground">
                Halaman {leaves.current_page} dari {leaves.last_page} (Total {leaves.total} permohonan izin)
              </span>
              <div className="flex items-center gap-1">
                {leaves.links.map((link, i) => (
                  <Button
                    key={i}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                    className="h-8 text-xs"
                    dangerouslySetInnerHTML={{ __html: link.label }}
                  />
                ))}
              </div>
            </div>
          )}
        </Card>

        {/* Dialog Pratinjau Surat Lampiran */}
        <Dialog open={!!previewAttachment} onOpenChange={(open) => !open && setPreviewAttachment(null)}>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5 text-primary" />
                <span>Lampiran Bukti Surat Izin</span>
              </DialogTitle>
            </DialogHeader>

            <div className="p-4 flex items-center justify-center bg-muted/40 rounded-lg border border-border">
              {previewAttachment && (
                <img
                  src={previewAttachment}
                  alt="Surat Bukti Izin"
                  className="max-h-[500px] w-auto object-contain rounded shadow-sm"
                  onError={(e) => {
                    (e.target as HTMLElement).style.display = 'none';
                  }}
                />
              )}
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setPreviewAttachment(null)}>
                Tutup
              </Button>
              {previewAttachment && (
                <Button asChild>
                  <a href={previewAttachment} target="_blank" rel="noreferrer" className="gap-1.5">
                    <ExternalLink className="h-4 w-4" />
                    <span>Buka File Asli</span>
                  </a>
                </Button>
              )}
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  );
}
