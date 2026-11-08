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
    ExternalLink
} from 'lucide-react';
import { SelfieCameraModal } from '@/Components/camera/SelfieCameraModal';

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
    const [isCameraOpen, setIsCameraOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    const form = useForm<{
        type: string;
        start_date: string;
        end_date: string;
        reason: string;
        attachment: File | null;
    }>({
        type: 'sakit',
        start_date: todayDate,
        end_date: todayDate,
        reason: '',
        attachment: null,
    });

    const handleCameraCapture = (file: File, url: string) => {
        form.setData('attachment', file);
        form.clearErrors('attachment');
        setPreviewUrl(url);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('attachment', file);
            form.clearErrors('attachment');
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleRemoveAttachment = () => {
        form.setData('attachment', null);
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!form.data.attachment) {
            form.setError('attachment', 'Bukti foto atau surat keterangan wajib dilampirkan.');
            return;
        }
        form.post('/siswa/izin', {
            forceFormData: true,
            onSuccess: () => {
                form.reset('reason');
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
                                        onChange={(e) => form.setData('start_date', e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="end_date">Sampai Tanggal *</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={form.data.end_date}
                                        onChange={(e) => form.setData('end_date', e.target.value)}
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

                            {/* Section Upload Foto / Bukti Selfie Live */}
                            <div className="space-y-2 pt-1">
                                <Label>
                                    Bukti Foto / Surat Keterangan (Wajib) <span className="text-destructive">*</span>
                                </Label>

                                {previewUrl ? (
                                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-3 rounded-lg border border-border bg-muted/40">
                                        <img
                                            src={previewUrl}
                                            alt="Pratinjau Bukti"
                                            className="h-28 w-36 object-cover rounded-md border border-border shadow-xs"
                                        />
                                        <div className="space-y-1.5">
                                            <div className="flex items-center gap-2">
                                                <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-[11px]">
                                                    Foto Bukti Terlampir
                                                </Badge>
                                                <span className="text-xs text-muted-foreground font-mono">
                                                    {form.data.attachment?.name}
                                                </span>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Foto ini akan diverifikasi oleh Wali Kelas dan Guru BK.
                                            </p>
                                            <div className="flex items-center gap-2 pt-1">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setIsCameraOpen(true)}
                                                    className="gap-1.5 text-xs h-8"
                                                >
                                                    <Camera className="h-3.5 w-3.5" />
                                                    Ambil Ulang
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={handleRemoveAttachment}
                                                    className="gap-1.5 text-xs h-8"
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setIsCameraOpen(true)}
                                            className="gap-2 text-xs"
                                        >
                                            <Camera className="h-4 w-4 text-primary" />
                                            Buka Kamera Selfie (Live)
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => fileInputRef.current?.click()}
                                            className="gap-2 text-xs"
                                        >
                                            <Upload className="h-4 w-4" />
                                            Pilih dari Galeri / File
                                        </Button>

                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp,application/pdf"
                                            onChange={handleFileChange}
                                            className="hidden"
                                        />

                                        <span className="text-xs text-muted-foreground">
                                            (Format: JPG, PNG, WEBP, atau PDF. Maks. 10MB)
                                        </span>
                                    </div>
                                )}

                                {form.errors.attachment && (
                                    <p className="text-xs text-destructive">{form.errors.attachment}</p>
                                )}
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
                                            <TableHead>Bukti Foto</TableHead>
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
                                                <TableCell className="text-xs font-mono">
                                                    {leave.start_date} s.d. {leave.end_date}
                                                </TableCell>
                                                <TableCell className="text-xs max-w-xs truncate">
                                                    {leave.reason}
                                                    {leave.rejection_reason && (
                                                        <span className="block text-[11px] text-destructive mt-0.5">
                                                            Catatan: {leave.rejection_reason}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {leave.attachment_path ? (
                                                        <a
                                                            href={`/storage/${leave.attachment_path}`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="inline-flex items-center gap-1 text-xs text-primary hover:underline font-medium"
                                                        >
                                                            <ExternalLink className="h-3 w-3" />
                                                            <span>Lihat Foto</span>
                                                        </a>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">-</span>
                                                    )}
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
        </AppLayout>
    );
}
