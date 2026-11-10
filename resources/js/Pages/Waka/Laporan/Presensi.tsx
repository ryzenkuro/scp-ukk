import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
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
    Printer,
    FileText,
    Calendar,
    Filter,
    Users,
    CheckCircle2
} from 'lucide-react';

interface RekapItem {
    id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
    total: number;
    persentase: number;
}

interface PresensiReportProps {
    kelasList: string[];
    selectedKelas: string;
    filters: {
        kelas: string;
        start_date: string;
        end_date: string;
    };
    rekapData: RekapItem[];
    summary: {
        total_students: number;
        total_hadir: number;
        total_sakit: number;
        total_izin: number;
        total_alpa: number;
        class_attendance_rate: number;
    };
    config: {
        academic_year: string;
        active_cycle: number;
    };
    todayDate: string;
}

export default function LaporanPresensi({
    kelasList = [],
    selectedKelas,
    filters,
    rekapData = [],
    summary,
    config,
    todayDate,
}: PresensiReportProps) {
    const [currentKelas, setCurrentKelas] = useState(filters.kelas || selectedKelas);
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/waka/laporan/presensi', {
            kelas: currentKelas,
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Rekapitulasi Presensi">
            <Head title="Rekap Presensi Siswa" />

            <div className="space-y-6">
                {/* Filter & Print Toolbar (Disembunyikan saat mencetak A4) */}
                <Card className="print:hidden">
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Rekapitulasi Presensi Siswa per Kelas
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Hitung persentase kehadiran (Hadir, Sakit, Izin, Alpa) dan cetak dokumen resmi format A4.
                            </p>
                        </div>
                        <Button onClick={handlePrint} className="gap-2">
                            <Printer className="h-4 w-4" />
                            Cetak Dokumen A4 (Print)
                        </Button>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Pilih Kelas *</Label>
                                <select
                                    value={currentKelas}
                                    onChange={(e) => setCurrentKelas(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    {kelasList.map((k) => (
                                        <option key={k} value={k}>{k}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Mulai Tanggal</Label>
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
                                />
                            </div>

                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Sampai Tanggal</Label>
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
                                />
                            </div>

                            <div className="flex items-end">
                                <Button type="submit" variant="secondary" className="w-full gap-2 text-xs">
                                    <Filter className="h-3.5 w-3.5" />
                                    Terapkan Filter
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Printable Document Paper (Ukuran A4 Standar) */}
                <div className="rounded-xl border border-border bg-card p-6 md:p-8 shadow-sm print:border-none print:shadow-none print:p-0">
                    {/* KOP SURAT RESMI SEKOLAH */}
                    <div className="border-b-2 border-foreground pb-4 text-center space-y-1 mb-6">
                        <h4 className="text-xs font-semibold tracking-wider uppercase text-muted-foreground">
                            Pemerintah Daerah Daerah Istimewa Yogyakarta &bull; Dinas Pendidikan, Pemuda, dan Olahraga
                        </h4>
                        <h2 className="text-xl md:text-2xl font-bold tracking-tight text-foreground uppercase">
                            SMK NEGERI 1 BANTUL
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Jl. Parangtritis Km 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715 &bull; Telepon: (0274) 367156
                        </p>
                    </div>

                    {/* Judul Laporan */}
                    <div className="text-center mb-6">
                        <h3 className="text-base font-bold uppercase tracking-wide">
                            Laporan Rekapitulasi Presensi Kehadiran Siswa
                        </h3>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Kelas: <strong>{selectedKelas}</strong> &bull; Tahun Ajaran: <strong>{config.academic_year}</strong> &bull; Siklus Putaran {config.active_cycle}
                        </p>
                    </div>

                    {/* Ringkasan Angka Rekapitulasi */}
                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                        <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                            <span className="text-[11px] text-muted-foreground block font-medium">Total Siswa</span>
                            <span className="text-lg font-bold">{summary.total_students}</span>
                        </div>
                        <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                            <span className="text-[11px] text-muted-foreground block font-medium">Total Hadir (H)</span>
                            <span className="text-lg font-bold text-emerald-600">{summary.total_hadir}</span>
                        </div>
                        <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                            <span className="text-[11px] text-muted-foreground block font-medium">Total Sakit (S)</span>
                            <span className="text-lg font-bold text-amber-600">{summary.total_sakit}</span>
                        </div>
                        <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                            <span className="text-[11px] text-muted-foreground block font-medium">Total Izin (I)</span>
                            <span className="text-lg font-bold text-blue-600">{summary.total_izin}</span>
                        </div>
                        <div className="border border-border rounded-lg p-3 text-center bg-muted/20 col-span-2 sm:col-span-1">
                            <span className="text-[11px] text-muted-foreground block font-medium">Kehadiran Kelas</span>
                            <span className="text-lg font-bold text-foreground">{summary.class_attendance_rate}%</span>
                        </div>
                    </div>

                    {/* Tabel Rekapitulasi Siswa */}
                    <div className="overflow-x-auto mb-8">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10 text-center font-bold">No</TableHead>
                                    <TableHead className="font-bold">NIS</TableHead>
                                    <TableHead className="font-bold">Nama Lengkap Siswa</TableHead>
                                    <TableHead className="text-center font-bold">L/P</TableHead>
                                    <TableHead className="text-center font-bold">H</TableHead>
                                    <TableHead className="text-center font-bold">S</TableHead>
                                    <TableHead className="text-center font-bold">I</TableHead>
                                    <TableHead className="text-center font-bold">A</TableHead>
                                    <TableHead className="text-center font-bold">Total</TableHead>
                                    <TableHead className="text-right font-bold">% Hadir</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rekapData.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={10} className="h-24 text-center text-muted-foreground">
                                            Tidak ada data siswa atau presensi pada kelas ini.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rekapData.map((row, idx) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="text-center text-xs font-mono">{idx + 1}</TableCell>
                                            <TableCell className="font-mono text-xs font-semibold">{row.nis}</TableCell>
                                            <TableCell className="font-medium text-xs">{row.name}</TableCell>
                                            <TableCell className="text-center text-xs">{row.gender}</TableCell>
                                            <TableCell className="text-center text-xs font-semibold text-emerald-600">{row.hadir}</TableCell>
                                            <TableCell className="text-center text-xs">{row.sakit}</TableCell>
                                            <TableCell className="text-center text-xs">{row.izin}</TableCell>
                                            <TableCell className="text-center text-xs font-bold text-destructive">{row.alpa}</TableCell>
                                            <TableCell className="text-center text-xs">{row.total}</TableCell>
                                            <TableCell className="text-right font-mono text-xs font-bold">{row.persentase}%</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Bagian Tanda Tangan Resmi Dokumen */}
                    <div className="grid grid-cols-2 gap-8 pt-8 text-center text-xs text-foreground">
                        <div>
                            <p className="text-muted-foreground">Mengetahui,</p>
                            <p className="font-semibold">Kepala Sekolah SMK N 1 Bantul</p>
                            <div className="h-20"></div>
                            <p className="font-bold underline uppercase">Mujari, S.Pd., M.Pd</p>
                            <p className="text-muted-foreground">NIP. 19680512 199412 1 002</p>
                        </div>

                        <div>
                            <p className="text-muted-foreground">Bantul, {todayDate}</p>
                            <p className="font-semibold">Waka Kurikulum</p>
                            <div className="h-20"></div>
                            <p className="font-bold underline uppercase">Drs. H. Hartono, M.Pd</p>
                            <p className="text-muted-foreground">NIP. 19700315 199702 1 003</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

