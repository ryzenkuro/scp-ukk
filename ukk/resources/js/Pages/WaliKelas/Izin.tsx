import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
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
  DialogDescription,
  DialogFooter,
} from '@/Components/ui/dialog';
import { 
  ClipboardList, 
  Search, 
  CheckCircle2, 
  XCircle, 
  Clock, 
  ExternalLink 
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
  classNames: string[];
  leaves: PaginatedLeaves;
  stats: {
    pending: number;
    approved: number;
    rejected: number;
  };
  filters: {
    status: string;
    type: string;
    search: string;
  };
}

export default function Izin({ classNames = [], leaves, stats, filters }: IzinProps) {
  const [selectedLeave, setSelectedLeave] = useState<LeaveItem | null>(null);
  const [actionType, setActionType] = useState<'approved' | 'rejected' | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const [search, setSearch] = useState(filters.search || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
  const [typeFilter, setTypeFilter] = useState(filters.type || 'all');

  const verifyForm = useForm({
    status: '',
    rejection_reason: '',
  });

  const handleFilter = () => {
    router.get('/wali-kelas/izin', {
      search,
      status: statusFilter,
      type: typeFilter,
    }, { preserveState: true });
  };

  const handleResetFilter = () => {
    setSearch('');
    setStatusFilter('all');
    setTypeFilter('all');
    router.get('/wali-kelas/izin', {}, { preserveState: true });
  };

  const openVerifyDialog = (leave: LeaveItem, action: 'approved' | 'rejected') => {
    setSelectedLeave(leave);
    setActionType(action);
    verifyForm.setData({
      status: action,
      rejection_reason: '',
    });
    setIsDialogOpen(true);
  };

  const submitVerify = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedLeave) return;

    verifyForm.post(`/wali-kelas/izin/${selectedLeave.id}/verify`, {
      onSuccess: () => {
        setIsDialogOpen(false);
        setSelectedLeave(null);
      }
    });
  };

  return (
    <AppLayout title="Verifikasi Izin Rombel">
      <Head title="Verifikasi Izin Rombel - Wali Kelas" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Verifikasi Izin Siswa Kelas Binaan</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Kelola dan tindak lanjuti surat izin dari siswa kelas {classNames.join(', ') || 'Anda'}.
            </p>
          </div>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card className="p-4 border-l-4 border-l-amber-500">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground">Menunggu Verifikasi</p>
                <p className="text-2xl font-bold mt-1">{stats.pending}</p>
              </div>
              <Clock className="h-6 w-6 text-amber-500" />
            </div>
          </Card>

          <Card className="p-4 border-l-4 border-l-emerald-500">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground">Telah Disetujui</p>
                <p className="text-2xl font-bold mt-1">{stats.approved}</p>
              </div>
              <CheckCircle2 className="h-6 w-6 text-emerald-500" />
            </div>
          </Card>

          <Card className="p-4 border-l-4 border-l-destructive">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground">Telah Ditolak</p>
                <p className="text-2xl font-bold mt-1">{stats.rejected}</p>
              </div>
              <XCircle className="h-6 w-6 text-destructive" />
            </div>
          </Card>
        </div>

        {/* Filters */}
        <Card className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <Input
                placeholder="Cari siswa atau NIS..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                className="h-9 text-xs"
              />
            </div>
            <div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="all">Semua Status</option>
                <option value="pending">Menunggu</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
              </select>
            </div>
            <div>
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="all">Semua Jenis</option>
                <option value="sakit">Sakit</option>
                <option value="izin">Izin</option>
              </select>
            </div>
            <div className="flex items-center gap-2">
              <Button onClick={handleFilter} size="sm" className="h-9 flex-1 gap-1 text-xs">
                <Search className="h-3.5 w-3.5" />
                <span>Cari</span>
              </Button>
              <Button onClick={handleResetFilter} variant="outline" size="sm" className="h-9 text-xs">
                Reset
              </Button>
            </div>
          </div>
        </Card>

        {/* Data Table */}
        <Card>
          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12">No</TableHead>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead>Kelas</TableHead>
                  <TableHead>Jenis</TableHead>
                  <TableHead>Tanggal Izin</TableHead>
                  <TableHead>Alasan</TableHead>
                  <TableHead>Bukti</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Tindakan</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {leaves.data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={9} className="h-24 text-center text-muted-foreground">
                      Tidak ada permohonan izin dari kelas binaan Anda.
                    </TableCell>
                  </TableRow>
                ) : (
                  leaves.data.map((leave, idx) => (
                    <TableRow key={leave.id}>
                      <TableCell className="text-xs text-muted-foreground">
                        {(leaves.current_page - 1) * 15 + idx + 1}
                      </TableCell>
                      <TableCell>
                        <div className="font-semibold text-sm">
                          {leave.student?.name ?? `Siswa #${leave.id}`}
                        </div>
                        <div className="text-[11px] text-muted-foreground font-mono">
                          NIS: {leave.student?.nis ?? '-'}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="text-xs">
                          {leave.student?.kelas ?? '-'}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <Badge 
                          variant={leave.type === 'sakit' ? 'secondary' : 'outline'} 
                          className="capitalize text-xs"
                        >
                          {leave.type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                        {leave.start_date} {leave.end_date !== leave.start_date ? `s/d ${leave.end_date}` : ''}
                      </TableCell>
                      <TableCell className="max-w-[200px] text-xs">
                        <p className="line-clamp-2">{leave.reason}</p>
                        {leave.rejection_reason && (
                          <p className="text-[11px] text-destructive mt-1 font-medium">
                            Catatan: {leave.rejection_reason}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        {leave.attachment_path ? (
                          <a
                            href={`/storage/${leave.attachment_path}`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1 text-xs text-primary hover:underline font-medium"
                          >
                            <ExternalLink className="h-3 w-3" />
                            <span>Lihat Foto</span>
                          </a>
                        ) : (
                          <span className="text-xs text-muted-foreground">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        {leave.status === 'pending' && (
                          <Badge variant="outline" className="text-amber-600 border-amber-300 bg-amber-50 text-xs">
                            Menunggu
                          </Badge>
                        )}
                        {leave.status === 'approved' && (
                          <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-xs">
                            Disetujui
                          </Badge>
                        )}
                        {leave.status === 'rejected' && (
                          <Badge variant="destructive" className="text-xs">
                            Ditolak
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        {leave.status === 'pending' ? (
                          <div className="flex items-center justify-end gap-1.5">
                            <Button
                              size="sm"
                              variant="default"
                              className="h-7 text-xs bg-emerald-600 hover:bg-emerald-700 text-white"
                              onClick={() => openVerifyDialog(leave, 'approved')}
                            >
                              Setujui
                            </Button>
                            <Button
                              size="sm"
                              variant="outline"
                              className="h-7 text-xs text-destructive hover:bg-destructive/10"
                              onClick={() => openVerifyDialog(leave, 'rejected')}
                            >
                              Tolak
                            </Button>
                          </div>
                        ) : (
                          <span className="text-[11px] text-muted-foreground">
                            {leave.verifier?.fullname || leave.verifier?.username || 'Divalidasi'}
                          </span>
                        )}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {/* Pagination */}
          {leaves.last_page > 1 && (
            <div className="flex items-center justify-between p-4 border-t border-border">
              <span className="text-xs text-muted-foreground">
                Halaman {leaves.current_page} dari {leaves.last_page} ({leaves.total} total data)
              </span>
              <div className="flex items-center gap-1">
                {leaves.links.map((link, lIdx) => (
                  <Button
                    key={lIdx}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    className="h-8 text-xs"
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                  />
                ))}
              </div>
            </div>
          )}
        </Card>
      </div>

      {/* Decision Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <form onSubmit={submitVerify}>
            <DialogHeader>
              <DialogTitle>
                {actionType === 'approved' ? 'Setujui Izin Siswa Rombel' : 'Tolak Izin Siswa Rombel'}
              </DialogTitle>
              <DialogDescription className="text-xs">
                {actionType === 'approved'
                  ? `Setujui pengajuan izin untuk ${selectedLeave?.student?.name}. Status kehadiran siswa akan tercatat sebagai izin/sakit yang sah.`
                  : `Tolak pengajuan izin untuk ${selectedLeave?.student?.name}.`}
              </DialogDescription>
            </DialogHeader>

            {actionType === 'rejected' && (
              <div className="my-4 space-y-2">
                <label className="text-xs font-semibold">Alasan Penolakan</label>
                <Input
                  placeholder="Keterangan alasan penolakan..."
                  value={verifyForm.data.rejection_reason}
                  onChange={(e) => verifyForm.setData('rejection_reason', e.target.value)}
                  className="text-xs"
                />
              </div>
            )}

            <DialogFooter className="mt-4 gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setIsDialogOpen(false)}
                disabled={verifyForm.processing}
                className="text-xs"
              >
                Batal
              </Button>
              <Button
                type="submit"
                size="sm"
                variant={actionType === 'approved' ? 'default' : 'destructive'}
                disabled={verifyForm.processing}
                className={actionType === 'approved' ? 'bg-emerald-600 hover:bg-emerald-700 text-white text-xs' : 'text-xs'}
              >
                {verifyForm.processing ? 'Memproses...' : actionType === 'approved' ? 'Setujui Izin' : 'Tolak Izin'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
