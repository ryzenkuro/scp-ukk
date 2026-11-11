import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
import {
    Calendar,
    Clock,
    BookOpen,
    Printer,
    Coffee,
    CheckCircle2,
    Sparkles,
    User,
    Users,
    MapPin,
    Layers,
    List,
    Flag,
    ClipboardList,
} from 'lucide-react';

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

interface TimeSlotItem {
    id: number;
    name: string;
    order: number;
    start_time: string;
    end_time: string;
    is_break: boolean;
}

interface JadwalProps {
    classNames: string[];
    selectedClass: string;
    schedules: ScheduleItem[];
    timeSlots: TimeSlotItem[];
    todayName: string;
    activeCycle: number;
    config: {
        active_cycle: number;
        academic_year: string;
        ceremony_cycle_1?: boolean;
        ceremony_cycle_2?: boolean;
    };
}

const SCHOOL_DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as const;

export default function WaliKelasJadwal({
    classNames = [],
    selectedClass = 'XI RPL 1',
    schedules = [],
    timeSlots = [],
    todayName = 'Senin',
    activeCycle,
    config,
}: JadwalProps) {
    const [currentClass, setCurrentClass] = useState(selectedClass);
    const [selectedCycle, setSelectedCycle] = useState(activeCycle.toString());

    const initialDay = SCHOOL_DAYS.includes(todayName as (typeof SCHOOL_DAYS)[number])
        ? todayName
        : 'Senin';
    const [selectedDay, setSelectedDay] = useState<string>(initialDay);
    const [viewMode, setViewMode] = useState<'slots' | 'blocks'>('slots');

    const handleClassChange = (newClass: string) => {
        setCurrentClass(newClass);
        router.get('/wali-kelas/jadwal', { kelas: newClass, cycle: selectedCycle }, { preserveState: true });
    };

    const handleCycleChange = (val: string) => {
        setSelectedCycle(val);
        router.get('/wali-kelas/jadwal', { kelas: currentClass, cycle: val }, { preserveState: true });
    };

    // Session count per day
    const sessionCountByDay = useMemo(() => {
        const counts: Record<string, number> = {
            Senin: 0,
            Selasa: 0,
            Rabu: 0,
            Kamis: 0,
            Jumat: 0,
        };
        schedules.forEach((s) => {
            if (counts[s.day_of_week] !== undefined) {
                counts[s.day_of_week]++;
            }
        });
        return counts;
    }, [schedules]);

    // Helper: Match a schedule to a specific time slot
    const getScheduleForSlot = (day: string, slot: TimeSlotItem): ScheduleItem | undefined => {
        const slotStart = slot.start_time.substring(0, 5);
        const slotEnd = slot.end_time.substring(0, 5);

        return schedules.find((s) => {
            if (s.day_of_week !== day) return false;
            const schedStart = s.start_time.substring(0, 5);
            const schedEnd = s.end_time.substring(0, 5);
            return slotStart >= schedStart && slotEnd <= schedEnd;
        });
    };

    // Helper: Live status of slot
    const getSlotLiveStatus = (slot: TimeSlotItem, day: string) => {
        if (day !== todayName) return null;

        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        const [sh, sm] = slot.start_time.substring(0, 5).split(':').map(Number);
        const [eh, em] = slot.end_time.substring(0, 5).split(':').map(Number);
        const startMinutes = sh * 60 + sm;
        const endMinutes = eh * 60 + em;

        if (currentMinutes >= startMinutes && currentMinutes < endMinutes) {
            return 'active';
        }
        if (currentMinutes >= endMinutes) {
            return 'past';
        }
        return 'future';
    };

    // Filter schedules for blocks view
    const filteredBlockSchedules = useMemo(() => {
        if (selectedDay === 'all') {
            return schedules;
        }
        return schedules.filter((s) => s.day_of_week === selectedDay);
    }, [schedules, selectedDay]);

    return (
        <AppLayout title="Jadwal Kelas Binaan">
            <Head title="Jadwal Kelas Binaan" />

            <div className="space-y-6">
                <Card className="border border-border/80 shadow-sm">
                    {/* Header */}
                    <CardHeader className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-border/60">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <CardTitle className="text-xl font-bold tracking-tight">
                                    Jadwal Pelajaran Kelas {currentClass}
                                </CardTitle>
                                <Badge variant="outline" className="text-xs bg-muted/50">
                                    TA {config.academic_year}
                                </Badge>
                                <Badge variant="secondary" className="text-xs">
                                    Kelas Binaan
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground mt-1">
                                Pantau jadwal KBM Jam Ke-1 s.d. Jam Ke-10 kelas binaan Anda secara real-time.
                            </p>
                        </div>

                        {/* Top Action Controls */}
                        <div className="flex flex-wrap items-center gap-2.5">
                            {/* Selector Class if multiple classes */}
                            {classNames.length > 1 && (
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xs text-muted-foreground font-medium">Kelas:</span>
                                    <select
                                        value={currentClass}
                                        onChange={(e) => handleClassChange(e.target.value)}
                                        className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm font-semibold shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        {classNames.map((c) => (
                                            <option key={c} value={c}>
                                                {c}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            {/* Toggle View Mode */}
                            <div className="flex items-center bg-muted/60 p-0.5 rounded-lg border border-border">
                                <button
                                    type="button"
                                    onClick={() => setViewMode('slots')}
                                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all ${
                                        viewMode === 'slots'
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    <List className="h-3.5 w-3.5" />
                                    <span>Jam Pelajaran (1-10)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setViewMode('blocks')}
                                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all ${
                                        viewMode === 'blocks'
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    <Layers className="h-3.5 w-3.5" />
                                    <span>Blok Sesi KBM</span>
                                </button>
                            </div>

                            {/* Putaran Filter */}
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-muted-foreground font-medium hidden sm:inline">Putaran:</span>
                                <select
                                    value={selectedCycle}
                                    onChange={(e) => handleCycleChange(e.target.value)}
                                    className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                >
                                    <option value="all">Semua Putaran</option>
                                    <option value="1">Putaran 1</option>
                                    <option value="2">Putaran 2</option>
                                </select>
                            </div>

                            {/* Print PDF Button */}
                            <a
                                href={`/jadwal/export-pdf?cycle=${selectedCycle !== 'all' ? selectedCycle : '1'}&class_name=${currentClass}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Button variant="outline" size="sm" className="gap-1.5 text-xs h-9">
                                    <Printer className="h-4 w-4" />
                                    <span>Cetak Jadwal (A4)</span>
                                </Button>
                            </a>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-5 space-y-5">
                        {/* Day Navigation Tabs */}
                        <div className="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar border-b border-border/40">
                            {SCHOOL_DAYS.map((day) => {
                                const isSelected = selectedDay === day;
                                const isToday = todayName === day;
                                const count = sessionCountByDay[day] || 0;

                                return (
                                    <button
                                        key={day}
                                        type="button"
                                        onClick={() => setSelectedDay(day)}
                                        className={`flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap border ${
                                            isSelected
                                                ? 'bg-primary text-primary-foreground border-primary shadow-sm'
                                                : 'bg-card text-muted-foreground hover:bg-muted/80 hover:text-foreground border-border/70'
                                        }`}
                                    >
                                        <Calendar className={`h-4 w-4 ${isSelected ? 'text-primary-foreground' : 'text-muted-foreground'}`} />
                                        <span>{day}</span>
                                        {isToday && (
                                            <span
                                                className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${
                                                    isSelected
                                                        ? 'bg-primary-foreground/20 text-primary-foreground'
                                                        : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                                }`}
                                            >
                                                Hari Ini
                                            </span>
                                        )}
                                        <span
                                            className={`text-[11px] font-mono px-1.5 py-0.2 rounded ${
                                                isSelected
                                                    ? 'bg-primary-foreground/25 text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground'
                                            }`}
                                        >
                                            {count} Sesi
                                        </span>
                                    </button>
                                );
                            })}

                            <button
                                type="button"
                                onClick={() => setSelectedDay('all')}
                                className={`flex items-center gap-1.5 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap border ${
                                    selectedDay === 'all'
                                        ? 'bg-primary text-primary-foreground border-primary shadow-sm'
                                        : 'bg-card text-muted-foreground hover:bg-muted/80 hover:text-foreground border-border/70'
                                }`}
                            >
                                <Sparkles className={`h-4 w-4 ${selectedDay === 'all' ? 'text-primary-foreground' : 'text-muted-foreground'}`} />
                                <span>Semua Hari</span>
                                <span
                                    className={`text-[11px] font-mono px-1.5 py-0.2 rounded ${
                                        selectedDay === 'all'
                                            ? 'bg-primary-foreground/25 text-primary-foreground'
                                            : 'bg-muted text-muted-foreground'
                                    }`}
                                >
                                    {schedules.length}
                                </span>
                            </button>
                        </div>

                        {/* Content Area */}
                        {schedules.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-12 text-center text-sm text-muted-foreground">
                                <Clock className="h-10 w-10 mx-auto text-muted-foreground/60 mb-2" />
                                <p className="font-semibold text-foreground">Tidak Ada Jadwal Pembelajaran</p>
                                <p className="text-xs text-muted-foreground mt-1">
                                    Belum ada sesi KBM yang terdaftar untuk kelas binaan {currentClass} pada putaran yang dipilih.
                                </p>
                            </div>
                        ) : selectedDay !== 'all' && viewMode === 'slots' ? (
                            /* DETAIL JAM PELAJARAN (JAM KE-1 SAMPAI JAM KE-10) */
                            <div className="space-y-4">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-muted/40 p-3.5 rounded-lg border border-border/60">
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-4 w-4 text-primary" />
                                        <span className="text-sm font-semibold text-foreground">
                                            Jadwal Hari {selectedDay} (Jam Ke-1 s.d. Jam Ke-10)
                                        </span>
                                        {selectedDay === todayName && (
                                            <Badge variant="default" className="text-[11px] bg-emerald-600 text-white hover:bg-emerald-600">
                                                Aktif Hari Ini
                                            </Badge>
                                        )}
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        Rombel Binaan: <strong className="text-foreground">{currentClass}</strong> &bull; Putaran: <strong className="text-foreground">{selectedCycle === 'all' ? 'Semua' : selectedCycle}</strong>
                                    </span>
                                </div>

                                <div className="overflow-x-auto rounded-lg border border-border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                                <TableHead className="w-28 text-center font-bold">Jam Pelajaran</TableHead>
                                                <TableHead className="w-32 font-bold">Waktu</TableHead>
                                                <TableHead className="font-bold">Mata Pelajaran</TableHead>
                                                <TableHead className="font-bold">Guru Pengampu</TableHead>
                                                <TableHead className="w-32 font-bold">Ruangan</TableHead>
                                                <TableHead className="w-36 text-center font-bold">Status Sesi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {timeSlots.map((slot) => {
                                                const slotStart = slot.start_time.substring(0, 5);
                                                const slotEnd = slot.end_time.substring(0, 5);
                                                const liveStatus = getSlotLiveStatus(slot, selectedDay);

                                                // 1. Baris Waktu Istirahat Resmi
                                                if (slot.is_break) {
                                                    const isLongBreak = slot.order >= 7;
                                                    return (
                                                        <TableRow
                                                            key={slot.id}
                                                            className="bg-amber-500/10 dark:bg-amber-500/15 hover:bg-amber-500/15 border-y border-amber-300 dark:border-amber-700/50"
                                                        >
                                                            <TableCell className="text-center">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="bg-amber-500/20 text-amber-800 dark:text-amber-300 border-amber-400 font-semibold text-[11px]"
                                                                >
                                                                    Istirahat
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="font-mono text-xs font-semibold text-amber-900 dark:text-amber-200">
                                                                {slotStart} - {slotEnd}
                                                            </TableCell>
                                                            <TableCell colSpan={3} className="py-2.5">
                                                                <div className="flex items-center gap-2 text-xs font-medium text-amber-900 dark:text-amber-200">
                                                                    <Coffee className="h-4 w-4 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                                                                    <span>
                                                                        {slot.name} &bull;{' '}
                                                                        {isLongBreak
                                                                            ? 'Istirahat Siang & Sholat Dzuhur (30 Menit)'
                                                                            : 'Jeda Istirahat Pagi (15 Menit)'}
                                                                    </span>
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {liveStatus === 'active' ? (
                                                                    <Badge className="bg-amber-600 text-white font-bold text-xs shadow-sm ring-2 ring-amber-500/30">
                                                                        Sedang Istirahat
                                                                    </Badge>
                                                                ) : (
                                                                    <span className="text-[11px] text-amber-700/80 dark:text-amber-400/80 font-medium">
                                                                        Jeda KBM
                                                                    </span>
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                }

                                                // 2. Baris Jam Pelajaran KBM
                                                const isCycleCeremonyActive = selectedCycle === '2'
                                                    ? Boolean(config?.ceremony_cycle_2)
                                                    : (selectedCycle === '1' ? Boolean(config?.ceremony_cycle_1) : Boolean(config?.active_cycle === 2 ? config?.ceremony_cycle_2 : config?.ceremony_cycle_1));

                                                const isMondayCeremony = selectedDay === 'Senin' && (slot.order === 1 || slot.name === 'Jam Ke-1') && isCycleCeremonyActive;

                                                if (isMondayCeremony) {
                                                    return (
                                                        <TableRow
                                                            key={slot.id}
                                                            className="bg-red-500/10 dark:bg-red-500/15 hover:bg-red-500/15 border-y border-red-300 dark:border-red-700/50"
                                                        >
                                                            <TableCell className="text-center font-bold text-xs">
                                                                <span className="inline-block px-2.5 py-1 rounded font-mono font-bold text-xs bg-red-600 text-white shadow-sm">
                                                                    {slot.name}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="font-mono text-xs font-semibold text-red-900 dark:text-red-200">
                                                                {slotStart} - {slotEnd}
                                                            </TableCell>
                                                            <TableCell colSpan={3} className="py-2.5">
                                                                <div className="flex items-center gap-2 text-xs font-semibold text-red-900 dark:text-red-200">
                                                                    <Flag className="h-4 w-4 text-red-600 dark:text-red-400 flex-shrink-0" />
                                                                    <span>Upacara Bendera Hari Senin &bull; Lapangan Utama (Wajib Hadir Seluruh Siswa Kelas Binaan)</span>
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                <Badge variant="outline" className="bg-red-500/20 text-red-800 dark:text-red-300 border-red-400 font-bold text-[11px] gap-1">
                                                                    <Flag className="h-3 w-3" /> Upacara
                                                                </Badge>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                }

                                                const schedule = getScheduleForSlot(selectedDay, slot);
                                                const isFridayAfternoon = selectedDay === 'Jumat' && (slot.name === 'Jam Ke-9' || slot.name === 'Jam Ke-10');

                                                return (
                                                    <TableRow
                                                        key={slot.id}
                                                        className={`transition-colors ${
                                                            liveStatus === 'active'
                                                                ? 'bg-emerald-100/90 dark:bg-emerald-950/70 border-y-2 border-y-emerald-600/40 border-l-4 border-l-emerald-600 dark:border-l-emerald-400 shadow-sm'
                                                                : ''
                                                        }`}
                                                    >
                                                        <TableCell className="text-center font-bold text-xs">
                                                            <span className={`inline-block px-2.5 py-1 rounded font-mono font-bold text-xs ${
                                                                liveStatus === 'active'
                                                                    ? 'bg-emerald-600 dark:bg-emerald-500 text-white shadow-sm'
                                                                    : 'bg-muted text-foreground'
                                                            }`}>
                                                                {slot.name}
                                                            </span>
                                                        </TableCell>

                                                        <TableCell className="font-mono text-xs font-medium text-foreground">
                                                            {slotStart} - {slotEnd}
                                                        </TableCell>

                                                        <TableCell>
                                                            {schedule ? (
                                                                <div>
                                                                    <span className={`text-sm block ${
                                                                        liveStatus === 'active'
                                                                            ? 'font-extrabold text-emerald-950 dark:text-emerald-50'
                                                                            : 'font-semibold text-foreground'
                                                                    }`}>
                                                                        {schedule.subject}
                                                                    </span>
                                                                    <div className="flex items-center gap-1.5 mt-0.5">
                                                                        <span className="text-[11px] text-muted-foreground font-mono">
                                                                            Putaran {schedule.cycle_number}
                                                                        </span>
                                                                        {liveStatus === 'active' && (
                                                                            <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                                                (Berlangsung)
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ) : isFridayAfternoon ? (
                                                                <div className="flex items-center gap-1.5 text-xs text-muted-foreground italic">
                                                                    <CheckCircle2 className="h-3.5 w-3.5 text-muted-foreground/80" />
                                                                    <span>Kegiatan Mandiri / Kepulangan Siswa</span>
                                                                </div>
                                                            ) : (
                                                                <span className="text-xs text-muted-foreground/70 italic">
                                                                    Jam Kosong / Mandiri
                                                                </span>
                                                            )}
                                                        </TableCell>

                                                        <TableCell className="text-xs sm:text-sm">
                                                            {schedule ? (
                                                                <div className="flex items-center gap-1.5 text-foreground">
                                                                    <User className="h-3.5 w-3.5 text-muted-foreground flex-shrink-0" />
                                                                    <span>{schedule.teacher?.fullname || '-'}</span>
                                                                </div>
                                                            ) : (
                                                                <span className="text-muted-foreground/60">-</span>
                                                            )}
                                                        </TableCell>

                                                        <TableCell>
                                                            {schedule?.room_name ? (
                                                                <Badge variant="secondary" className="gap-1 text-xs font-medium">
                                                                    <MapPin className="h-3 w-3 text-muted-foreground" />
                                                                    <span>{schedule.room_name}</span>
                                                                </Badge>
                                                            ) : schedule ? (
                                                                <Badge variant="outline" className="text-xs text-muted-foreground">
                                                                    Ruang Kelas
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-muted-foreground/60">-</span>
                                                            )}
                                                        </TableCell>

                                                        <TableCell className="text-center">
                                                            {liveStatus === 'active' && schedule ? (
                                                                <Link href={`/guru/journal/create?schedule_id=${schedule.id}`}>
                                                                    <Button size="sm" className="h-8 gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                                                                        <ClipboardList className="h-3.5 w-3.5" />
                                                                        Isi Jurnal
                                                                    </Button>
                                                                </Link>
                                                            ) : liveStatus === 'active' ? (
                                                                <span className="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 dark:bg-emerald-500 text-white shadow-sm ring-2 ring-emerald-600/25">
                                                                    Berlangsung
                                                                </span>
                                                            ) : liveStatus === 'past' ? (
                                                                <span className="text-[11px] text-muted-foreground font-medium">
                                                                    Selesai
                                                                </span>
                                                            ) : liveStatus === 'future' ? (
                                                                <span className="text-[11px] text-muted-foreground font-medium">
                                                                    Mendatang
                                                                </span>
                                                            ) : schedule ? (
                                                                <Badge variant="outline" className="text-[11px] font-normal">
                                                                    Terjadwal
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-[11px] text-muted-foreground/50">-</span>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        ) : (
                            /* TAMPILAN BLOK SESI KBM / SEMUA HARI */
                            <div className="space-y-4">
                                {selectedDay === 'all' && (
                                    <div className="flex items-center gap-2 bg-muted/40 p-3 rounded-lg border border-border/60">
                                        <Calendar className="h-4 w-4 text-primary" />
                                        <span className="text-sm font-semibold text-foreground">
                                            Menampilkan Seluruh Jadwal Pembelajaran Mingguan (Senin s.d. Jumat)
                                        </span>
                                    </div>
                                )}

                                <div className="overflow-x-auto rounded-lg border border-border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                                <TableHead className="w-12 text-center font-bold">No</TableHead>
                                                <TableHead className="font-bold">Hari & Putaran</TableHead>
                                                <TableHead className="font-bold">Waktu Pembelajaran</TableHead>
                                                <TableHead className="font-bold">Mata Pelajaran</TableHead>
                                                <TableHead className="font-bold">Guru Pengampu</TableHead>
                                                <TableHead className="font-bold">Ruangan</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredBlockSchedules.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="text-center py-8 text-sm text-muted-foreground">
                                                        Tidak ada sesi KBM pada hari {selectedDay}.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                filteredBlockSchedules.map((schedule, idx) => (
                                                    <TableRow key={schedule.id}>
                                                        <TableCell className="text-center font-mono text-xs">
                                                            {idx + 1}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-semibold text-sm">{schedule.day_of_week}</span>
                                                                <Badge variant="outline" className="text-[11px] font-mono">
                                                                    Putaran {schedule.cycle_number}
                                                                </Badge>
                                                                {schedule.day_of_week === todayName && (
                                                                    <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                                                        Hari Ini
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <span className="font-semibold block text-foreground font-mono">
                                                                {schedule.start_time.substring(0, 5)} - {schedule.end_time.substring(0, 5)}
                                                            </span>
                                                            {schedule.start_slot && schedule.end_slot && (
                                                                <span className="text-muted-foreground font-mono text-[11px]">
                                                                    {schedule.start_slot.name} s.d. {schedule.end_slot.name}
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="font-semibold text-foreground text-sm">
                                                            {schedule.subject}
                                                        </TableCell>
                                                        <TableCell className="text-xs sm:text-sm">
                                                            <div className="flex items-center gap-1.5">
                                                                <User className="h-3.5 w-3.5 text-muted-foreground flex-shrink-0" />
                                                                <span>{schedule.teacher?.fullname || '-'}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            {schedule.room_name ? (
                                                                <Badge variant="secondary" className="gap-1 text-xs">
                                                                    <MapPin className="h-3 w-3 text-muted-foreground" />
                                                                    <span>{schedule.room_name}</span>
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="text-xs text-muted-foreground">
                                                                    Ruang Kelas
                                                                </Badge>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

