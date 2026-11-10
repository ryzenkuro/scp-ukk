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
    Calendar,
    CalendarPlus,
    Lock,
    Unlock,
    Search,
    Edit2,
    Trash2,
    AlertTriangle,
    ShieldAlert,
    Clock,
    UserCheck,
    Building2,
    BookOpen,
    Printer,
    FileSpreadsheet,
    Upload,
    Download,
    FileText,
    Layers,
    CheckCircle2,
    X,
    Flag,
} from 'lucide-react';

interface TimeSlotItem {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_break: boolean;
    order: number;
}

interface SchoolClassItem {
    id: number;
    name: string;
    grade: string;
    jurusan: string;
}

interface RoomItem {
    id: number;
    name: string;
}

interface TeacherItem {
    id: number;
    fullname: string;
    username: string;
}

interface ScheduleItem {
    id: number;
    day_of_week: string;
    cycle_number: number;
    subject: string;
    teacher_id: number;
    class_id: number | null;
    class_name: string;
    room_id: number | null;
    room_name: string | null;
    start_time: string;
    end_time: string;
    start_slot_id: number | null;
    end_slot_id: number | null;
    academic_year: string;
    teacher?: TeacherItem;
    school_class?: SchoolClassItem;
    room?: RoomItem;
    start_slot?: TimeSlotItem;
    end_slot?: TimeSlotItem;
}

interface IndexProps {
    schedules: ScheduleItem[];
    filters: {
        cycle: string;
        day: string;
        class_id: string;
        teacher_id: string;
    };
    classes: SchoolClassItem[];
    rooms: RoomItem[];
    teachers: TeacherItem[];
    timeSlots: TimeSlotItem[];
    config: {
        active_cycle: number;
        is_scheduled_locked: boolean;
        academic_year: string;
        ceremony_cycle_1?: boolean;
        ceremony_cycle_2?: boolean;
    };
}

export default function JadwalIndex({
    schedules,
    filters,
    classes,
    rooms,
    teachers,
    timeSlots,
    config,
}: IndexProps) {
    const isLocked = config.is_scheduled_locked;

    const [selectedCycle, setSelectedCycle] = useState(filters.cycle || config.active_cycle.toString());
    const [selectedDay, setSelectedDay] = useState(filters.day || 'all');
    const [selectedClass, setSelectedClass] = useState(filters.class_id || 'all');
    const [selectedTeacher, setSelectedTeacher] = useState(filters.teacher_id || 'all');

    // Dialog state
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editItem, setEditItem] = useState<ScheduleItem | null>(null);
    const [deleteItem, setDeleteItem] = useState<ScheduleItem | null>(null);
    const [isImportOpen, setIsImportOpen] = useState(false);
    const [isResetAllOpen, setIsResetAllOpen] = useState(false);

    const importForm = useForm<{
        file: File | null;
        files: File[];
        cycle_number: string;
    }>({
        file: null,
        files: [],
        cycle_number: 'auto',
    });

    const getFileClassification = (file: File) => {
        const name = file.name.toLowerCase();
        const ext = name.split('.').pop() || '';
        if (ext === 'md') {
            return {
                label: 'Master Markdown (.md)',
                badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300',
                desc: 'Tabel jadwal kurasi'
            };
        }
        if (['xlsx', 'xls', 'csv'].includes(ext)) {
            return {
                label: 'Spreadsheet Excel',
                badgeClass: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300 border-green-300',
                desc: 'Template / Data Jadwal'
            };
        }
        if (ext === 'pdf') {
            const isRuang = name.includes('ruang') || name.includes('ruangan');
            const isPut2 = name.includes('put_2') || name.includes('put 2') || name.includes('putaran 2') || name.includes('putaran_2') || name.includes('sem ganjil 26_27');
            const isPut1 = name.includes('put_1') || name.includes('put 1') || name.includes('putaran 1') || name.includes('putaran_1') || name.includes('rev_sept');

            if (isRuang) {
                if (isPut2) return {
                    label: 'Jadwal Ruangan Putaran 2',
                    badgeClass: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-300',
                    desc: 'Penempatan lab/ruangan siklus 2'
                };
                if (isPut1) return {
                    label: 'Jadwal Ruangan Putaran 1',
                    badgeClass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-300',
                    desc: 'Penempatan lab/ruangan siklus 1'
                };
                return {
                    label: 'Jadwal Ruangan (PDF)',
                    badgeClass: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-300',
                    desc: 'Matriks ruangan'
                };
            } else {
                if (isPut2) return {
                    label: 'Jadwal Kelas Putaran 2',
                    badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300',
                    desc: 'Jadwal KBM kelas siklus 2'
                };
                if (isPut1) return {
                    label: 'Jadwal Kelas Putaran 1',
                    badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-300',
                    desc: 'Jadwal KBM kelas siklus 1'
                };
                return {
                    label: 'Jadwal Kelas (PDF)',
                    badgeClass: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-300',
                    desc: 'Matriks FET jadwal'
                };
            }
        }
        return {
            label: ext.toUpperCase(),
            badgeClass: 'bg-slate-100 text-slate-800 border-slate-300',
            desc: 'Berkas'
        };
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const removeFileAt = (index: number) => {
        const updated = importForm.data.files.filter((_, i) => i !== index);
        importForm.setData({
            ...importForm.data,
            files: updated,
            file: updated[0] || null,
        });
    };

    const resetAllForm = useForm({});
    const submitResetAll = () => {
        resetAllForm.delete('/waka/jadwal/reset-semua', {
            onSuccess: () => setIsResetAllOpen(false),
        });
    };

    // Form Tambah Jadwal
    const createForm = useForm({
        day_of_week: 'Senin',
        cycle_number: config.active_cycle,
        subject: '',
        teacher_id: teachers[0]?.id?.toString() || '',
        class_id: classes[0]?.id?.toString() || '',
        room_id: rooms[0]?.id?.toString() || '',
        start_slot_id: timeSlots[0]?.id?.toString() || '',
        end_slot_id: timeSlots[0]?.id?.toString() || '',
    });

    // Form Edit Jadwal
    const editForm = useForm({
        day_of_week: 'Senin',
        cycle_number: 1,
        subject: '',
        teacher_id: '',
        class_id: '',
        room_id: '',
        start_slot_id: '',
        end_slot_id: '',
    });

    const handleFilterChange = (key: string, value: string) => {
        const nextFilters = {
            cycle: selectedCycle,
            day: selectedDay,
            class_id: selectedClass,
            teacher_id: selectedTeacher,
            [key]: value,
        };

        if (key === 'cycle') setSelectedCycle(value);
        if (key === 'day') setSelectedDay(value);
        if (key === 'class_id') setSelectedClass(value);
        if (key === 'teacher_id') setSelectedTeacher(value);

        router.get('/waka/jadwal', nextFilters, { preserveState: true });
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/waka/jadwal', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const openEdit = (schedule: ScheduleItem) => {
        setEditItem(schedule);
        editForm.setData({
            day_of_week: schedule.day_of_week,
            cycle_number: schedule.cycle_number,
            subject: schedule.subject,
            teacher_id: schedule.teacher_id.toString(),
            class_id: schedule.class_id ? schedule.class_id.toString() : '',
            room_id: schedule.room_id ? schedule.room_id.toString() : '',
            start_slot_id: schedule.start_slot_id ? schedule.start_slot_id.toString() : '',
            end_slot_id: schedule.end_slot_id ? schedule.end_slot_id.toString() : '',
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editItem) return;
        editForm.put(`/waka/jadwal/${editItem.id}`, {
            onSuccess: () => setEditItem(null),
        });
    };

    const submitDelete = () => {
        if (!deleteItem) return;
        router.delete(`/waka/jadwal/${deleteItem.id}`, {
            onSuccess: () => setDeleteItem(null),
        });
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!importForm.data.file && importForm.data.files.length === 0) return;
        importForm.post('/waka/jadwal/import', {
            forceFormData: true,
            onSuccess: () => {
                setIsImportOpen(false);
                importForm.reset();
            },
        });
    };

    const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    return (
        <AppLayout title="Jadwal Pembelajaran Sekolah">
            <Head title="Jadwal Sekolah" />

            <div className="space-y-6">
                {/* Alert Proteksi Kunci Jadwal (Revisi Fitur Waka #1) */}
                {isLocked ? (
                    <div className="flex items-center gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-destructive shadow-sm">
                        <ShieldAlert className="h-5 w-5 shrink-0" />
                        <div className="text-sm">
                            <span className="font-semibold block">Proteksi Aktif: Jadwal Pembelajaran Sedang Terkunci</span>
                            <span>Penambahan, pengeditan, atau penghapusan jadwal dinonaktifkan oleh Waka Kurikulum. Buka kunci di Dashboard untuk mengubah jadwal.</span>
                        </div>
                    </div>
                ) : null}

                {/* Filter & Action Header */}
                <Card>
                    <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-xl font-bold tracking-tight">
                                    Pengelolaan Jadwal Pelajaran
                                </CardTitle>
                                <Badge variant={isLocked ? "destructive" : "secondary"} className="gap-1 text-xs">
                                    {isLocked ? <Lock className="h-3 w-3" /> : <Unlock className="h-3 w-3" />}
                                    {isLocked ? 'Jadwal Terkunci' : 'Jadwal Terbuka'}
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                Terhubung dengan Master Jam Pembelajaran, dilengkapi validasi otomatis bentrok Guru, Ruangan, dan Kelas.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setIsImportOpen(true)}
                                disabled={isLocked}
                                className="gap-2"
                            >
                                <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                                Import Jadwal
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setIsResetAllOpen(true)}
                                disabled={schedules.length === 0 || isLocked}
                                className="gap-2 text-destructive border-destructive/30 hover:bg-destructive/10"
                            >
                                <Trash2 className="h-4 w-4" />
                                Reset Semua Jadwal
                            </Button>
                            <a 
                                href={`/waka/jadwal/export-pdf?cycle=${selectedCycle}&class=${selectedClass !== 'all' ? selectedClass : ''}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Button variant="outline" className="gap-2">
                                    <Printer className="h-4 w-4" />
                                    Cetak Jadwal (A4)
                                </Button>
                            </a>
                            <Button
                                onClick={() => setIsCreateOpen(true)}
                                disabled={isLocked}
                                className="gap-2"
                            >
                                <CalendarPlus className="h-4 w-4" />
                                Tambah Jadwal Baru
                            </Button>
                        </div>
                    </CardHeader>

                    {/* Filter Bar */}
                    <CardContent className="pt-0">
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Filter Putaran:</Label>
                                <select
                                    value={selectedCycle}
                                    onChange={(e) => handleFilterChange('cycle', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Putaran</option>
                                    <option value="1">Putaran 1</option>
                                    <option value="2">Putaran 2</option>
                                </select>
                            </div>

                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Filter Hari:</Label>
                                <select
                                    value={selectedDay}
                                    onChange={(e) => handleFilterChange('day', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Hari</option>
                                    {days.map((d) => (
                                        <option key={d} value={d}>{d}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Filter Kelas:</Label>
                                <select
                                    value={selectedClass}
                                    onChange={(e) => handleFilterChange('class_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Kelas</option>
                                    {classes.map((c) => (
                                        <option key={c.id} value={c.id.toString()}>{c.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <Label className="text-xs text-muted-foreground mb-1 block">Filter Guru:</Label>
                                <select
                                    value={selectedTeacher}
                                    onChange={(e) => handleFilterChange('teacher_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="all">Semua Guru</option>
                                    {teachers.map((t) => (
                                        <option key={t.id} value={t.id.toString()}>{t.fullname}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Table Jadwal */}
                <Card>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12 text-center">No</TableHead>
                                    <TableHead>Hari & Putaran</TableHead>
                                    <TableHead>Waktu / Jam Pembelajaran</TableHead>
                                    <TableHead>Mata Pelajaran</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Guru Pengampu</TableHead>
                                    <TableHead>Ruangan</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {((filters.cycle === '2' ? config?.ceremony_cycle_2 : config?.ceremony_cycle_1) || (filters.cycle === 'all' && (config?.ceremony_cycle_1 || config?.ceremony_cycle_2))) && (filters.day === 'Senin' || filters.day === 'all') && (
                                    <TableRow className="bg-red-500/10 dark:bg-red-500/15 border-y-2 border-red-300 dark:border-red-800">
                                        <TableCell className="text-center font-bold text-red-700">*</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1.5 font-bold text-red-700">
                                                <span>Senin</span>
                                                <Badge variant="outline" className="text-[10px] bg-red-100 text-red-700 border-red-300">
                                                    {filters.cycle === 'all' ? 'Putaran Aktif' : `Putaran ${filters.cycle}`}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-semibold block text-red-900 dark:text-red-200 text-xs">
                                                07:15 - 08:00
                                            </span>
                                            <span className="text-red-700/80 font-mono text-[11px]">
                                                Jam Ke-1
                                            </span>
                                        </TableCell>
                                        <TableCell className="font-bold text-red-900 dark:text-red-200">
                                            <div className="flex items-center gap-1.5">
                                                <Flag className="h-4 w-4 text-red-600 flex-shrink-0" />
                                                <span>Upacara Bendera</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="bg-red-100/60 text-red-800 border-red-300 text-xs font-semibold">
                                                Semua Kelas
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm font-medium text-red-900 dark:text-red-200">
                                            Seluruh Guru & Tendik
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="text-xs bg-red-100/60 text-red-800 border-red-300">
                                                Lapangan Upacara
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Badge className="bg-red-600 text-white text-[11px]">
                                                Kegiatan Resmi
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {schedules.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-32 text-center text-muted-foreground">
                                            Tidak ada jadwal pembelajaran yang sesuai filter.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    schedules.map((schedule, idx) => (
                                        <TableRow key={schedule.id}>
                                            <TableCell className="text-center font-mono text-xs">
                                                {idx + 1}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1.5 font-medium">
                                                    <span>{schedule.day_of_week}</span>
                                                    <Badge variant="outline" className="text-[11px] font-mono">
                                                        Putaran {schedule.cycle_number}
                                                    </Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-xs">
                                                    <span className="font-semibold block text-foreground">
                                                        {schedule.start_time.substring(0, 5)} - {schedule.end_time.substring(0, 5)}
                                                    </span>
                                                    {schedule.start_slot && schedule.end_slot && (
                                                        <span className="text-muted-foreground font-mono text-[11px]">
                                                            {schedule.start_slot.name} s.d. {schedule.end_slot.name}
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-medium text-foreground">
                                                {schedule.subject}
                                                {schedule.day_of_week === 'Senin' && schedule.start_time.substring(0, 5) === '07:15' && (schedule.cycle_number === 2 ? config?.ceremony_cycle_2 : config?.ceremony_cycle_1) && (
                                                    <span className="block text-[11px] text-red-600 dark:text-red-400 font-semibold mt-0.5">
                                                        (Digantikan Upacara Bendera pada siklus ini)
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary" className="font-medium">
                                                    {schedule.class_name}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {schedule.teacher ? schedule.teacher.fullname : '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="text-xs">
                                                    {schedule.room_name || '-'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        disabled={isLocked}
                                                        onClick={() => openEdit(schedule)}
                                                        className="h-8 w-8 p-0"
                                                        title={isLocked ? 'Terkunci' : 'Edit Jadwal'}
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        disabled={isLocked}
                                                        onClick={() => setDeleteItem(schedule)}
                                                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                        title={isLocked ? 'Terkunci' : 'Hapus Jadwal'}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* Modal Tambah Jadwal */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Tambah Jadwal Pembelajaran Baru</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="day_of_week">Hari Pembelajaran *</Label>
                                <select
                                    id="day_of_week"
                                    value={createForm.data.day_of_week}
                                    onChange={(e) => createForm.setData('day_of_week', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    {days.map((d) => (
                                        <option key={d} value={d}>{d}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="cycle_number">Siklus Putaran *</Label>
                                <select
                                    id="cycle_number"
                                    value={createForm.data.cycle_number}
                                    onChange={(e) => createForm.setData('cycle_number', parseInt(e.target.value))}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="1">Putaran 1</option>
                                    <option value="2">Putaran 2</option>
                                </select>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="subject">Mata Pelajaran *</Label>
                            <Input
                                id="subject"
                                value={createForm.data.subject}
                                onChange={(e) => createForm.setData('subject', e.target.value)}
                                placeholder="Contoh: Pemrograman Web & Bergerak"
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="teacher_id">Guru Pengampu *</Label>
                            <select
                                id="teacher_id"
                                value={createForm.data.teacher_id}
                                onChange={(e) => createForm.setData('teacher_id', e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                required
                            >
                                {teachers.map((t) => (
                                    <option key={t.id} value={t.id.toString()}>{t.fullname}</option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="class_id">Kelas *</Label>
                                <select
                                    id="class_id"
                                    value={createForm.data.class_id}
                                    onChange={(e) => createForm.setData('class_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {classes.map((c) => (
                                        <option key={c.id} value={c.id.toString()}>{c.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="room_id">Ruangan *</Label>
                                <select
                                    id="room_id"
                                    value={createForm.data.room_id}
                                    onChange={(e) => createForm.setData('room_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {rooms.map((r) => (
                                        <option key={r.id} value={r.id.toString()}>{r.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Pilihan Jam Pembelajaran Terhubung ke TimeSlot */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="start_slot_id">Mulai Jam Pembelajaran *</Label>
                                <select
                                    id="start_slot_id"
                                    value={createForm.data.start_slot_id}
                                    onChange={(e) => createForm.setData('start_slot_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {timeSlots.map((ts) => (
                                        <option key={ts.id} value={ts.id.toString()}>
                                            {ts.name} ({ts.start_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="end_slot_id">Sampai Jam Pembelajaran *</Label>
                                <select
                                    id="end_slot_id"
                                    value={createForm.data.end_slot_id}
                                    onChange={(e) => createForm.setData('end_slot_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {timeSlots.map((ts) => (
                                        <option key={ts.id} value={ts.id.toString()}>
                                            {ts.name} ({ts.end_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                Simpan Jadwal
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Jadwal */}
            <Dialog open={!!editItem} onOpenChange={(open) => !open && setEditItem(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit Jadwal: {editItem?.subject}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_day">Hari Pembelajaran *</Label>
                                <select
                                    id="edit_day"
                                    value={editForm.data.day_of_week}
                                    onChange={(e) => editForm.setData('day_of_week', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    {days.map((d) => (
                                        <option key={d} value={d}>{d}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_cycle">Siklus Putaran *</Label>
                                <select
                                    id="edit_cycle"
                                    value={editForm.data.cycle_number}
                                    onChange={(e) => editForm.setData('cycle_number', parseInt(e.target.value))}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="1">Putaran 1</option>
                                    <option value="2">Putaran 2</option>
                                </select>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_subject">Mata Pelajaran *</Label>
                            <Input
                                id="edit_subject"
                                value={editForm.data.subject}
                                onChange={(e) => editForm.setData('subject', e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit_teacher">Guru Pengampu *</Label>
                            <select
                                id="edit_teacher"
                                value={editForm.data.teacher_id}
                                onChange={(e) => editForm.setData('teacher_id', e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                required
                            >
                                {teachers.map((t) => (
                                    <option key={t.id} value={t.id.toString()}>{t.fullname}</option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_class">Kelas *</Label>
                                <select
                                    id="edit_class"
                                    value={editForm.data.class_id}
                                    onChange={(e) => editForm.setData('class_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {classes.map((c) => (
                                        <option key={c.id} value={c.id.toString()}>{c.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_room">Ruangan *</Label>
                                <select
                                    id="edit_room"
                                    value={editForm.data.room_id}
                                    onChange={(e) => editForm.setData('room_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {rooms.map((r) => (
                                        <option key={r.id} value={r.id.toString()}>{r.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_start_slot">Mulai Jam *</Label>
                                <select
                                    id="edit_start_slot"
                                    value={editForm.data.start_slot_id}
                                    onChange={(e) => editForm.setData('start_slot_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {timeSlots.map((ts) => (
                                        <option key={ts.id} value={ts.id.toString()}>
                                            {ts.name} ({ts.start_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_end_slot">Sampai Jam *</Label>
                                <select
                                    id="edit_end_slot"
                                    value={editForm.data.end_slot_id}
                                    onChange={(e) => editForm.setData('end_slot_id', e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    required
                                >
                                    {timeSlots.map((ts) => (
                                        <option key={ts.id} value={ts.id.toString()}>
                                            {ts.name} ({ts.end_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditItem(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Delete Confirmation */}
            <Dialog open={!!deleteItem} onOpenChange={(open) => !open && setDeleteItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Konfirmasi Hapus Jadwal
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Hapus jadwal pelajaran <strong className="text-foreground">{deleteItem?.subject}</strong> ({deleteItem?.class_name}) pada hari {deleteItem?.day_of_week}?
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteItem(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={submitDelete}>
                            Ya, Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Import Jadwal */}
            <Dialog open={isImportOpen} onOpenChange={setIsImportOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileSpreadsheet className="h-5 w-5 text-emerald-600" />
                            Import Jadwal Pelajaran (PDF / Markdown / Excel)
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitImport} className="space-y-4">
                        <div className="bg-primary/5 border border-primary/20 rounded-lg p-3 text-xs text-muted-foreground space-y-1">
                            <span className="font-semibold text-primary block">Format Resmi PDF & Markdown Didukung:</span>
                            <p>
                                Berkas PDF jadwal sekolah (FET) atau tabel Markdown (.md) Putaran 1 & Putaran 2 dapat diunggah langsung. Sistem otomatis memetakan 48 rombel kelas, pengampu, hari KBM, dan jam pelajaran tanpa guru bocor.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            <a href={`/waka/jadwal/master-md?cycle=${selectedCycle}`} className="inline-flex items-center gap-1 font-semibold text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Master Markdown Putaran {selectedCycle} (.md)
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a href="/waka/jadwal/template" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Template Excel
                            </a>
                            <span className="text-muted-foreground">&bull;</span>
                            <a href="/waka/jadwal/contoh" className="inline-flex items-center gap-1 text-primary hover:underline">
                                <Download className="h-3.5 w-3.5" />
                                Contoh 5 Jadwal
                            </a>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="import-jadwal-cycle" className="text-xs">Pilih Target Putaran</Label>
                            <select
                                id="import-jadwal-cycle"
                                value={importForm.data.cycle_number}
                                onChange={(e) => importForm.setData('cycle_number', e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-xs shadow-sm"
                            >
                                <option value="auto">Otomatis (Deteksi dari Nama Berkas / Putaran Aktif)</option>
                                <option value="1">Khusus Putaran 1 (Siklus 1)</option>
                                <option value="2">Khusus Putaran 2 (Siklus 2)</option>
                            </select>
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="import-jadwal-file" className="text-xs font-semibold">
                                    Pilih Berkas Jadwal (Mendukung hingga 4 Berkas Sekaligus)
                                </Label>
                                {importForm.data.files.length > 0 && (
                                    <span className="text-[11px] font-medium text-primary">
                                        {importForm.data.files.length} berkas dipilih
                                    </span>
                                )}
                            </div>

                            <Input
                                id="import-jadwal-file"
                                type="file"
                                multiple
                                accept=".pdf,.xlsx,.xls,.csv,.md"
                                onChange={(e) => {
                                    const selected = e.target.files ? Array.from(e.target.files) : [];
                                    importForm.setData({
                                        ...importForm.data,
                                        files: selected,
                                        file: selected[0] || null,
                                    });
                                }}
                            />

                            {/* Pratinjau Berkas & Auto-Deteksi Jenis Jadwal */}
                            {importForm.data.files.length > 0 && (
                                <div className="space-y-1.5 mt-2 max-h-48 overflow-y-auto rounded-md border p-2 bg-muted/20">
                                    {importForm.data.files.map((f, idx) => {
                                        const info = getFileClassification(f);
                                        return (
                                            <div
                                                key={idx}
                                                className="flex items-center justify-between gap-2 p-1.5 rounded-md bg-card border text-xs"
                                            >
                                                <div className="flex items-center gap-2 min-w-0">
                                                    <span className={`px-2 py-0.5 rounded text-[10px] font-semibold border shrink-0 ${info.badgeClass}`}>
                                                        {info.label}
                                                    </span>
                                                    <span className="truncate font-mono text-[11px]" title={f.name}>
                                                        {f.name}
                                                    </span>
                                                    <span className="text-[10px] text-muted-foreground shrink-0">
                                                        ({formatFileSize(f.size)})
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeFileAt(idx)}
                                                    className="text-muted-foreground hover:text-destructive p-0.5"
                                                    title="Hapus berkas ini"
                                                >
                                                    <X className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* Callout Smart Merger */}
                            <div className="p-2.5 rounded-md bg-primary/5 border border-primary/20 text-[11px] text-muted-foreground space-y-1">
                                <p className="font-semibold text-primary flex items-center gap-1">
                                    <Layers className="h-3.5 w-3.5" /> Smart Merger 4 Berkas:
                                </p>
                                <p>
                                    Anda dapat memilih <strong>Jadwal Kelas (Putaran 1 & 2)</strong> bersamaan dengan <strong>Jadwal Ruangan (Putaran 1 & 2)</strong>. Sistem otomatis menggabungkan penempatan ruangan (Lab, OR, Ruang Kelas) ke setiap sesi pelajaran secara akurat.
                                </p>
                            </div>

                            {importForm.errors.file && (
                                <p className="text-xs text-destructive">{importForm.errors.file}</p>
                            )}
                            {importForm.errors.files && (
                                <p className="text-xs text-destructive">{importForm.errors.files}</p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsImportOpen(false)}>
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={importForm.processing || (importForm.data.files.length === 0 && !importForm.data.file)}
                                className="gap-2"
                            >
                                <Upload className="h-4 w-4" />
                                {importForm.data.files.length > 0
                                    ? `Mulai Import (${importForm.data.files.length} Berkas)`
                                    : 'Mulai Import Jadwal'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Semua Jadwal */}
            <Dialog open={isResetAllOpen} onOpenChange={setIsResetAllOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <AlertTriangle className="h-5 w-5" />
                            Reset Seluruh Jadwal Pelajaran
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm text-muted-foreground pt-1">
                        <p>
                            Apakah Anda yakin ingin mengosongkan seluruh data jadwal pelajaran ({schedules.length} jadwal) secara keseluruhan dari 0?
                        </p>
                        <p className="text-xs bg-muted p-3 rounded-lg border border-border">
                            Tindakan ini ditujukan untuk persiapan tahun ajaran atau angkatan baru. Setelah jadwal dikosongkan, Anda dapat langsung mengimpor berkas jadwal baru (.xlsx/.csv). Master jam pembelajaran sekolah tidak terpengaruh oleh aksi ini.
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
                            {resetAllForm.processing ? 'Mengosongkan...' : 'Ya, Kosongkan Seluruh Jadwal'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

