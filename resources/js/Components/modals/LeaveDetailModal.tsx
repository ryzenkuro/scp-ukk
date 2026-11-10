import React, { useState, useEffect } from 'react';
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
    User,
    FileText,
    ExternalLink,
    AlertCircle,
    CheckCircle2,
    XCircle,
    Clock,
    Paperclip,
    Camera,
    Eye,
} from 'lucide-react';
import { formatDateRangeIndo } from '@/lib/utils';

export interface LeaveDetailData {
    id?: number;
    type: string;
    start_date: string;
    end_date: string;
    reason: string;
    attachment_path?: string | null;
    selfie_path?: string | null;
    status?: string;
    rejection_reason?: string | null;
    created_at?: string;
    student?: {
        name?: string;
        nis?: string;
        kelas?: string;
    } | null;
    verifier?: {
        name?: string;
        fullname?: string;
        username?: string;
    } | null;
    verified_at?: string | null;
}

interface LeaveDetailModalProps {
    isOpen: boolean;
    onClose: () => void;
    leave: LeaveDetailData | null;
}

export function LeaveDetailModal({ isOpen, onClose, leave }: LeaveDetailModalProps) {
    const [previewDoc, setPreviewDoc] = useState<{
        type: 'selfie' | 'attachment';
        title: string;
        url: string;
        isPdf: boolean;
    } | null>(null);
    const [imageLoadError, setImageLoadError] = useState(false);

    useEffect(() => {
        setPreviewDoc(null);
        setImageLoadError(false);
    }, [leave?.id, isOpen]);

    if (!leave) return null;

    const handleOpenSelfie = () => {
        if (!leave.selfie_path) return;
        setImageLoadError(false);
        setPreviewDoc({
            type: 'selfie',
            title: 'Foto Realtime Siswa',
            url: `/storage/${leave.selfie_path}`,
            isPdf: false,
        });
    };

    const handleOpenAttachment = () => {
        if (!leave.attachment_path) return;
        const isPdf = leave.attachment_path.toLowerCase().endsWith('.pdf');
        setImageLoadError(false);
        setPreviewDoc({
            type: 'attachment',
            title: 'Surat Keterangan / Dokter',
            url: `/storage/${leave.attachment_path}`,
            isPdf,
        });
    };

    const getStatusBadge = (status?: string) => {
        if (!status) return null;
        switch (status) {
            case 'approved':
                return (
                    <Badge className="bg-emerald-600 hover:bg-emerald-700 text-white gap-1 text-xs">
                        <CheckCircle2 className="h-3 w-3" /> Disetujui
                    </Badge>
                );
            case 'rejected':
                return (
                    <Badge variant="destructive" className="gap-1 text-xs">
                        <XCircle className="h-3 w-3" /> Ditolak
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline" className="border-amber-500/50 bg-amber-500/10 text-amber-600 dark:text-amber-400 gap-1 text-xs">
                        <Clock className="h-3 w-3" /> Menunggu
                    </Badge>
                );
        }
    };

    return (
        <>
            {/* Modal Card 1: Form Detail Pengajuan Izin (Ramping & Bebas Kotak Pratinjau di Form) */}
            <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
                <DialogContent className="sm:max-w-md md:max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <DialogHeader className="space-y-1">
                        <div className="flex items-center justify-between pr-6">
                            <DialogTitle className="text-lg font-bold flex items-center gap-2">
                                <FileText className="h-5 w-5 text-primary" />
                                Detail Pengajuan Izin
                            </DialogTitle>
                        </div>
                        <DialogDescription className="text-xs text-muted-foreground">
                            Keterangan lengkap alasan dan informasi permohonan izin siswa.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 pt-2 text-sm">
                        {/* Header Badges */}
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge
                                variant={leave.type === 'sakit' ? 'secondary' : 'outline'}
                                className="capitalize text-xs font-semibold"
                            >
                                {leave.type}
                            </Badge>
                            {getStatusBadge(leave.status)}
                        </div>

                        {/* Informasi Siswa (Jika Ada) */}
                        {leave.student && (
                            <div className="rounded-lg border border-border/80 bg-muted/30 p-3 space-y-1 text-xs">
                                <div className="flex items-center gap-1.5 font-semibold text-foreground text-sm">
                                    <User className="h-4 w-4 text-muted-foreground" />
                                    {leave.student.name}
                                </div>
                                <div className="text-muted-foreground pl-5.5">
                                    {leave.student.nis && <span>NIS: {leave.student.nis}</span>}
                                    {leave.student.nis && leave.student.kelas && <span> &bull; </span>}
                                    {leave.student.kelas && <span>Kelas: {leave.student.kelas}</span>}
                                </div>
                            </div>
                        )}

                        {/* Rentang Tanggal */}
                        <div className="flex items-center gap-2 text-xs text-muted-foreground bg-muted/20 px-3 py-2 rounded-lg border border-border/50">
                            <Calendar className="h-4 w-4 text-primary shrink-0" />
                            <div>
                                <span className="font-medium text-foreground">Rentang Tanggal: </span>
                                <span className="font-semibold text-foreground">
                                    {formatDateRangeIndo(leave.start_date, leave.end_date)}
                                </span>
                            </div>
                        </div>

                        {/* Alasan / Penjelasan Lengkap */}
                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-foreground uppercase tracking-wider block">
                                Alasan / Penjelasan
                            </label>
                            <div className="rounded-xl border border-border bg-muted/40 p-4 text-xs md:text-sm text-foreground leading-relaxed whitespace-pre-wrap break-words max-h-52 overflow-y-auto shadow-inner">
                                {leave.reason || <span className="italic text-muted-foreground">Tidak ada alasan dituliskan.</span>}
                            </div>
                        </div>

                        {/* Catatan Penolakan (Jika ada) */}
                        {leave.rejection_reason && (
                            <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-3.5 space-y-1 text-xs text-destructive">
                                <div className="flex items-center gap-1.5 font-semibold">
                                    <AlertCircle className="h-4 w-4 shrink-0" />
                                    Catatan Penolakan:
                                </div>
                                <p className="leading-relaxed pl-5.5 whitespace-pre-wrap break-words">
                                    {leave.rejection_reason}
                                </p>
                                {leave.verifier && (
                                    <p className="text-[11px] text-muted-foreground pl-5.5 pt-1">
                                        Diverifikasi oleh: {leave.verifier.fullname || leave.verifier.name || leave.verifier.username}
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Berkas & Bukti Lampiran (Punya Card Sendiri Saat Diklik) */}
                        <div className="space-y-2.5 pt-2 border-t border-border">
                            <label className="text-xs font-semibold text-foreground uppercase tracking-wider block">
                                Berkas & Bukti Lampiran
                            </label>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                {/* Tombol 1: Buka Foto Realtime Siswa */}
                                {leave.selfie_path ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleOpenSelfie}
                                        className="w-full justify-between h-10 px-3 text-xs font-medium border-primary/20 hover:bg-primary/5 hover:text-primary cursor-pointer transition-colors"
                                    >
                                        <div className="flex items-center gap-2 truncate">
                                            <Camera className="h-4 w-4 text-primary shrink-0" />
                                            <span className="truncate">Buka Foto Realtime</span>
                                        </div>
                                        <Eye className="h-3.5 w-3.5 shrink-0 opacity-70" />
                                    </Button>
                                ) : (
                                    <div className="flex items-center gap-2 px-3 h-10 rounded-md border border-dashed border-border bg-muted/20 text-xs text-muted-foreground select-none">
                                        <Camera className="h-4 w-4 text-muted-foreground/60 shrink-0" />
                                        <span className="truncate italic">Foto realtime tidak dilampirkan</span>
                                    </div>
                                )}

                                {/* Tombol 2: Buka Berkas Surat Dokter / Izin */}
                                {leave.attachment_path ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleOpenAttachment}
                                        className="w-full justify-between h-10 px-3 text-xs font-medium border-primary/20 hover:bg-primary/5 hover:text-primary cursor-pointer transition-colors"
                                    >
                                        <div className="flex items-center gap-2 truncate">
                                            <Paperclip className="h-4 w-4 text-primary shrink-0" />
                                            <span className="truncate">Buka Berkas Surat</span>
                                        </div>
                                        <Eye className="h-3.5 w-3.5 shrink-0 opacity-70" />
                                    </Button>
                                ) : (
                                    <div className="flex items-center gap-2 px-3 h-10 rounded-md border border-dashed border-border bg-muted/20 text-xs text-muted-foreground select-none">
                                        <Paperclip className="h-4 w-4 text-muted-foreground/60 shrink-0" />
                                        <span className="truncate italic">Surat tidak dilampirkan</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="pt-3 border-t border-border mt-2">
                        <Button variant="secondary" onClick={onClose} className="w-full sm:w-auto text-xs">
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Card 2: Card Khusus Pratinjau Berkas (Tersendiri di Tengah Layar dengan Background Blur) */}
            <Dialog open={!!previewDoc} onOpenChange={(open) => !open && setPreviewDoc(null)}>
                <DialogContent className="sm:max-w-xl md:max-w-2xl lg:max-w-3xl p-6 border border-border bg-card shadow-2xl">
                    <DialogHeader className="space-y-1 pb-3 border-b border-border">
                        <div className="flex items-center justify-between pr-6">
                            <DialogTitle className="text-base font-bold flex items-center gap-2">
                                {previewDoc?.type === 'selfie' ? (
                                    <Camera className="h-5 w-5 text-primary" />
                                ) : (
                                    <Paperclip className="h-5 w-5 text-primary" />
                                )}
                                <span>{previewDoc?.title}</span>
                            </DialogTitle>
                        </div>
                        <DialogDescription className="text-xs text-muted-foreground">
                            Pratinjau berkas lampiran pengajuan izin &bull; {leave.student?.name || 'Siswa'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-2">
                        {previewDoc?.isPdf ? (
                            <div className="relative rounded-lg overflow-hidden border border-border bg-white min-h-[400px]">
                                <iframe
                                    src={previewDoc.url}
                                    title={previewDoc.title}
                                    className="w-full h-[500px] border-0"
                                />
                            </div>
                        ) : imageLoadError ? (
                            <div className="p-8 text-center bg-muted/20 rounded-lg border border-border space-y-2">
                                <AlertCircle className="h-8 w-8 text-amber-500 mx-auto" />
                                <p className="text-xs font-semibold text-foreground">
                                    Berkas Pratinjau Belum Tersedia di Penyimpanan Lokal
                                </p>
                                <p className="text-[11px] text-muted-foreground font-mono truncate max-w-sm mx-auto">
                                    {previewDoc?.url}
                                </p>
                                <a
                                    href={previewDoc?.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 text-xs text-primary underline pt-1 font-medium"
                                >
                                    Coba buka tautan langsung <ExternalLink className="h-3 w-3" />
                                </a>
                            </div>
                        ) : (
                            <div className="relative rounded-lg overflow-hidden border border-border bg-slate-950 flex items-center justify-center p-2 min-h-[250px] max-h-[70vh]">
                                <img
                                    src={previewDoc?.url}
                                    alt={previewDoc?.title}
                                    onError={() => setImageLoadError(true)}
                                    className="max-h-[65vh] w-auto max-w-full object-contain rounded shadow"
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter className="pt-3 border-t border-border mt-2 flex flex-row items-center justify-between sm:justify-between">
                        <a
                            href={previewDoc?.url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium py-1 px-2 rounded-md hover:bg-muted transition-colors"
                        >
                            <ExternalLink className="h-3.5 w-3.5" />
                            <span>Buka di Tab Baru</span>
                        </a>
                        <Button
                            variant="secondary"
                            onClick={() => setPreviewDoc(null)}
                            className="text-xs cursor-pointer"
                        >
                            Tutup Pratinjau
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
