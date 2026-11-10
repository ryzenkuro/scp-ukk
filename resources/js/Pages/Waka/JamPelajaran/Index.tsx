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
    Plus,
    Clock,
    Edit,
    Trash2,
    RefreshCw,
    RotateCcw,
    AlertTriangle,
    CheckCircle2,
    Coffee,
    BookOpen,
    Layers,
    Calendar,
    Flag,
} from 'lucide-react';

interface TimeSlot {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_break: boolean;
    order: number;
}

interface Props {
    timeSlots: TimeSlot[];
    activeCycle?: number;
    ceremonySettings?: Record<number, boolean>;
}

export default function JamPelajaranIndex({ timeSlots, activeCycle = 1, ceremonySettings }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isResetAllOpen, setIsResetAllOpen] = useState(false);
    const [isSyncOpen, setIsSyncOpen] = useState(false);
    const [isSwitchingCycle, setIsSwitchingCycle] = useState(false);
    const [isSyncing, setIsSyncing] = useState(false);
    const [editingSlot, setEditingSlot] = useState<TimeSlot | null>(null);
    const [deletingSlot, setDeletingSlot] = useState<TimeSlot | null>(null);

    // Form Tambah
    const createForm = useForm({
        name: '',
        start_time: '',
        end_time: '',
        is_break: false,
        order: (timeSlots.length + 1) || 1,
    });

    // Form Edit
    const editForm = useForm({
        name: '',
        start_time: '',
        end_time: '',
        is_break: false,
        order: 1,
    });

    // Form Hapus 1 Slot
    const deleteForm = useForm({});

    // Form Reset Semua
    const resetAllForm = useForm({});

    // Form Sync
    const syncForm = useForm({});

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/jam-pelajaran', {
            onSuccess: () => {
                createForm.reset();
                setIsCreateOpen(false);
            },
        });
    };

    const handleEditOpen = (slot: TimeSlot) => {
        setEditingSlot(slot);
        editForm.setData({
            name: slot.name,
            start_time: slot.start_time.substring(0, 5),
            end_time: slot.end_time.substring(0, 5),
            is_break: slot.is_break,
            order: slot.order,
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingSlot) return;
        editForm.put(`/waka/jam-pelajaran/${editingSlot.id}`, {
            onSuccess: () => {
                setEditingSlot(null);
            },
        });
    };

    const handleDeleteSubmit = () => {
        if (!deletingSlot) return;
        deleteForm.delete(`/waka/jam-pelajaran/${deletingSlot.id}`, {
            onSuccess: () => setDeletingSlot(null),
        });
    };

    const submitResetAll = () => {
        resetAllForm.delete('/waka/jam-pelajaran/reset-semua', {
            onSuccess: () => setIsResetAllOpen(false),
        });
    };

    const handleSwitchCycle = (cycle: number) => {
        if (cycle === activeCycle || isSwitchingCycle) return;
        setIsSwitchingCycle(true);
        router.post('/waka/jam-pelajaran/switch-cycle', { cycle }, {
            preserveScroll: true,
            onFinish: () => setIsSwitchingCycle(false),
        });
    };

    const [isUpdatingCeremony, setIsUpdatingCeremony] = useState(false);

    const handleToggleCeremony = (cycle: number, currentValue: boolean) => {
        if (isUpdatingCeremony) return;
        setIsUpdatingCeremony(true);
        router.post('/waka/jam-pelajaran/toggle-ceremony', {
            cycle,
            has_ceremony: !currentValue,
        }, {
            preserveScroll: true,
            onFinish: () => setIsUpdatingCeremony(false),
        });
    };

    const handleSyncSchedule = () => {
        setIsSyncing(true);
        syncForm.post('/waka/jam-pelajaran/sync-jadwal', {
            preserveScroll: true,
            onSuccess: () => {
                setIsSyncOpen(false);
            },
            onFinish: () => setIsSyncing(false),
        });
    };

    const formatTime = (timeStr: string) => {
        return timeStr ? timeStr.substring(0, 5) : '';
    };

    const calculateDuration = (startTime: string, endTime: string) => {
        if (!startTime || !endTime) return '-';
        const [startH, startM] = startTime.split(':').map(Number);
        const [endH, endM] = endTime.split(':').map(Number);
        const totalMinutes = (endH * 60 + endM) - (startH * 60 + startM);
        return totalMinutes > 0 ? `${totalMinutes} Menit` : '-';
    };

    const totalKbm = timeSlots.filter((s) => !s.is_break).length;
    const totalBreak = timeSlots.filter((s) => s.is_break).length;

    return (
        <AppLayout title="Jam Pembelajaran Sekolah">
            <Head title="Master Jam Pembelajaran" />

            <div className="space-y-6">
                {/* Header Section with Cycle Switcher */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-card p-5 rounded-xl border border-border shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-xl font-bold tracking-tight">Master Jam Pelajaran</h2>
                            <Badge variant={activeCycle === 1 ? 'default' : 'secondary'} className="text-xs px-2.5 py-0.5 font-medium">
                                Putaran {activeCycle} Aktif
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Konfigurasi struktur durasi jam belajar dan pergantian siklus kurikulum resmi SMK N 1 Bantul.
                        </p>
                    </div>

                    {/* Cycle Switcher Control */}
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex items-center bg-muted p-1 rounded-lg border border-border/80">
                            <span className="text-xs font-semibold px-2.5 text-muted-foreground flex items-center gap-1.5">
                                <Layers className="w-3.5 h-3.5" />
                                Siklus:
                            </span>
                            <button
                                type="button"
                                onClick={() => handleSwitchCycle(1)}
                                disabled={isSwitchingCycle}
                                className={`px-3 py-1 text-xs font-semibold rounded-md transition-all ${
                                    activeCycle === 1
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-background/60'
                                }`}
                            >
                                Putaran 1
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSwitchCycle(2)}
                                disabled={isSwitchingCycle}
                                className={`px-3 py-1 text-xs font-semibold rounded-md transition-all ${
                                    activeCycle === 2
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-background/60'
                                }`}
                            >
                                Putaran 2
                            </button>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setIsSyncOpen(true)}
                                className="gap-2 border-emerald-500/30 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-xs"
                            >
                                <RefreshCw className="h-3.5 w-3.5" />
                                Sinkronisasi dari Jadwal
                            </Button>

                            <Button
                                onClick={() => setIsCreateOpen(true)}
                                className="gap-2 text-xs"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Tambah Jam
                            </Button>

                            {timeSlots.length > 0 && (
                                <Button
                                    variant="outline"
                                    onClick={() => setIsResetAllOpen(true)}
                                    className="gap-2 text-destructive hover:bg-destructive/10 border-destructive/30 text-xs"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    Reset Jam
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Stat Summary Cards */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Card className="border border-border/80 bg-card">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-primary/10 text-primary">
                                <Clock className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground font-medium">Total Sesi</p>
                                <h3 className="text-xl font-bold">{timeSlots.length}</h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/80 bg-card">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-blue-500/10 text-blue-600">
                                <BookOpen className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground font-medium">Jam Belajar (KBM)</p>
                                <h3 className="text-xl font-bold text-blue-600">{totalKbm}</h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/80 bg-card">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-amber-500/10 text-amber-600">
                                <Coffee className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground font-medium">Istirahat</p>
                                <h3 className="text-xl font-bold text-amber-600">{totalBreak}</h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/80 bg-card">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-600">
                                <Calendar className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground font-medium">Siklus Aktif</p>
                                <h3 className="text-xl font-bold text-emerald-600">Putaran {activeCycle}</h3>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Pengaturan Upacara Bendera per Putaran (On/Off) */}
                <Card className="border border-border/80 shadow-sm bg-card overflow-hidden">
                    <CardHeader className="py-3.5 px-4 sm:px-6 bg-muted/40 border-b border-border/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400">
                                <Flag className="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle className="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                                    Pengaturan Upacara Bendera (Hari Senin Jam Ke-1)
                                </CardTitle>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Ketika diaktifkan (ON), sesi Jam Ke-1 (07:15 - 08:00) pada hari Senin otomatis digantikan dengan agenda resmi Upacara Bendera di seluruh jadwal (Waka, Guru, Siswa, Ortu, Wali Kelas).
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Putaran 1 Toggle */}
                            <div className={`p-4 rounded-xl border transition-all ${
                                ceremonySettings?.[1]
                                    ? 'bg-red-50/50 dark:bg-red-950/20 border-red-300 dark:border-red-800/60 shadow-sm'
                                    : 'bg-muted/30 border-border/80'
                            }`}>
                                <div className="flex items-center justify-between gap-3">
                                    <div className="flex items-center gap-2.5">
                                        <Badge variant="outline" className={`font-bold text-xs ${
                                            activeCycle === 1 ? 'border-primary text-primary bg-primary/5' : ''
                                        }`}>
                                            Putaran 1 {activeCycle === 1 && '(Aktif)'}
                                        </Badge>
                                        <span className="text-xs sm:text-sm font-semibold text-foreground">
                                            Upacara Bendera
                                        </span>
                                    </div>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={ceremonySettings?.[1] ? 'default' : 'outline'}
                                        disabled={isUpdatingCeremony}
                                        onClick={() => handleToggleCeremony(1, ceremonySettings?.[1] ?? false)}
                                        className={`gap-1.5 h-8 text-xs font-bold transition-all ${
                                            ceremonySettings?.[1]
                                                ? 'bg-red-600 hover:bg-red-700 text-white'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <Flag className="h-3.5 w-3.5" />
                                        <span>{ceremonySettings?.[1] ? 'ON (Ada Upacara)' : 'OFF (Tanpa Upacara)'}</span>
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground mt-2">
                                    {ceremonySettings?.[1]
                                        ? 'Jam Ke-1 (07:15 - 08:00) pada hari Senin di Putaran 1 ditetapkan sebagai Upacara Bendera untuk seluruh sekolah.'
                                        : 'Putaran 1 tidak ada upacara bendera. Jam Ke-1 pada hari Senin berjalan normal untuk pembelajaran KBM mapel.'}
                                </p>
                            </div>

                            {/* Putaran 2 Toggle */}
                            <div className={`p-4 rounded-xl border transition-all ${
                                ceremonySettings?.[2]
                                    ? 'bg-red-50/50 dark:bg-red-950/20 border-red-300 dark:border-red-800/60 shadow-sm'
                                    : 'bg-muted/30 border-border/80'
                            }`}>
                                <div className="flex items-center justify-between gap-3">
                                    <div className="flex items-center gap-2.5">
                                        <Badge variant="outline" className={`font-bold text-xs ${
                                            activeCycle === 2 ? 'border-primary text-primary bg-primary/5' : ''
                                        }`}>
                                            Putaran 2 {activeCycle === 2 && '(Aktif)'}
                                        </Badge>
                                        <span className="text-xs sm:text-sm font-semibold text-foreground">
                                            Upacara Bendera
                                        </span>
                                    </div>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={ceremonySettings?.[2] ? 'default' : 'outline'}
                                        disabled={isUpdatingCeremony}
                                        onClick={() => handleToggleCeremony(2, ceremonySettings?.[2] ?? false)}
                                        className={`gap-1.5 h-8 text-xs font-bold transition-all ${
                                            ceremonySettings?.[2]
                                                ? 'bg-red-600 hover:bg-red-700 text-white'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <Flag className="h-3.5 w-3.5" />
                                        <span>{ceremonySettings?.[2] ? 'ON (Ada Upacara)' : 'OFF (Tanpa Upacara)'}</span>
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground mt-2">
                                    {ceremonySettings?.[2]
                                        ? 'Jam Ke-1 (07:15 - 08:00) pada hari Senin di Putaran 2 ditetapkan sebagai Upacara Bendera untuk seluruh sekolah.'
                                        : 'Putaran 2 tidak ada upacara bendera. Jam Ke-1 pada hari Senin berjalan normal untuk pembelajaran KBM mapel.'}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Empty State after Reset / No Slots */}
                {timeSlots.length === 0 ? (
                    <Card className="border-dashed border-2 border-primary/30 bg-primary/5 p-8 text-center">
                        <div className="max-w-md mx-auto space-y-4">
                            <div className="w-14 h-14 mx-auto rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                <Clock className="w-7 h-7" />
                            </div>
                            <div className="space-y-1">
                                <h3 className="text-lg font-bold tracking-tight">Master Jam Pelajaran Kosong</h3>
                                <p className="text-sm text-muted-foreground">
                                    Data master jam pelajaran saat ini kosong (atau baru saja di-reset). Anda dapat menyelaraskan langsung dari jadwal resmi sekolah (12 sesi jam KBM standar 07:15 - 15:30) atau menambahkannya secara manual.
                                </p>
                            </div>
                            <div className="flex flex-col sm:flex-row items-center justify-center gap-2.5 pt-2">
                                <Button
                                    onClick={() => setIsSyncOpen(true)}
                                    className="gap-2 w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white"
                                >
                                    <RefreshCw className="h-4 w-4" />
                                    Sinkronisasi dari Jadwal Sekolah (Sync)
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => setIsCreateOpen(true)}
                                    className="gap-2 w-full sm:w-auto"
                                >
                                    <Plus className="h-4 w-4" />
                                    Tambah Manual
                                </Button>
                            </div>
                        </div>
                    </Card>
                ) : (
                    /* Main Table Card */
                    <Card className="border border-border/80 shadow-sm">
                        <CardHeader className="py-4 border-b border-border/80 flex flex-row items-center justify-between">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <Clock className="h-4 w-4 text-primary" />
                                Daftar Sesi Jam Pembelajaran Resmi
                            </CardTitle>
                            <span className="text-xs text-muted-foreground">
                                Menampilkan {timeSlots.length} sesi terurut
                            </span>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                                            <TableHead className="w-16 text-center font-bold">Urutan</TableHead>
                                            <TableHead className="font-bold">Nama Sesi Jam</TableHead>
                                            <TableHead className="text-center font-bold">Jam Mulai</TableHead>
                                            <TableHead className="text-center font-bold">Jam Selesai</TableHead>
                                            <TableHead className="text-center font-bold">Durasi</TableHead>
                                            <TableHead className="text-center font-bold">Kategori / Tipe</TableHead>
                                            <TableHead className="w-28 text-center font-bold">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {timeSlots.map((slot) => (
                                            <TableRow
                                                key={slot.id}
                                                className={`transition-colors ${
                                                    slot.is_break
                                                        ? 'bg-amber-50/40 hover:bg-amber-50/70 dark:bg-amber-950/10'
                                                        : 'hover:bg-muted/40'
                                                }`}
                                            >
                                                <TableCell className="text-center font-mono font-semibold text-xs">
                                                    #{slot.order}
                                                </TableCell>
                                                <TableCell className="font-medium text-foreground">
                                                    <div className="flex items-center gap-2">
                                                        {slot.is_break ? (
                                                            <Coffee className="w-4 h-4 text-amber-600 flex-shrink-0" />
                                                        ) : (
                                                            <BookOpen className="w-4 h-4 text-primary flex-shrink-0" />
                                                        )}
                                                        <span>{slot.name}</span>
                                                        {(slot.order === 1 || slot.name === 'Jam Ke-1') && ceremonySettings?.[activeCycle] && (
                                                            <Badge variant="outline" className="text-[10px] bg-red-100/70 text-red-700 dark:bg-red-950/40 dark:text-red-300 border-red-300/80 gap-1 font-semibold">
                                                                <Flag className="h-2.5 w-2.5" /> Upacara Senin
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center font-mono font-medium text-sm">
                                                    {formatTime(slot.start_time)}
                                                </TableCell>
                                                <TableCell className="text-center font-mono font-medium text-sm">
                                                    {formatTime(slot.end_time)}
                                                </TableCell>
                                                <TableCell className="text-center text-xs text-muted-foreground font-medium">
                                                    {calculateDuration(slot.start_time, slot.end_time)}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {slot.is_break ? (
                                                        <Badge variant="outline" className="bg-amber-100/60 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-300/60 text-xs">
                                                            Istirahat
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="bg-blue-100/60 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border-blue-300/60 text-xs">
                                                            Jam Pembelajaran
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <div className="flex items-center justify-center gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEditOpen(slot)}
                                                            className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                                                        >
                                                            <Edit className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setDeletingSlot(slot)}
                                                            className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Modal Tambah Jam Pembelajaran */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Plus className="h-5 w-5 text-primary" />
                            Tambah Jam Pembelajaran
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreateSubmit} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="create_name" className="text-xs font-semibold">
                                Nama Jam / Sesi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="create_name"
                                placeholder="Misal: Jam Ke-1, Istirahat Pagi, Upacara"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                className="h-9"
                                required
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-destructive">{createForm.errors.name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="create_start" className="text-xs font-semibold">
                                    Jam Mulai <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="create_start"
                                    type="time"
                                    value={createForm.data.start_time}
                                    onChange={(e) => createForm.setData('start_time', e.target.value)}
                                    className="h-9 font-mono"
                                    required
                                />
                                {createForm.errors.start_time && (
                                    <p className="text-xs text-destructive">{createForm.errors.start_time}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="create_end" className="text-xs font-semibold">
                                    Jam Selesai <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="create_end"
                                    type="time"
                                    value={createForm.data.end_time}
                                    onChange={(e) => createForm.setData('end_time', e.target.value)}
                                    className="h-9 font-mono"
                                    required
                                />
                                {createForm.errors.end_time && (
                                    <p className="text-xs text-destructive">{createForm.errors.end_time}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="create_order" className="text-xs font-semibold">
                                Nomor Urutan Sesi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="create_order"
                                type="number"
                                min={1}
                                value={createForm.data.order}
                                onChange={(e) => createForm.setData('order', parseInt(e.target.value) || 1)}
                                className="h-9 font-mono"
                                required
                            />
                            {createForm.errors.order && (
                                <p className="text-xs text-destructive">{createForm.errors.order}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="create_is_break"
                                checked={createForm.data.is_break}
                                onChange={(e) => createForm.setData('is_break', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label htmlFor="create_is_break" className="text-xs cursor-pointer select-none">
                                Tandai sebagai Waktu Istirahat (bukan sesi KBM mata pelajaran)
                            </Label>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Jam'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Jam Pembelajaran */}
            <Dialog open={editingSlot !== null} onOpenChange={(open) => !open && setEditingSlot(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Edit className="h-5 w-5 text-primary" />
                            Edit Jam Pembelajaran
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_name" className="text-xs font-semibold">
                                Nama Jam / Sesi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="edit_name"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                className="h-9"
                                required
                            />
                            {editForm.errors.name && (
                                <p className="text-xs text-destructive">{editForm.errors.name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_start" className="text-xs font-semibold">
                                    Jam Mulai <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="edit_start"
                                    type="time"
                                    value={editForm.data.start_time}
                                    onChange={(e) => editForm.setData('start_time', e.target.value)}
                                    className="h-9 font-mono"
                                    required
                                />
                                {editForm.errors.start_time && (
                                    <p className="text-xs text-destructive">{editForm.errors.start_time}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_end" className="text-xs font-semibold">
                                    Jam Selesai <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="edit_end"
                                    type="time"
                                    value={editForm.data.end_time}
                                    onChange={(e) => editForm.setData('end_time', e.target.value)}
                                    className="h-9 font-mono"
                                    required
                                />
                                {editForm.errors.end_time && (
                                    <p className="text-xs text-destructive">{editForm.errors.end_time}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_order" className="text-xs font-semibold">
                                Nomor Urutan Sesi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="edit_order"
                                type="number"
                                min={1}
                                value={editForm.data.order}
                                onChange={(e) => editForm.setData('order', parseInt(e.target.value) || 1)}
                                className="h-9 font-mono"
                                required
                            />
                            {editForm.errors.order && (
                                <p className="text-xs text-destructive">{editForm.errors.order}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="edit_is_break"
                                checked={editForm.data.is_break}
                                onChange={(e) => editForm.setData('is_break', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label htmlFor="edit_is_break" className="text-xs cursor-pointer select-none">
                                Tandai sebagai Waktu Istirahat (bukan sesi KBM mata pelajaran)
                            </Label>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setEditingSlot(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui Jam'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Konfirmasi Sinkronisasi dari Jadwal Sekolah */}
            <Dialog open={isSyncOpen} onOpenChange={setIsSyncOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-emerald-600">
                            <RefreshCw className="h-5 w-5" />
                            Sinkronisasi dari Jadwal Sekolah
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm text-muted-foreground pt-1">
                        <p>
                            Sistem akan mengisi dan menyelaraskan otomatis <strong>12 sesi jam pembelajaran resmi</strong> SMK N 1 Bantul (10 jam KBM + 2 jam istirahat) dari data kurikulum aktif <strong>Putaran {activeCycle}</strong>.
                        </p>
                        <div className="bg-emerald-50 dark:bg-emerald-950/30 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-200 space-y-1.5">
                            <div className="font-semibold flex items-center gap-1.5">
                                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                Standar Durasi Jam Sekolah:
                            </div>
                            <ul className="list-disc list-inside space-y-0.5 text-muted-foreground ml-1">
                                <li>Jam Ke-1 sd Ke-3: 07:15 - 09:30</li>
                                <li>Istirahat Pagi: 09:30 - 09:45</li>
                                <li>Jam Ke-4 sd Ke-6: 09:45 - 12:00</li>
                                <li>Istirahat Siang: 12:00 - 12:30</li>
                                <li>Jam Ke-7 sd Ke-10: 12:30 - 15:30</li>
                            </ul>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            ID referensi slot pada data jadwal pelajaran yang ada juga akan otomatis ditautkan kembali.
                        </p>
                    </div>
                    <DialogFooter className="pt-2">
                        <Button type="button" variant="outline" onClick={() => setIsSyncOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            className="bg-emerald-600 hover:bg-emerald-700 text-white gap-2"
                            onClick={handleSyncSchedule}
                            disabled={isSyncing}
                        >
                            <RefreshCw className={`h-4 w-4 ${isSyncing ? 'animate-spin' : ''}`} />
                            {isSyncing ? 'Menyelaraskan...' : 'Sinkronkan Sekarang'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Semua Jam Pembelajaran */}
            <Dialog open={isResetAllOpen} onOpenChange={setIsResetAllOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Reset Seluruh Master Jam Pelajaran
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm text-muted-foreground pt-1">
                        <p>
                            Apakah Anda yakin ingin mengosongkan seluruh data master jam pelajaran ({timeSlots.length} sesi) secara keseluruhan dari 0?
                        </p>
                        <p className="text-xs bg-muted p-3 rounded-lg border border-border">
                            Setelah dikosongkan, Anda dapat menggunakan tombol <strong>Sinkronisasi dari Jadwal</strong> untuk memulihkan konfigurasi 12 sesi jam KBM standar secara instan.
                        </p>
                    </div>
                    <DialogFooter className="pt-2">
                        <Button type="button" variant="outline" onClick={() => setIsResetAllOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={submitResetAll}
                            disabled={resetAllForm.processing}
                        >
                            {resetAllForm.processing ? 'Mengosongkan...' : 'Ya, Kosongkan Seluruh Jam'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Hapus 1 Slot */}
            <Dialog open={deletingSlot !== null} onOpenChange={(open) => !open && setDeletingSlot(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Jam Pembelajaran</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Apakah Anda yakin ingin menghapus jam pembelajaran <strong>{deletingSlot?.name}</strong>?
                    </p>
                    <p className="text-xs text-muted-foreground mt-2 bg-muted p-2.5 rounded border border-border">
                        Penghapusan hanya berlaku pada Master Jam Pembelajaran ini saja dan tidak merubah atau menghapus data jadwal pelajaran yang sudah ada.
                    </p>
                    <DialogFooter className="pt-2">
                        <Button type="button" variant="outline" onClick={() => setDeletingSlot(null)}>
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDeleteSubmit}
                            disabled={deleteForm.processing}
                        >
                            {deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
