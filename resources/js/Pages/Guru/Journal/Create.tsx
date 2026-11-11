import React, { useState, useRef } from 'react';
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
    Save,
    Camera,
    Upload,
    Trash2,
    AlertTriangle,
} from 'lucide-react';
import { JournalCameraModal } from '@/Components/camera/JournalCameraModal';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
    gender: 'L' | 'P';
}

interface ApprovedLeaveItem {
    id: number;
    student_id: number;
    type: 'sakit' | 'izin';
    status?: 'approved' | 'rejected' | 'pending';
    start_date: string;
    end_date: string;
    reason: string | null;
    rejection_reason?: string | null;
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
    approvedLeaves?: ApprovedLeaveItem[];
    todayDate: string;
    activeCycle: number;
    isLocked?: boolean;
    graceMinutesRemaining?: number | null;
    timeStatus?: string;
}

export default function JournalCreate({
    schedules = [],
    selectedSchedule,
    students = [],
    approvedLeaves = [],
    todayDate,
    activeCycle,
    isLocked = false,
    graceMinutesRemaining = null,
    timeStatus = 'upcoming',
}: CreateProps) {
    const getApprovedLeave = (studentId: number, dateStr: string) => {
        return approvedLeaves?.find(
            (l) => l.student_id === studentId && dateStr >= l.start_date && dateStr <= (l.end_date || l.start_date)
        );
    };

    const [isCameraOpen, setIsCameraOpen] = useState(false);
    const [photoPreviewUrl, setPhotoPreviewUrl] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    // Initialize attendances for all students
    const initialAttendances = students.map((s) => {
        const leave = getApprovedLeave(s.id, todayDate);
        let status: 'hadir' | 'sakit' | 'izin' | 'alpa' = 'hadir';
        let notes = '';

        if (leave) {
            if (leave.status === 'rejected') {
                status = 'alpa';
                notes = `Izin ditolak: ${leave.rejection_reason || 'Tidak disetujui wali kelas'}`;
            } else {
                status = leave.type === 'sakit' ? 'sakit' : 'izin';
                notes = `Izin terverifikasi: ${leave.reason || ''}`;
            }
        }

        return {
            student_id: s.id,
            status,
            notes,
        };
    });

    const form = useForm<{
        schedule_id: string;
        date: string;
        topic: string;
        notes: string;
        documentation: File | null;
        attendances: Array<{
            student_id: number;
            status: 'hadir' | 'sakit' | 'izin' | 'alpa';
            notes: string;
        }>;
    }>({
        schedule_id: selectedSchedule?.id?.toString() || '',
        date: todayDate,
        topic: '',
        notes: '',
        documentation: null,
        attendances: initialAttendances,
    });

    const handleScheduleChange = (scheduleId: string) => {
        router.get('/guru/journal/create', { schedule_id: scheduleId, date: form.data.date });
    };

    const handleCameraCapture = (file: File, previewUrl: string) => {
        form.setData('documentation', file);
        form.clearErrors('documentation');
        if (photoPreviewUrl) {
            URL.revokeObjectURL(photoPreviewUrl);
        }
        setPhotoPreviewUrl(previewUrl);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        form.setData('documentation', file);
        form.clearErrors('documentation');
        if (photoPreviewUrl) {
            URL.revokeObjectURL(photoPreviewUrl);
        }
        setPhotoPreviewUrl(URL.createObjectURL(file));
    };

    const handleRemovePhoto = () => {
        form.setData('documentation', null);
        if (photoPreviewUrl) {
            URL.revokeObjectURL(photoPreviewUrl);
        }
        setPhotoPreviewUrl(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const setStudentStatus = (index: number, status: 'hadir' | 'sakit' | 'izin' | 'alpa') => {
        const next = [...form.data.attendances];
        next[index] = { ...next[index], status };
        form.setData('attendances', next);
    };

    const setAllHadir = () => {
        const next = form.data.attendances.map((att) => {
            const leave = getApprovedLeave(att.student_id, form.data.date);
            if (leave) {
                // Siswa berizin resmi yang disetujui wali kelas tidak tertimpa hadir
                return att;
            }
            return {
                ...att,
                status: 'hadir' as const,
            };
        });
        form.setData('attendances', next);
    };

    const countStatus = (status: string) => {
        return form.data.attendances.filter((a) => a.status === status).length;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!form.data.documentation) {
            form.setError('documentation', 'Dokumentasi foto kegiatan KBM wajib dilampirkan (bisa foto langsung atau unggah file).');
            return;
        }
        form.post('/guru/journal', {
            forceFormData: true,
        });
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

                {/* Alert Banners (Toleransi 30 Menit / Sedang Berlangsung) */}
                {graceMinutesRemaining !== null && graceMinutesRemaining !== undefined && (
                    <div className="flex items-center gap-3 p-4 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 text-amber-900 dark:text-amber-200">
                        <AlertTriangle className="h-5 w-5 text-amber-600 shrink-0" />
                        <div className="text-xs">
                            <span className="font-bold">Peringatan Masa Toleransi Pengisian Jurnal (30 Menit):</span>
                            {' '}Jam pelajaran telah usai. Anda memiliki sisa waktu <strong>{graceMinutesRemaining} menit</strong> untuk melengkapi pengisian jurnal dan presensi KBM ini sebelum ditandai tidak mengisi jurnal.
                        </div>
                    </div>
                )}
                {isLocked && !graceMinutesRemaining && (
                    <div className="flex items-center gap-3 p-3.5 rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200">
                        <CheckCircle2 className="h-5 w-5 text-emerald-600 shrink-0" />
                        <div className="text-xs">
                            <span className="font-bold">Sesi KBM Sedang Berlangsung:</span>
                            {' '}Jadwal mengajar dan tanggal pelaksanaan dikunci otomatis sesuai sesi KBM aktif Anda saat ini.
                        </div>
                    </div>
                )}

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
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="schedule_id">Pilih Jadwal Mengajar *</Label>
                                    {isLocked && (
                                        <span className="text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                            🔒 Terkunci (Sesi KBM aktif)
                                        </span>
                                    )}
                                </div>
                                <select
                                    id="schedule_id"
                                    value={form.data.schedule_id}
                                    disabled={isLocked}
                                    onChange={(e) => handleScheduleChange(e.target.value)}
                                    className={`flex h-10 w-full rounded-md border border-input px-3 py-2 text-sm shadow-sm ${
                                        isLocked
                                            ? 'bg-muted/70 text-muted-foreground cursor-not-allowed border-dashed'
                                            : 'bg-transparent'
                                    }`}
                                    required
                                >
                                    {schedules.map((s) => (
                                        <option key={s.id} value={s.id.toString()}>
                                            [{s.day_of_week} Putaran {s.cycle_number}] {s.subject} - {s.class_name} ({s.start_time.substring(0, 5)} - {s.end_time.substring(0, 5)})
                                        </option>
                                    ))}
                                </select>
                                {isLocked && <input type="hidden" name="schedule_id" value={form.data.schedule_id} />}
                            </div>

                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="date">Tanggal Pelaksanaan *</Label>
                                    {isLocked && (
                                        <span className="text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                            🔒 Hari Ini
                                        </span>
                                    )}
                                </div>
                                <Input
                                    id="date"
                                    type="date"
                                    disabled={isLocked}
                                    value={form.data.date}
                                    onChange={(e) => form.setData('date', e.target.value)}
                                    className={isLocked ? 'bg-muted/70 text-muted-foreground cursor-not-allowed border-dashed' : ''}
                                    required
                                />
                                {isLocked && <input type="hidden" name="date" value={form.data.date} />}
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

                        {/* Dokumentasi Foto KBM (Wajib) */}
                        <div className="space-y-2.5 pt-3 border-t border-border">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm font-bold text-foreground flex items-center gap-1.5">
                                    <Camera className="h-4 w-4 text-primary" />
                                    <span>Dokumentasi Foto KBM</span>
                                    <span className="text-destructive">*</span>
                                </Label>
                                {photoPreviewUrl && (
                                    <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-300 text-[11px] font-semibold">
                                        Foto Terlampir
                                    </Badge>
                                )}
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Ambil foto kegiatan pembelajaran secara langsung dengan kamera atau unggah berkas foto (JPG, PNG, WEBP, Maks. 10MB).
                            </p>

                            {photoPreviewUrl ? (
                                <div className="space-y-2 max-w-md">
                                    <div className="relative rounded-xl overflow-hidden border border-border bg-slate-950 aspect-video flex items-center justify-center shadow-xs">
                                        <img
                                            src={photoPreviewUrl}
                                            alt="Pratinjau Dokumentasi KBM"
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 pt-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setIsCameraOpen(true)}
                                            className="gap-1.5 text-xs h-8 flex-1"
                                        >
                                            <Camera className="h-3.5 w-3.5" />
                                            Foto Ulang (Kamera)
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => fileInputRef.current?.click()}
                                            className="gap-1.5 text-xs h-8 flex-1"
                                        >
                                            <Upload className="h-3.5 w-3.5" />
                                            Ganti Berkas
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={handleRemovePhoto}
                                            className="gap-1.5 text-xs h-8"
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col sm:flex-row items-center gap-3 p-4 rounded-xl border-2 border-dashed border-border bg-muted/20">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setIsCameraOpen(true)}
                                        className="gap-2 text-xs font-semibold h-10 w-full sm:w-auto"
                                    >
                                        <Camera className="h-4 w-4 text-primary" />
                                        Buka Kamera (Live)
                                    </Button>

                                    <span className="text-xs text-muted-foreground font-medium">atau</span>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="gap-2 text-xs font-semibold h-10 w-full sm:w-auto bg-card"
                                    >
                                        <Upload className="h-4 w-4 text-primary" />
                                        Pilih File Foto
                                    </Button>
                                </div>
                            )}

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={handleFileChange}
                                className="hidden"
                            />

                            {form.errors.documentation && (
                                <p className="text-xs text-destructive font-medium">{form.errors.documentation}</p>
                            )}
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
                                            const leave = getApprovedLeave(student.id, form.data.date);
                                            const isVerifiedLeave = !!leave;
                                            const isRejected = leave?.status === 'rejected';
                                            const currentStatus = isVerifiedLeave
                                                ? (isRejected ? 'alpa' : leave.type)
                                                : (form.data.attendances[idx]?.status || 'hadir');

                                            return (
                                                <TableRow
                                                    key={student.id}
                                                    className={isVerifiedLeave ? "bg-slate-100/70 dark:bg-slate-800/40 text-muted-foreground select-none" : ""}
                                                >
                                                    <TableCell className="text-center font-mono text-xs">
                                                        {idx + 1}
                                                    </TableCell>
                                                    <TableCell className="font-mono text-xs font-semibold">
                                                        {student.nis}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        <span className={isVerifiedLeave ? "text-slate-500 dark:text-slate-400 font-medium" : "text-foreground"}>
                                                            {student.name}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs">
                                                        {student.gender === 'L' ? 'L' : 'P'}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <div className={`inline-flex rounded-lg border border-border p-0.5 text-xs ${isVerifiedLeave ? 'bg-muted/80 opacity-70 cursor-not-allowed select-none' : 'bg-muted'}`}>
                                                            <button
                                                                type="button"
                                                                disabled={isVerifiedLeave}
                                                                onClick={() => !isVerifiedLeave && setStudentStatus(idx, 'hadir')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${
                                                                    currentStatus === 'hadir'
                                                                        ? isVerifiedLeave
                                                                            ? 'bg-slate-300 text-slate-800 dark:bg-slate-700 dark:text-slate-200 shadow-sm'
                                                                            : 'bg-secondary text-secondary-foreground shadow-sm'
                                                                        : 'text-muted-foreground'
                                                                } ${isVerifiedLeave ? 'cursor-not-allowed' : 'hover:text-foreground'}`}
                                                            >
                                                                Hadir (H)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                disabled={isVerifiedLeave}
                                                                onClick={() => !isVerifiedLeave && setStudentStatus(idx, 'sakit')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${
                                                                    currentStatus === 'sakit'
                                                                        ? isVerifiedLeave
                                                                            ? 'bg-slate-300 text-slate-900 dark:bg-slate-700 dark:text-slate-100 font-bold shadow-sm'
                                                                            : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200 shadow-sm'
                                                                        : 'text-muted-foreground'
                                                                } ${isVerifiedLeave ? 'cursor-not-allowed' : 'hover:text-foreground'}`}
                                                            >
                                                                Sakit (S)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                disabled={isVerifiedLeave}
                                                                onClick={() => !isVerifiedLeave && setStudentStatus(idx, 'izin')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${
                                                                    currentStatus === 'izin'
                                                                        ? isVerifiedLeave
                                                                            ? 'bg-slate-300 text-slate-900 dark:bg-slate-700 dark:text-slate-100 font-bold shadow-sm'
                                                                            : 'bg-blue-100 text-blue-900 dark:bg-blue-950 dark:text-blue-200 shadow-sm'
                                                                        : 'text-muted-foreground'
                                                                } ${isVerifiedLeave ? 'cursor-not-allowed' : 'hover:text-foreground'}`}
                                                            >
                                                                Izin (I)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                disabled={isVerifiedLeave}
                                                                onClick={() => !isVerifiedLeave && setStudentStatus(idx, 'alpa')}
                                                                className={`rounded-md px-2.5 py-1 font-semibold transition-colors ${
                                                                    currentStatus === 'alpa'
                                                                        ? isVerifiedLeave
                                                                            ? 'bg-destructive text-destructive-foreground font-bold shadow-sm'
                                                                            : 'bg-destructive text-destructive-foreground shadow-sm'
                                                                        : 'text-muted-foreground'
                                                                } ${isVerifiedLeave ? 'cursor-not-allowed' : 'hover:text-foreground'}`}
                                                            >
                                                                Alpa (A)
                                                            </button>
                                                        </div>
                                                        {isVerifiedLeave && (
                                                            <p className="text-[10px] text-muted-foreground mt-0.5 italic">
                                                                {isRejected
                                                                    ? `*Terkunci: surat izin ditolak wali kelas (${leave.rejection_reason || 'Tidak disetujui'})`
                                                                    : '*Terkunci: surat izin telah disetujui wali kelas'}
                                                            </p>
                                                        )}
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

            <JournalCameraModal
                open={isCameraOpen}
                onOpenChange={setIsCameraOpen}
                title="Foto Dokumentasi KBM"
                classNameInfo={`${selectedSchedule?.class_name || ''} • ${selectedSchedule?.subject || ''}`}
                onCapture={handleCameraCapture}
            />
        </AppLayout>
    );
}

