import React, { useState, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
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
    ClipboardList,
    Send,
    AlertCircle,
    CheckCircle2,
    Clock,
    FileText,
    Camera,
    Upload,
    Trash2,
    ExternalLink,
    Eye,
    Paperclip,
    Loader2,
} from 'lucide-react';
import { SelfieCameraModal } from '@/Components/camera/SelfieCameraModal';
import { formatDateRangeIndo } from '@/lib/utils';
import { LeaveDetailModal, LeaveDetailData } from '@/Components/modals/LeaveDetailModal';
import { validateDocumentFile } from '@/lib/documentDetector';

interface SiswaItem {
    id: number;
    nis: string;
    name: string;
    kelas: string;
}

interface LeaveItem {
    id: number;
    type: 'sakit' | 'izin';
    start_date: string;
    end_date: string;
    reason: string;
    attachment_path?: string | null;
    selfie_path?: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    created_at: string;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface IzinProps {
    student: SiswaItem | null;
    leaves: PaginatedData<LeaveItem>;
    todayDate: string;
}

export default function SiswaIzin({ student, leaves, todayDate }: IzinProps) {
    const [selectedDetailLeave, setSelectedDetailLeave] = useState<LeaveDetailData | null>(null);
    const [isCameraOpen, setIsCameraOpen] = useState(false);
    const [selfiePreviewUrl, setSelfiePreviewUrl] = useState<string | null>(null);
    const [documentPreviewUrl, setDocumentPreviewUrl] = useState<string | null>(null);
    const [documentFileName, setDocumentFileName] = useState<string | null>(null);
    const [documentFileType, setDocumentFileType] = useState<string | null>(null);
    const [isScanningDoc, setIsScanningDoc] = useState(false);
    const [docScanError, setDocScanError] = useState<string | null>(null);
    const documentInputRef = useRef<HTMLInputElement | null>(null);

    const initialStartDate = todayDate || new Date().toISOString().split('T')[0];
    const initialEndDate = initialStartDate;

    const form = useForm<{
        type: string;
        start_date: string;
        end_date: string;
        reason: string;
        selfie: File | null;
        attachment: File | null;
    }>({
        type: 'sakit',
        start_date: initialStartDate,
        end_date: initialEndDate,
        reason: '',
        selfie: null,
        attachment: null,
    });

    const handleCameraCapture = (file: File, url: string) => {
        form.setData('selfie', file);
        form.clearErrors('selfie');
        setSelfiePreviewUrl(url);
    };

    const handleRemoveSelfie = () => {
        form.setData('selfie', null);
        if (selfiePreviewUrl) {
            URL.revokeObjectURL(selfiePreviewUrl);
        }
        setSelfiePreviewUrl(null);
    };

    const handleDocumentFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setDocScanError(null);
        form.clearErrors('attachment');
        setIsScanningDoc(true);

        try {
            const scanResult = await validateDocumentFile(file);
            if (!scanResult.isValidDocument) {
                const errMsg = scanResult.errorMessage || 'Berkas ditolak: Sistem mendeteksi foto wajah/selfie. Mohon unggah dokumen fisik asli (surat dokter/keterangan) atau PDF.';
                setDocScanError(errMsg);
                form.setError('attachment', errMsg);
                if (documentInputRef.current) {
                    documentInputRef.current.value = '';
                }
                return;
            }

            form.setData('attachment', file);
            form.clearErrors('attachment');
            setDocumentFileName(file.name);
            setDocumentFileType(file.type);
            if (file.type.startsWith('image/')) {
                setDocumentPreviewUrl(URL.createObjectURL(file));
            } else {
                setDocumentPreviewUrl(null);
            }
        } catch (err) {
            console.error('Document scan error:', err);
            form.setData('attachment', file);
            form.clearErrors('attachment');
            setDocumentFileName(file.name);
            setDocumentFileType(file.type);
        } finally {
            setIsScanningDoc(false);
        }
    };

    const handleRemoveAttachment = () => {
        form.setData('attachment', null);
        if (documentPreviewUrl) {
            URL.revokeObjectURL(documentPreviewUrl);
        }
        setDocumentPreviewUrl(null);
        setDocumentFileName(null);
        setDocumentFileType(null);
        setDocScanError(null);
        if (documentInputRef.current) {
            documentInputRef.current.value = '';
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        let hasError = false;
        if (!form.data.selfie) {
            form.setError('selfie', 'Foto selfie realtime wajib diambil langsung dengan kamera.');
            hasError = true;
        }
        if (!form.data.attachment) {
            form.setError('attachment', 'Berkas surat keterangan dokter / orang tua wajib diunggah.');
            hasError = true;
        }
        if (hasError) return;

        form.post('/siswa/izin', {
            forceFormData: true,
            onSuccess: () => {
                form.reset('reason');
                handleRemoveSelfie();
                handleRemoveAttachment();
            },
        });
    };

    return (
        <AppLayout title="Pengajuan Izin Siswa">
            <Head title="Pengajuan Izin" />

            <div className="space-y-6">
                {/* Form Pengajuan Izin */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-xl font-bold tracking-tight flex items-center gap-2">
                            <Send className="h-5 w-5" />
                            Formulir Permohonan Izin / Sakit
                        </CardTitle>
                        <p className="text-sm text-muted-foreground mt-0.5">
                            Siswa: <strong className="text-foreground">{student?.name}</strong> (NIS: {student?.nis} &bull; Kelas: {student?.kelas})
                        </p>
                    </CardHeader>

                    <CardContent className="pt-0">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="type">Jenis Permohonan *</Label>
                                    <select
                                        id="type"
                                        value={form.data.type}
                                        onChange={(e) => form.setData('type', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        required
                                    >
                                        <option value="sakit">Sakit (S)</option>
                                        <option value="izin">Izin Keperluan Keluarga (I)</option>
                                    </select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="start_date">Mulai Tanggal *</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            form.setData((prev) => ({
                                                ...prev,
                                                start_date: val,
                                                end_date: prev.end_date && val && prev.end_date < val ? val : (prev.end_date || val),
                                            }));
                                        }}
                                        required
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="end_date">Sampai Tanggal *</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={form.data.end_date}
                                        min={form.data.start_date || undefined}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            const minAllowed = form.data.start_date;
                                            if (minAllowed && val && val < minAllowed) {
                                                form.setData('end_date', minAllowed);
                                            } else {
                                                form.setData('end_date', val);
                                            }
                                        }}
                                        required
                                    />
                                    {form.errors.end_date && (
                                        <p className="text-xs text-destructive">{form.errors.end_date}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="reason">Alasan / Penjelasan Izin *</Label>
                                <Input
                                    id="reason"
                                    value={form.data.reason}
                                    onChange={(e) => form.setData('reason', e.target.value)}
                                    placeholder="Contoh: Sakit demam tinggi, surat dokter terlampir"
                                    required
                                />
                                {form.errors.reason && (
                                    <p className="text-xs text-destructive">{form.errors.reason}</p>
                                )}
                            </div>

                            {/* Section Bukti: Pemisahan Foto Selfie & Surat Keterangan */}
                            <div className="space-y-4 pt-2 border-t border-border">
                                <div className="space-y-1">
                                    <h4 className="text-sm font-bold text-foreground">
                                        Lampiran Bukti Pengajuan (Keduanya Wajib *)
                                    </h4>
                                    <p className="text-xs text-muted-foreground">
                                        Harap ambil foto selfie realtime langsung dengan kamera dan unggah berkas surat keterangan dokter / izin asli.
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* 1. Foto Selfie Realtime */}
                                    <div className="p-4 rounded-xl border border-border bg-card space-y-3">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground flex items-center gap-1.5">
                                                <Camera className="h-4 w-4 text-primary" />
                                                <span>1. Foto Selfie Realtime (Wajib)</span>
                                                <span className="text-destructive">*</span>
                                            </Label>
                                            {selfiePreviewUrl && (
                                                <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-[11px]">
                                                    Terambil
                                                </Badge>
                                            )}
                                        </div>

                                        <p className="text-xs text-muted-foreground">
                                            Wajib foto manual langsung dari kamera. Pengambilan dari galeri dinonaktifkan.
                                        </p>

                                        {selfiePreviewUrl ? (
                                            <div className="space-y-2">
                                                <div className="relative rounded-lg overflow-hidden border border-border bg-slate-950 aspect-video flex items-center justify-center">
                                                    <img
                                                        src={selfiePreviewUrl}
                                                        alt="Selfie Realtime Siswa"
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
                                                        Foto Ulang
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={handleRemoveSelfie}
                                                        className="gap-1.5 text-xs h-8"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex flex-col items-center justify-center border-2 border-dashed border-border rounded-lg p-6 text-center space-y-3 bg-muted/20">
                                                <div className="h-10 w-10 rounded-full bg-secondary flex items-center justify-center text-secondary-foreground">
                                                    <Camera className="h-5 w-5" />
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    onClick={() => setIsCameraOpen(true)}
                                                    className="gap-2 text-xs"
                                                >
                                                    <Camera className="h-4 w-4" />
                                                    Buka Kamera Selfie (Live)
                                                </Button>
                                                <span className="text-[11px] text-muted-foreground">
                                                    Klik untuk mengaktifkan kamera depan & ambil foto
                                                </span>
                                            </div>
                                        )}

                                        {form.errors.selfie && (
                                            <p className="text-xs text-destructive font-medium">{form.errors.selfie}</p>
                                        )}
                                    </div>

                                    {/* 2. Surat Keterangan / Dokter */}
                                    <div className="p-4 rounded-xl border border-border bg-card space-y-3">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground flex items-center gap-1.5">
                                                <FileText className="h-4 w-4 text-primary" />
                                                <span>2. Surat Keterangan / Dokter (Wajib)</span>
                                                <span className="text-destructive">*</span>
                                            </Label>
                                            {form.data.attachment && (
                                                <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-[11px]">
                                                    Terunggah
                                                </Badge>
                                            )}
                                        </div>

                                        <p className="text-xs text-muted-foreground">
                                            Unggah foto surat dokter/keterangan asli atau PDF (Maks. 10MB). Sistem otomatis memverifikasi keaslian dokumen.
                                        </p>

                                        {form.data.attachment ? (
                                            <div className="space-y-2">
                                                {documentPreviewUrl ? (
                                                    <div className="relative rounded-lg overflow-hidden border border-border bg-muted/30 aspect-video flex items-center justify-center">
                                                        <img
                                                            src={documentPreviewUrl}
                                                            alt="Surat Keterangan"
                                                            className="w-full h-full object-cover"
                                                        />
                                                    </div>
                                                ) : (
                                                    <div className="rounded-lg border border-border bg-muted/30 p-4 flex flex-col items-center justify-center gap-2 aspect-video text-center">
                                                        <FileText className="h-10 w-10 text-rose-500" />
                                                        <span className="text-xs font-semibold text-foreground">Dokumen PDF Terunggah</span>
                                                        <span className="text-[11px] text-muted-foreground font-mono truncate max-w-[200px]">
                                                            {documentFileName}
                                                        </span>
                                                    </div>
                                                )}
                                                <div className="flex items-center justify-between text-xs text-muted-foreground px-1">
                                                    <span className="truncate max-w-[180px] font-mono">{documentFileName}</span>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={handleRemoveAttachment}
                                                        className="text-xs h-7 text-destructive hover:bg-destructive/10 gap-1"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                        Ganti Berkas
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex flex-col items-center justify-center border-2 border-dashed border-border rounded-lg p-6 text-center space-y-3 bg-muted/20">
                                                {isScanningDoc ? (
                                                    <div className="py-4 space-y-2 flex flex-col items-center">
                                                        <Loader2 className="h-6 w-6 animate-spin text-primary" />
                                                        <p className="text-xs text-muted-foreground">
                                                            Memverifikasi jenis dokumen...
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <>
                                                        <div className="h-10 w-10 rounded-full bg-secondary flex items-center justify-center text-secondary-foreground">
                                                            <Upload className="h-5 w-5" />
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="secondary"
                                                            onClick={() => documentInputRef.current?.click()}
                                                            className="gap-2 text-xs"
                                                        >
                                                            <Upload className="h-4 w-4" />
                                                            Pilih Berkas Surat
                                                        </Button>
                                                        <input
                                                            ref={documentInputRef}
                                                            type="file"
                                                            accept="image/jpeg,image/png,image/webp,application/pdf"
                                                            onChange={handleDocumentFileChange}
                                                            className="hidden"
                                                        />
                                                        <span className="text-[11px] text-muted-foreground">
                                                            Format: JPG, PNG, WEBP, atau PDF
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        )}

                                        {(docScanError || form.errors.attachment) && (
                                            <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-2.5 text-xs text-destructive flex items-start gap-1.5">
                                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                                <span>{docScanError || form.errors.attachment}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end pt-3">
                                <Button type="submit" disabled={form.processing} className="gap-2">
                                    <Send className="h-4 w-4" />
                                    {form.processing ? 'Mengirim...' : 'Kirim Permohonan Izin'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Riwayat Pengajuan Izin */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-lg font-bold tracking-tight">
                            Riwayat Permohonan Izin Anda
                        </CardTitle>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Status verifikasi dari Wali Kelas atau Guru BK sekolah.
                        </p>
                    </CardHeader>

                    <CardContent className="pt-0">
                        {leaves.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Belum ada riwayat permohonan izin yang pernah diajukan.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12 text-center">No</TableHead>
                                            <TableHead>Jenis Izin</TableHead>
                                            <TableHead>Rentang Tanggal</TableHead>
                                            <TableHead>Alasan / Keterangan</TableHead>
                                            <TableHead className="text-center">Status Verifikasi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {leaves.data.map((leave, idx) => (
                                            <TableRow key={leave.id}>
                                                <TableCell className="text-center font-mono text-xs">
                                                    {(leaves.current_page - 1) * 10 + idx + 1}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={leave.type === 'sakit' ? 'secondary' : 'outline'} className="capitalize">
                                                        {leave.type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs whitespace-nowrap font-medium">
                                                    {formatDateRangeIndo(leave.start_date, leave.end_date)}
                                                </TableCell>
                                                <TableCell>
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelectedDetailLeave({ ...leave, student })}
                                                        className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 transition-colors whitespace-nowrap"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        <span>Lihat Detail</span>
                                                    </button>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {leave.status === 'pending' && (
                                                        <Badge variant="outline" className="text-amber-600 border-amber-300 bg-amber-50">
                                                            <Clock className="mr-1 h-3 w-3" /> Menunggu
                                                        </Badge>
                                                    )}
                                                    {leave.status === 'approved' && (
                                                        <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50">
                                                            <CheckCircle2 className="mr-1 h-3 w-3" /> Disetujui
                                                        </Badge>
                                                    )}
                                                    {leave.status === 'rejected' && (
                                                        <Badge variant="destructive">
                                                            <AlertCircle className="mr-1 h-3 w-3" /> Ditolak
                                                        </Badge>
                                                    )}
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

            {/* Selfie Camera Modal */}
            <SelfieCameraModal
                open={isCameraOpen}
                onOpenChange={setIsCameraOpen}
                studentName={student?.name || 'Siswa'}
                studentClass={student?.kelas || ''}
                onCapture={handleCameraCapture}
            />

            {/* Detail Alasan Izin Modal */}
            <LeaveDetailModal
                isOpen={!!selectedDetailLeave}
                onClose={() => setSelectedDetailLeave(null)}
                leave={selectedDetailLeave}
            />
        </AppLayout>
    );
}
