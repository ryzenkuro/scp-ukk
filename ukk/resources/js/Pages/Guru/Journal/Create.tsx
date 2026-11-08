import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
    BookOpen,
    CheckCircle2,
    Calendar,
    Users,
    ArrowLeft,
    Save
} from 'lucide-react';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
    gender: 'L' | 'P';
}

interface ScheduleItem {
    id: number;
    day_of_week: string;
    cycle_number: number;
    subject: string;
    class_name: string;
    room_name: string | null;
    start_time: string;
    end_time: string;
}

interface CreateProps {
    schedules: ScheduleItem[];
    selectedSchedule: ScheduleItem | null;
    students: SiswaItem[];
    todayDate: string;
    activeCycle: number;
}

export default function JournalCreate({
    schedules = [],
    selectedSchedule,
    students = [],
    todayDate,
    activeCycle,
}: CreateProps) {
    // Initialize attendances for all students defaulting to 'hadir'
    const initialAttendances = students.map((s) => ({
        student_id: s.id,
        status: 'hadir' as 'hadir' | 'sakit' | 'izin' | 'alpa',
        notes: '',
    }));

    const form = useForm({
        schedule_id: selectedSchedule?.id?.toString() || '',
        date: todayDate,
        topic: '',
        notes: '',
        attendances: initialAttendances,
    });

    const handleScheduleChange = (scheduleId: string) => {
        router.get('/guru/journal/create', { schedule_id: scheduleId });
    };

    const setStudentStatus = (index: number, status: 'hadir' | 'sakit' | 'izin' | 'alpa') => {
        const next = [...form.data.attendances];
        next[index] = { ...next[index], status };
        form.setData('attendances', next);
    };

    const setAllHadir = () => {
        const next = form.data.attendances.map((att) => ({
            ...att,
            status: 'hadir' as const,
        }));
        form.setData('attendances', next);
    };

    const countStatus = (status: string) => {
        return form.data.attendances.filter((a) => a.status === status).length;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/guru/journal');
    };

    return (
        <AppLayout title="Isi Jurnal KBM & Presensi">
            <Head title="Isi Jurnal KBM" />

            <form onSubmit={submit} className="space-y-6">
                {/* Header Card */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href="/guru/journal">
                            <Button type="button" variant="outline" size="sm" className="h-9 w-9 p-0">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h2 className="text-xl font-bold tracking-tight">
                                Formulir Jurnal Pembelajaran & Presensi Kelas
                            </h2>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Catat materi yang disampaikan dan kehadiran seluruh siswa kelas pada sesi KBM ini.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={form.processing || students.length === 0} className="gap-2">
                            <Save className="h-4 w-4" />
                            Simpan Jurnal & Presensi
                        </Button>
                    </div>
                </div>

                {/* Form Informasi KBM */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold">
                            1. Informasi Pelaksanaan KBM
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 pt-0">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="schedule_id">Pilih Jadwal Mengajar *</Label>
                                <select
                                    id="schedule_id"
                                    value={form.data.schedule_id}
                                    onChange={(e) => handleScheduleChange(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm"
                                    required
                                >
                                    {schedules.map((s) => (
                                        <option key={s.id} value={s.id.toString()}>
                                            [{s.day_of_week} Putaran {s.cycle_number}] {s.subject} - {s.class_name} ({s.start_time.substring(0, 5)} - {s.end_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="date">Tanggal Pelaksanaan *</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) => form.setData('date', e.target.value)}
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="topic">Materi / Topik Pembelajaran yang Disampaikan *</Label>
                            <Input
                                id="topic"
                                value={form.data.topic}
                                onChange={(e) => form.setData('topic', e.target.value)}
                                placeholder="Contoh: Implementasi State Management React dengan Inertia.js"
                                required
                            />
                            {form.errors.topic && (
                                <p className="text-xs text-destructive">{form.errors.topic}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="notes">Catatan KBM / Hambatan Kelas (Opsional)</Label>
                            <Input
                                id="notes"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                placeholder="Catatan tambahan mengenai jalannya kelas atau tugas siswa..."
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Checklist Presensi Siswa */}
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-base font-semibold">
                                    2. Checklist Presensi Murid ({selectedSchedule?.class_name || 'Kelas'})
                                </CardTitle>
                                <Badge variant="secondary">
                                    Total {students.length} Siswa
                                </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Klik status kehadiran untuk setiap siswa. Default semua siswa diatur ke "Hadir".
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {/* Summary Pill Counters */}
                            <div className="flex items-center gap-1.5 text-xs font-mono bg-muted/50 p-1.5 rounded-lg border border-border">
                                <span className="bg-secondary text-secondary-foreground px-2 py-0.5 rounded font-semibold">
                                    Hadir: {countStatus('hadir')}
                                </span>
                                <span className="bg-background px-2 py-0.5 rounded text-muted-foreground">
                                    Sakit: {countStatus('sakit')}
                                </span>
                                <span className="bg-background px-2 py-0.5 rounded text-muted-foreground">
                                    Izin: {countStatus('izin')}
                                </span>
                                <span className="bg-background px-2 py-0.5 rounded text-destructive font-semibold">
                                    Alpa: {countStatus('alpa')}
                                </span>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={setAllHadir}
                                className="text-xs"
                            >
                                Set Semua Hadir
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-0">
                        {students.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Tidak ada siswa terdaftar di kelas {selectedSchedule?.class_name}. Silakan tambahkan data siswa di menu Data Master terlebih dahulu.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12 text-center">No</TableHead>
                                            <TableHead>NIS</TableHead>
                                            <TableHead>Nama Lengkap Siswa</TableHead>
                                            <TableHead className="text-center">Jenis Kelamin</TableHead>
                                            <TableHead className="text-center">Status Kehadiran</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {students.map((student, idx) => {
                                            const currentStatus = form.data.attendances[idx]?.status || 'hadir';

                                            return (
                                                <TableRow key={student.id}>
                                                    <TableCell className="text-center font-mono text-xs">
                                                        {idx + 1}
                                                    </TableCell>
                                                    <TableCell className="font-mono text-xs font-semibold">
                                                        {student.nis}
                                                    </TableCell>
                                                    <TableCell className="font-medium text-foreground">
                                                        {student.name}
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs">
                                                        {student.gender === 'L' ? 'L' : 'P'}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <div className="inline-flex rounded-lg border border-border bg-muted p-0.5 text-xs">
                                                            <button
                                                                type="button"
                                                                onClick={() => setStudentStatus(idx, 'hadir')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${currentStatus === 'hadir' ? 'bg-secondary text-secondary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                                            >
                                                                Hadir (H)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => setStudentStatus(idx, 'sakit')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${currentStatus === 'sakit' ? 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200 shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                                            >
                                                                Sakit (S)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => setStudentStatus(idx, 'izin')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${currentStatus === 'izin' ? 'bg-blue-100 text-blue-900 dark:bg-blue-950 dark:text-blue-200 shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                                            >
                                                                Izin (I)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => setStudentStatus(idx, 'alpa')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${currentStatus === 'alpa' ? 'bg-destructive text-destructive-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                                            >
                                                                Alpa (A)
                                                            </button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}

