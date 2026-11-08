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
    GraduationCap,
    Search,
    CheckCircle2,
    ShieldCheck,
} from 'lucide-react';

interface AlumniItem {
    id: number;
    student_id: number | null;
    nis: string;
    name: string;
    jurusan: string | null;
    kelas_terakhir: string | null;
    tahun_lulus: string;
    tanggal_lulus: string;
    alamat: string | null;
    phone: string | null;
    email: string | null;
    keterangan: string | null;
    is_expired: boolean;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface IndexProps {
    alumnis: PaginatedData<AlumniItem>;
    filters: {
        search?: string;
        tahun_lulus?: string;
    };
    stats: {
        total: number;
        active: number;
        retention_threshold: number;
        current_year: number;
    };
    tahunList: string[];
}

export default function AlumniIndex({ alumnis, filters, stats, tahunList }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedTahun, setSelectedTahun] = useState(filters.tahun_lulus || 'all');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/alumni', {
            search: searchQuery,
            tahun_lulus: selectedTahun,
        }, { preserveState: true });
    };

    const handleFilterYearChange = (year: string) => {
        setSelectedTahun(year);
        router.get('/waka/alumni', {
            search: searchQuery,
            tahun_lulus: year,
        }, { preserveState: true });
    };

    return (
        <AppLayout title="Data Master Alumni & Retensi">
            <Head title="Data Alumni" />

            <div className="space-y-6">
                {/* 3 Kartu Ringkasan Status Retensi Otomatis */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Arsip Alumni Aktif</CardTitle>
                            <GraduationCap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                            <p className="text-xs text-muted-foreground mt-1">Lulusan tersimpan dalam masa retensi</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Masa Berlaku Arsip</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.retention_threshold} &ndash; {stats.current_year}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Maksimal 1 tahun setelah tahun kelulusan</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pembersihan Otomatis</CardTitle>
                            <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">Aktif</div>
                            <p className="text-xs text-muted-foreground mt-1">Data kadaluarsa otomatis dihapus sistem</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter Bar & Penjelasan Kebijakan */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-xl font-bold tracking-tight">
                            Arsip Kelulusan Siswa
                        </CardTitle>
                        <p className="text-sm text-muted-foreground mt-0.5">
                            Kebijakan retensi otomatis: Data alumni tersimpan selama masa retensi 1 tahun. Data yang melewati batas retensi otomatis dibersihkan oleh sistem.
                        </p>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="sm:col-span-2 relative">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama, NIS, atau kelas terakhir alumni..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9"
                                />
                            </div>

                            <div>
                                <select
                                    value={selectedTahun}
                                    onChange={(e) => handleFilterYearChange(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Tahun Kelulusan</option>
                                    {tahunList.map((t) => (
                                        <option key={t} value={t}>Lulus Tahun {t}</option>
                                    ))}
                                </select>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table Alumni (Read-only Viewer) */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead>Nama Alumni</TableHead>
                                    <TableHead>Kelas Terakhir</TableHead>
                                    <TableHead>Tahun Lulus</TableHead>
                                    <TableHead className="text-center">Status Retensi</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {alumnis.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                            Tidak ada data arsip alumni aktif ditemukan.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    alumnis.data.map((alumni, idx) => (
                                        <TableRow key={alumni.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {(alumnis.current_page - 1) * 20 + idx + 1}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs font-semibold">
                                                {alumni.nis}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {alumni.name}
                                                {alumni.phone && (
                                                    <span className="block text-xs text-muted-foreground font-normal">
                                                        HP: {alumni.phone}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {alumni.kelas_terakhir || '-'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="font-semibold text-xs">
                                                {alumni.tahun_lulus}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant="secondary" className="gap-1 text-xs font-medium">
                                                    Aktif
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground max-w-md truncate">
                                                {alumni.keterangan || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {alumnis.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-4">
                            <span className="text-xs text-muted-foreground">
                                Total {alumnis.total} arsip alumni (Halaman {alumnis.current_page} dari {alumnis.last_page})
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!alumnis.prev_page_url}
                                    onClick={() => alumnis.prev_page_url && router.get(alumnis.prev_page_url)}
                                >
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!alumnis.next_page_url}
                                    onClick={() => alumnis.next_page_url && router.get(alumnis.next_page_url)}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
