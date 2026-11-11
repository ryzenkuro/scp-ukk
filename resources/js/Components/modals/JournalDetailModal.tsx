import React, { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Calendar,
    BookOpen,
    Clock,
    MapPin,
    Users,
    FileText,
    Camera,
    ExternalLink,
    AlertCircle,
    UserCheck,
    Edit2,
    X,
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import { formatDateIndo } from '@/lib/utils';

export interface AttendanceDetailItem {
    id: number;
    student_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes?: string | null;
    student?: {
        id: number;
        nis: string;
        name: string;
        gender?: 'L' | 'P';
        kelas?: string;
    };
}

export interface JournalDetailData {
    id: number;
    date: string;
    topic: string;
    notes: string | null;
    can_edit?: boolean;
    documentation_path?: string | null;
    documentation_url?: string | null;
    schedule?: {
        id?: number;
        subject: string;
        class_name: string;
        day_of_week?: string;
        start_time?: string;
        end_time?: string;
        room_name?: string | null;
        room?: {
            name: string;
        } | null;
        schoolClass?: {
            name: string;
        } | null;
    } | null;
    attendances?: AttendanceDetailItem[];
}

interface JournalDetailModalProps {
    isOpen: boolean;
    onClose: () => void;
    journal: JournalDetailData | null;
    canEdit?: boolean;
}

export function JournalDetailModal({ isOpen, onClose, journal, canEdit = true }: JournalDetailModalProps) {
    const [isImageZoomed, setIsImageZoomed] = useState(false);

    if (!journal) return null;

    const attendances = journal.attendances || [];
    const hadir = attendances.filter((a) => a.status === 'hadir').length;
    const sakit = attendances.filter((a) => a.status === 'sakit').length;
    const izin = attendances.filter((a) => a.status === 'izin').length;
    const alpa = attendances.filter((a) => a.status === 'alpa').length;
    const total = attendances.length;

    const absentStudents = attendances.filter((a) => a.status !== 'hadir');
    const photoUrl = journal.documentation_url || (journal.documentation_path ? `/storage/${journal.documentation_path}` : null);

    return (
        <>
            <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto p-0 gap-0">
                    {/* Header */}
                    <DialogHeader className="p-5 pb-4 border-b border-border bg-muted/20">
                        <div className="flex items-start justify-between gap-4">
                            <div className="space-y-1">
                                <DialogTitle className="text-lg font-bold tracking-tight flex items-center gap-2">
                                    <BookOpen className="h-5 w-5 text-primary" />
                                    Detail Jurnal & Presensi KBM
                                </DialogTitle>
                                <DialogDescription className="text-xs text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 pt-1">
                                    <span className="inline-flex items-center gap-1 font-medium text-foreground">
                                        <Calendar className="h-3.5 w-3.5 text-muted-foreground" />
                                        {formatDateIndo(journal.date)}
                                    </span>
                                    <span>&bull;</span>
                                    <span className="font-semibold text-foreground">
                                        {journal.schedule?.subject || 'Mata Pelajaran'}
                                    </span>
                                    <span>&bull;</span>
                                    <Badge variant="secondary" className="text-[11px] font-semibold">
                                        {journal.schedule?.class_name || '-'}
                                    </Badge>
                                    {journal.schedule?.room?.name && (
                                        <>
                                            <span>&bull;</span>
                                            <span className="inline-flex items-center gap-1">
                                                <MapPin className="h-3 w-3 text-muted-foreground" />
                                                {journal.schedule.room.name}
                                            </span>
                                        </>
                                    )}
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>

                    {/* Content Body */}
                    <div className="p-5 space-y-5">
                        {/* 1. Foto Dokumentasi KBM */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <Camera className="h-3.5 w-3.5 text-primary" />
                                    Dokumentasi Kegiatan Pembelajaran
                                </h4>
                                {photoUrl && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setIsImageZoomed(true)}
                                        className="h-7 text-xs text-primary gap-1"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                        Perbesar Foto
                                    </Button>
                                )}
                            </div>

                            {photoUrl ? (
                                <div
                                    onClick={() => setIsImageZoomed(true)}
                                    className="relative rounded-xl overflow-hidden border border-border bg-slate-950 aspect-video flex items-center justify-center cursor-pointer group shadow-xs hover:border-primary/50 transition-colors"
                                >
                                    <img
                                        src={photoUrl}
                                        alt={`Dokumentasi ${journal.topic}`}
                                        className="w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-200"
                                    />
                                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-medium gap-1.5">
                                        <ExternalLink className="h-4 w-4" />
                                        Klik untuk melihat ukuran penuh
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted-foreground bg-muted/10">
                                    Foto dokumentasi belum diunggah untuk sesi pembelajaran ini.
                                </div>
                            )}
                        </div>

                        {/* 2. Topik & Catatan Pembelajaran */}
                        <div className="grid grid-cols-1 gap-3">
                            <div className="p-3.5 rounded-xl border border-border bg-card space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block">
                                    Topik / Materi Pembelajaran
                                </span>
                                <p className="text-sm font-semibold text-foreground">
                                    {journal.topic}
                                </p>
                            </div>

                            {journal.notes && (
                                <div className="p-3.5 rounded-xl border border-border bg-muted/30 space-y-1">
                                    <span className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block">
                                        Catatan KBM / Hambatan Kelas
                                    </span>
                                    <p className="text-xs text-foreground/90 whitespace-pre-line">
                                        {journal.notes}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* 3. Rekapitulasi Presensi */}
                        <div className="space-y-3">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Users className="h-3.5 w-3.5 text-primary" />
                                Rekapitulasi Kehadiran Siswa
                            </h4>

                            {/* Stat Badges */}
                            <div className="grid grid-cols-5 gap-2 text-center">
                                <div className="p-2.5 rounded-lg border border-border bg-card">
                                    <span className="text-[10px] text-muted-foreground block font-medium">Hadir</span>
                                    <span className="text-base font-bold text-emerald-600 dark:text-emerald-400">{hadir}</span>
                                </div>
                                <div className="p-2.5 rounded-lg border border-border bg-card">
                                    <span className="text-[10px] text-muted-foreground block font-medium">Sakit</span>
                                    <span className="text-base font-bold text-amber-600 dark:text-amber-400">{sakit}</span>
                                </div>
                                <div className="p-2.5 rounded-lg border border-border bg-card">
                                    <span className="text-[10px] text-muted-foreground block font-medium">Izin</span>
                                    <span className="text-base font-bold text-blue-600 dark:text-blue-400">{izin}</span>
                                </div>
                                <div className="p-2.5 rounded-lg border border-border bg-card">
                                    <span className="text-[10px] text-muted-foreground block font-medium">Alpa</span>
                                    <span className={`text-base font-bold ${alpa > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground'}`}>{alpa}</span>
                                </div>
                                <div className="p-2.5 rounded-lg border border-border bg-card">
                                    <span className="text-[10px] text-muted-foreground block font-medium">Total</span>
                                    <span className="text-base font-bold text-foreground">{total}</span>
                                </div>
                            </div>

                            {/* Daftar Siswa Tidak Hadir */}
                            {absentStudents.length > 0 ? (
                                <div className="rounded-xl border border-border overflow-hidden">
                                    <div className="bg-muted/40 px-3 py-2 border-b border-border text-[11px] font-semibold text-muted-foreground">
                                        Rincian Siswa Tidak Hadir ({absentStudents.length} Siswa)
                                    </div>
                                    <div className="divide-y divide-border max-h-48 overflow-y-auto">
                                        {absentStudents.map((att) => {
                                            const statusLabel =
                                                att.status === 'sakit'
                                                    ? 'Sakit (S)'
                                                    : att.status === 'izin'
                                                    ? 'Izin (I)'
                                                    : 'Alpa (A)';
                                            const statusClass =
                                                att.status === 'sakit'
                                                    ? 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-200'
                                                    : att.status === 'izin'
                                                    ? 'bg-blue-100 text-blue-900 border-blue-300 dark:bg-blue-950 dark:text-blue-200'
                                                    : 'bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-200';

                                            return (
                                                <div key={att.id} className="p-2.5 px-3 flex items-center justify-between gap-3 text-xs">
                                                    <div className="min-w-0">
                                                        <span className="font-semibold text-foreground block truncate">
                                                            {att.student?.name || `Siswa #${att.student_id}`}
                                                        </span>
                                                        <span className="text-[11px] text-muted-foreground font-mono">
                                                            NIS: {att.student?.nis || '-'}
                                                            {att.notes && ` • Catatan: ${att.notes}`}
                                                        </span>
                                                    </div>
                                                    <span className={`px-2 py-0.5 rounded-full text-[11px] font-bold border shrink-0 ${statusClass}`}>
                                                        {statusLabel}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ) : (
                                <div className="p-3 rounded-xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50 dark:bg-emerald-950/20 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                    <UserCheck className="h-4 w-4 text-emerald-600 shrink-0" />
                                    <span>Seluruh siswa hadir lengkap pada sesi pembelajaran ini ({hadir} siswa).</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Footer */}
                    <DialogFooter className="p-4 border-t border-border bg-muted/20 flex sm:items-center sm:justify-between gap-2">
                        {canEdit && journal.can_edit !== false ? (
                            <Link href={`/guru/journal/${journal.id}/edit`}>
                                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                                    <Edit2 className="h-3.5 w-3.5" />
                                    Edit Jurnal
                                </Button>
                            </Link>
                        ) : (
                            <div />
                        )}
                        <Button type="button" variant="secondary" size="sm" onClick={onClose} className="text-xs">
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Lightbox Dialog for Zoomed Image */}
            {photoUrl && (
                <Dialog open={isImageZoomed} onOpenChange={setIsImageZoomed}>
                    <DialogContent className="max-w-4xl p-2 bg-slate-950 border-slate-800">
                        <div className="relative flex flex-col items-center">
                            <img
                                src={photoUrl}
                                alt={`Dokumentasi ${journal.topic}`}
                                className="max-h-[85vh] w-auto object-contain rounded-lg"
                            />
                            <div className="pt-2 text-center text-xs text-slate-400">
                                {journal.schedule?.subject} &bull; {journal.schedule?.class_name} ({formatDateIndo(journal.date)})
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}
