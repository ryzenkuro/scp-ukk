import React, { useState, useRef } from 'react';
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
    Users,
    Camera,
    Upload,
    Trash2,
} from 'lucide-react';
import { formatDateIndo } from '@/lib/utils';
import { JournalCameraModal } from '@/Components/camera/JournalCameraModal';

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
    documentation_path?: string | null;
    documentation_url?: string | null;
    schedule?: {
        subject: string;
        class_name: string;
        room_name: string | null;
        start_time: string;
        end_time: string;
    };
    attendances?: AttendanceItem[];
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

interface EditProps {
    journal: JournalData;
    approvedLeaves?: ApprovedLeaveItem[];
}

export default function JournalEdit({ journal, approvedLeaves = [] }: EditProps) {
    const getApprovedLeave = (studentId: number, dateStr: string) => {
        return approvedLeaves?.find(
            (l) => l.student_id === studentId && dateStr >= l.start_date && dateStr <= (l.end_date || l.start_date)
        );
    };

    const [isCameraOpen, setIsCameraOpen] = useState(false);
    const initialPhotoUrl = journal.documentation_url || (journal.documentation_path ? `/storage/${journal.documentation_path}` : null);
    const [photoPreviewUrl, setPhotoPreviewUrl] = useState<string | null>(initialPhotoUrl);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    const initialAttendances = (journal.attendances || []).map((att) => {
        const leave = getApprovedLeave(att.student_id, journal.date);
        let status = att.status;
        let notes = att.notes || '';
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
            student_id: att.student_id,
            status,
            notes,
            student: att.student,
        };
    });

    const form = useForm<{
        _method: string;
        date: string;
        topic: string;
        notes: string;
        documentation: File | null;
        attendances: typeof initialAttendances;
    }>({
        _method: 'put',
        date: journal.date,
        topic: journal.topic,
        notes: journal.notes || '',
        documentation: null,
        attendances: initialAttendances,
    });

    const handleCameraCapture = (file: File, previewUrl: string) => {
        form.setData('documentation', file);
        form.clearErrors('documentation');
        if (photoPreviewUrl && photoPreviewUrl !== initialPhotoUrl) {
            URL.revokeObjectURL(photoPreviewUrl);
        }
        setPhotoPreviewUrl(previewUrl);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        form.setData('documentation', file);
        form.clearErrors('documentation');
        if (photoPreviewUrl && photoPreviewUrl !== initialPhotoUrl) {
            URL.revokeObjectURL(photoPreviewUrl);
        }
        setPhotoPreviewUrl(URL.createObjectURL(file));
    };

    const handleRemovePhoto = () => {
        form.setData('documentation', null);
        if (photoPreviewUrl && photoPreviewUrl !== initialPhotoUrl) {
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

    const countStatus = (status: string) => {
        return form.data.attendances.filter((a) => a.status === status).length;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/guru/journal/${journal.id}`, {
            forceFormData: true,
        });
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
                                Kelas {journal.schedule?.class_name} &bull; Sesi {formatDateIndo(journal.date)}
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

                        {/* Dokumentasi Foto KBM */}
                        <div className="space-y-2.5 pt-3 border-t border-border">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm font-bold text-foreground flex items-center gap-1.5">
                                    <Camera className="h-4 w-4 text-primary" />
                                    <span>Dokumentasi Foto KBM</span>
                                </Label>
                                {photoPreviewUrl && (
                                    <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-300 text-[11px] font-semibold">
                                        Foto Terlampir
                                    </Badge>
                                )}
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Foto dokumentasi kegiatan pembelajaran di kelas. Anda dapat mengambil foto baru dengan kamera atau mengunggah berkas gambar (JPG, PNG, WEBP).
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
                                        const leave = getApprovedLeave(item.student_id, form.data.date);
                                        const isVerifiedLeave = !!leave;
                                        const isRejected = leave?.status === 'rejected';
                                        const currentStatus = isVerifiedLeave
                                            ? (isRejected ? 'alpa' : leave.type)
                                            : item.status;

                                        return (
                                            <TableRow
                                                key={item.student_id}
                                                className={isVerifiedLeave ? "bg-slate-100/70 dark:bg-slate-800/40 text-muted-foreground select-none" : ""}
                                            >
                                                <TableCell className="text-center font-mono text-xs">
                                                    {idx + 1}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs font-semibold">
                                                    {item.student?.nis || '-'}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    <span className={isVerifiedLeave ? "text-slate-500 dark:text-slate-400 font-medium" : "text-foreground"}>
                                                        {item.student?.name || `Siswa #${item.student_id}`}
                                                    </span>
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
                    </CardContent>
                </Card>
            </form>

            <JournalCameraModal
                open={isCameraOpen}
                onOpenChange={setIsCameraOpen}
                title="Foto Dokumentasi KBM"
                classNameInfo={`${journal.schedule?.class_name || ''} • ${journal.schedule?.subject || ''}`}
                onCapture={handleCameraCapture}
            />
        </AppLayout>
    );
}

