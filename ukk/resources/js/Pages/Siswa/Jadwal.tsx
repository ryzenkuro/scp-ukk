import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from '@/Components/ui/table';
import { Calendar, Clock, BookOpen, Printer } from 'lucide-react';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
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
    teacher?: { fullname: string };
    start_slot?: { name: string };
    end_slot?: { name: string };
}

interface JadwalProps {
    student: SiswaItem | null;
    schedules: ScheduleItem[];
    activeCycle: number;
    config: {
        active_cycle: number;
        academic_year: string;
    };
}

export default function SiswaJadwal({ student, schedules = [], activeCycle, config }: JadwalProps) {
    const [selectedCycle, setSelectedCycle] = useState(activeCycle.toString());

    const handleCycleChange = (val: string) => {
        setSelectedCycle(val);
        router.get('/siswa/jadwal', { cycle: val }, { preserveState: true });
    };

    return (
        <AppLayout title="Jadwal Pelajaran Kelas">
            <Head title="Jadwal Pelajaran" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4">
                        <div>
                            <CardTitle className="text-xl font-bold tracking-tight">
                                Jadwal Pelajaran Kelas {student?.kelas || ''}
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Tahun Ajaran {config.academic_year} &bull; Menampilkan sesi KBM terdaftar.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <a
                                href={`/siswa/jadwal/export-pdf${selectedCycle !== 'all' ? `?cycle=${selectedCycle}` : ''}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                                    <Printer className="h-4 w-4" />
                                    <span>Cetak Jadwal (A4)</span>
                                </Button>
                            </a>
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-muted-foreground font-medium">Putaran:</span>
                                <select
                                    value={selectedCycle}
                                    onChange={(e) => handleCycleChange(e.target.value)}
                                    className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Putaran</option>
                                    <option value="1">Putaran 1</option>
                                    <option value="2">Putaran 2</option>
                                </select>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-0">
                        {schedules.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Belum ada jadwal KBM yang diinputkan untuk kelas {student?.kelas}.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12 text-center">No</TableHead>
                                            <TableHead>Hari & Putaran</TableHead>
                                            <TableHead>Waktu Pembelajaran</TableHead>
                                            <TableHead>Mata Pelajaran</TableHead>
                                            <TableHead>Guru Pengampu</TableHead>
                                            <TableHead>Ruangan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {schedules.map((schedule, idx) => (
                                            <TableRow key={schedule.id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {idx + 1}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-semibold">{schedule.day_of_week}</span>
                                                    <Badge variant="outline" className="ms-2 text-[11px] font-mono">
                                                        Putaran {schedule.cycle_number}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <span className="font-semibold block text-foreground">
                                                        {schedule.start_time.substring(0, 5)} - {schedule.end_time.substring(0, 5)}
                                                    </span>
                                                    {schedule.start_slot && schedule.end_slot && (
                                                        <span className="text-muted-foreground font-mono text-[11px]">
                                                            {schedule.start_slot.name} s.d. {schedule.end_slot.name}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-medium text-foreground">
                                                    {schedule.subject}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {schedule.teacher?.fullname || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary" className="text-xs">
                                                        {schedule.room_name || '-'}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

