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
import { formatDateIndo } from '@/lib/utils';

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

        router.get('/waka/laporan/presensi', {
            kelas: currentKelas,
            start_date: s,
            end_date: e,
        }, { preserveState: true });
    };

    const handlePrint = () => {
        window.print();
    };

    const isSingleDay = summary.is_single_day || (startDate && endDate && startDate === endDate);

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

                    <CardContent className="pt-0 space-y-3">
                        {/* Tombol Pintas Periode */}
                        <div className="flex flex-wrap items-center gap-2 pt-1 border-t border-border/50">
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

                        <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-1">
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
                            {startDate && endDate ? (
                                startDate === endDate ? (
                                    <span> &bull; Tanggal: <strong>{formatDateIndo(startDate)}</strong> (Harian)</span>
                                ) : (
                                    <span> &bull; Periode: <strong>{formatDateIndo(startDate)} s.d. {formatDateIndo(endDate)}</strong></span>
                                )
                            ) : (
                                <span> &bull; Periode: <strong>Semua Riwayat Terlaksana</strong></span>
                            )}
                        </p>
                    </div>

                    {/* Ringkasan Angka Rekapitulasi (Adaptif: Mode 1 Hari vs Mode Periode Sesi KBM) */}
                    {isSingleDay ? (
                        /* Mode 1 Hari: Menghitung Headcount Siswa (Hadir + Izin + Sakit + Alpa = Total Siswa) */
                        <div className="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-6">
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Total Siswa</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_students}</span>
                                <span className="text-[10px] text-muted-foreground block">Kelas {selectedKelas}</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Siswa Hadir (H)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.headcount_hadir ?? summary.total_hadir}</span>
                                <span className="text-[10px] text-muted-foreground block">Siswa Mengikuti</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Siswa Sakit (S)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.headcount_sakit ?? summary.total_sakit}</span>
                                <span className="text-[10px] text-muted-foreground block">Surat Dokter</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Siswa Izin (I)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.headcount_izin ?? summary.total_izin}</span>
                                <span className="text-[10px] text-muted-foreground block">Izin Disetujui</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Siswa Alpa (A)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.headcount_alpa ?? summary.total_alpa}</span>
                                <span className="text-[10px] text-muted-foreground block">Tanpa Keterangan</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20 col-span-2 sm:col-span-1">
                                <span className="text-[11px] text-muted-foreground block font-medium">Kehadiran Kelas</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.class_attendance_rate}%</span>
                                <span className="text-[10px] text-muted-foreground block">Tingkat Disiplin</span>
                            </div>
                        </div>
                    ) : (
                        /* Mode Rentang Periode (Bulan / Semester): Menghitung Evaluasi KBM Rombel */
                        <div className="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-6">
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Total Siswa</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_students}</span>
                                <span className="text-[10px] text-muted-foreground block">Siswa Terdaftar</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Pertemuan KBM</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_sessions ?? 0}</span>
                                <span className="text-[10px] text-muted-foreground block">Sesi Terlaksana</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Total Sakit (S)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_sakit}</span>
                                <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Total Izin (I)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_izin}</span>
                                <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20">
                                <span className="text-[11px] text-muted-foreground block font-medium">Total Alpa (A)</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.total_alpa}</span>
                                <span className="text-[10px] text-muted-foreground block">Sesi / Hari</span>
                            </div>
                            <div className="border border-border rounded-lg p-3 text-center bg-muted/20 col-span-2 sm:col-span-1">
                                <span className="text-[11px] text-muted-foreground block font-medium">Rata-rata Kehadiran</span>
                                <span className="text-lg font-bold text-foreground block mt-0.5">{summary.class_attendance_rate}%</span>
                                <span className="text-[10px] text-muted-foreground block">Kehadiran Rombel</span>
                            </div>
                        </div>
                    )}

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

