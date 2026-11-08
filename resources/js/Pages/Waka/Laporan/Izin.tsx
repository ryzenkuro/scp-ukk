import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
    CheckCircle2,
    XCircle,
    Printer,
    Filter,
    ShieldCheck
} from 'lucide-react';

interface StudentInfo {
    id: number;
    nis: string;
    name: string;
    kelas: string;
}

interface LeaveItem {
    id: number;
    student_id: number;
    type: 'sakit' | 'izin';
    start_date: string;
    end_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    verified_at?: string | null;
    created_at: string;
    student?: StudentInfo;
    verifier?: { fullname: string };
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface IzinReportProps {
    leaves: PaginatedData<LeaveItem>;
    filters: {
        status: string;
        type: string;
        search: string;
    };
}

export default function LaporanIzin({ leaves, filters }: IzinReportProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');
    const [selectedType, setSelectedType] = useState(filters.type || 'all');

    const [verifyItem, setVerifyItem] = useState<LeaveItem | null>(null);

    const verifyForm = useForm({
        status: 'approved',
        rejection_reason: '',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/laporan/izin', {
            search: searchQuery,
            status: selectedStatus,
            type: selectedType,
        }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        const next = {
            search: searchQuery,
            status: selectedStatus,
            type: selectedType,
            [key]: value,
        };
        if (key === 'status') setSelectedStatus(value);
        if (key === 'type') setSelectedType(value);

        router.get('/waka/laporan/izin', next, { preserveState: true });
    };

    const openVerify = (leave: LeaveItem) => {
        setVerifyItem(leave);
        verifyForm.setData({
            status: 'approved',
            rejection_reason: '',
        });
    };

    const submitVerify = (e: React.FormEvent) => {
        e.preventDefault();
        if (!verifyItem) return;
        verifyForm.post(`/waka/laporan/izin/${verifyItem.id}/verify`, {
            onSuccess: () => setVerifyItem(null),
        });
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Rekapitulasi Izin Siswa">
            <Head title="Rekap Izin Siswa" />

            <div className="space-y-6">
                {/* Filter Toolbar */}
                <Card className="print:hidden">
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Rekapitulasi & Verifikasi Surat Izin Siswa
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Pantau dan setujui permohonan izin sakit atau izin keperluan keluarga yang diajukan siswa/orang tua.
                            </p>
                        </div>
                        <Button onClick={handlePrint} className="gap-2">
                            <Printer className="h-4 w-4" />
                            Cetak Laporan (Print)
                        </Button>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div className="sm:col-span-2 relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama siswa, NIS, atau kelas..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>

                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="pending">Menunggu Verifikasi</option>
                                    <option value="approved">Disetujui</option>
                                    <option value="rejected">Ditolak</option>
                                </select>
                            </div>

                            <div>
                                <select
                                    value={selectedType}
                                    onChange={(e) => handleFilterChange('type', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Jenis Izin</option>
                                    <option value="sakit">Sakit</option>
                                    <option value="izin">Izin</option>
                                </select>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table Rekap Izin */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Nama Siswa & Kelas</TableHead>
                                    <TableHead>Jenis Izin</TableHead>
                                    <TableHead>Rentang Tanggal</TableHead>
                                    <TableHead>Alasan / Keterangan</TableHead>
                                    <TableHead className="text-center">Status</TableHead>
                                    <TableHead className="text-right print:hidden">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {leaves.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data permohonan izin yang sesuai filter.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    leaves.data.map((leave, idx) => (
                                        <TableRow key={leave.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {(leaves.current_page - 1) * 15 + idx + 1}
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-semibold text-foreground block">
                                                    {leave.student?.name || 'Siswa'}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    NIS: {leave.student?.nis} &bull; {leave.student?.kelas}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={leave.type === 'sakit' ? 'secondary' : 'outline'} className="capitalize text-xs">
                                                    {leave.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs font-mono">
                                                {leave.start_date} s.d. {leave.end_date}
                                            </TableCell>
                                            <TableCell className="text-xs max-w-xs">
                                                <div className="font-medium text-foreground">{leave.reason}</div>
                                                {leave.rejection_reason && (
                                                    <div className="text-destructive mt-0.5">Alasan tolak: {leave.rejection_reason}</div>
                                                )}
                                                {leave.verifier && (
                                                    <div className="text-muted-foreground mt-0.5 text-[11px]">
                                                        Diverifikasi oleh: {leave.verifier.fullname}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
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
                                            <TableCell className="text-right print:hidden">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => openVerify(leave)}
                                                    className="h-8 gap-1.5 text-xs"
                                                >
                                                    <ShieldCheck className="h-3.5 w-3.5" />
                                                    Verifikasi
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {leaves.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4 print:hidden">
                            <span className="text-xs text-muted-foreground">
                                Total {leaves.total} data izin (Halaman {leaves.current_page} dari {leaves.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!leaves.prev_page_url}
                                    onClick={() => leaves.prev_page_url && router.get(leaves.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!leaves.next_page_url}
                                    onClick={() => leaves.next_page_url && router.get(leaves.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Verifikasi Izin */}
            <Dialog open={!!verifyItem} onOpenChange={(open) => !open && setVerifyItem(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-primary" />
                            Verifikasi Permohonan Izin Siswa
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitVerify} className="space-y-4">
                        <div className="rounded-lg border border-border bg-muted/40 p-3 space-y-1 text-xs">
                            <div>Siswa: <strong className="text-foreground">{verifyItem?.student?.name}</strong> ({verifyItem?.student?.kelas})</div>
                            <div>Jenis: <strong className="capitalize text-foreground">{verifyItem?.type}</strong></div>
                            <div>Tanggal: <span className="font-mono text-foreground">{verifyItem?.start_date} s.d. {verifyItem?.end_date}</span></div>
                            <div>Alasan: <span className="italic text-foreground">"{verifyItem?.reason}"</span></div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="verify_status">Keputusan Verifikasi *</Label>
                            <select
                                id="verify_status"
                                value={verifyForm.data.status}
                                onChange={(e) => verifyForm.setData('status', e.target.value)}
                                className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm"
                            >
                                <option value="approved">Setujui Permohonan Izin</option>
                                <option value="rejected">Tolak Permohonan Izin</option>
                            </select>
                        </div>

                        {verifyForm.data.status === 'rejected' && (
                            <div className="space-y-1.5">
                                <Label htmlFor="rejection_reason">Alasan Penolakan</Label>
                                <Input
                                    id="rejection_reason"
                                    value={verifyForm.data.rejection_reason}
                                    onChange={(e) => verifyForm.setData('rejection_reason', e.target.value)}
                                    placeholder="Contoh: Surat keterangan dokter belum dilampirkan"
                                />
                            </div>
                        )}

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setVerifyItem(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={verifyForm.processing}>
                                Simpan Verifikasi
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

