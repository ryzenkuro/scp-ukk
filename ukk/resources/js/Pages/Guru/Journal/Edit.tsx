import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
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
    ArrowLeft,
    Save,
    BookOpen,
    Users
} from 'lucide-react';

interface StudentItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
    gender: 'L' | 'P';
}

interface AttendanceItem {
    id: number;
    student_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes: string | null;
    student?: StudentItem;
}

interface JournalData {
    id: number;
    schedule_id: number;
    date: string;
    topic: string;
    notes: string | null;
    schedule?: {
        subject: string;
        class_name: string;
        room_name: string | null;
        start_time: string;
        end_time: string;
    };
    attendances?: AttendanceItem[];
}

interface EditProps {
    journal: JournalData;
}

export default function JournalEdit({ journal }: EditProps) {
    const initialAttendances = (journal.attendances || []).map((att) => ({
        student_id: att.student_id,
        status: att.status,
        notes: att.notes || '',
        student: att.student,
    }));

    const form = useForm({
        date: journal.date,
        topic: journal.topic,
        notes: journal.notes || '',
        attendances: initialAttendances,
    });

    const setStudentStatus = (index: number, status: 'hadir' | 'sakit' | 'izin' | 'alpa') => {
        const next = [...form.data.attendances];
        next[index] = { ...next[index], status };
        form.setData('attendances', next);
    };

    const countStatus = (status: string) => {
        return form.data.attendances.filter((a) => a.status === status).length;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/guru/journal/${journal.id}`);
    };

    return (
        <AppLayout title="Edit Jurnal KBM & Presensi">
            <Head title="Edit Jurnal KBM" />

            <form onSubmit={submit} className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href="/guru/journal">
                            <Button type="button" variant="outline" size="sm" className="h-9 w-9 p-0">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h2 className="text-xl font-bold tracking-tight">
                                Edit Jurnal Pembelajaran: {journal.schedule?.subject}
                            </h2>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Kelas {journal.schedule?.class_name} &bull; Sesi {journal.date}
                            </p>
                        </div>
                    </div>
                    <Button type="submit" disabled={form.processing} className="gap-2">
                        <Save className="h-4 w-4" />
                        Simpan Perubahan
                    </Button>
                </div>

                {/* Form Informasi Materi */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold">
                            1. Pembaruan Materi KBM
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 pt-0">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_date">Tanggal Pelaksanaan *</Label>
                                <Input
                                    id="edit_date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) => form.setData('date', e.target.value)}
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label>Kelas & Jadwal</Label>
                                <div className="h-10 px-3 py-2 rounded-md border border-input bg-muted/40 text-sm flex items-center justify-between">
                                    <span className="font-medium">{journal.schedule?.subject}</span>
                                    <Badge variant="outline">{journal.schedule?.class_name}</Badge>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_topic">Materi / Topik KBM *</Label>
                            <Input
                                id="edit_topic"
                                value={form.data.topic}
                                onChange={(e) => form.setData('topic', e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_notes">Catatan Tambahan (Opsional)</Label>
                            <Input
                                id="edit_notes"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Pembaruan Presensi Murid */}
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-base font-semibold">
                                    2. Koreksi Presensi Murid
                                </CardTitle>
                                <Badge variant="secondary">
                                    Total {form.data.attendances.length} Siswa
                                </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Ralat status kehadiran siswa jika terdapat kesalahan pada saat pengisian awal.
                            </p>
                        </div>

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
                    </CardHeader>

                    <CardContent className="pt-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12 text-center">No</TableHead>
                                        <TableHead>NIS</TableHead>
                                        <TableHead>Nama Siswa</TableHead>
                                        <TableHead className="text-center">Status Kehadiran</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {form.data.attendances.map((item, idx) => {
                                        const currentStatus = item.status;

                                        return (
                                            <TableRow key={item.student_id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {idx + 1}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs font-semibold">
                                                    {item.student?.nis || '-'}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {item.student?.name || `Siswa #${item.student_id}`}
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
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}

