import React, { useRef, useState, useEffect, useCallback } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Camera, RefreshCw, AlertCircle, SwitchCamera, X } from 'lucide-react';

interface JournalCameraModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    classNameInfo?: string;
    onCapture: (file: File, previewUrl: string) => void;
}

export function JournalCameraModal({
    open,
    onOpenChange,
    title = 'Ambil Foto Dokumentasi KBM',
    classNameInfo = '',
    onCapture,
}: JournalCameraModalProps) {
    const videoRef = useRef<HTMLVideoElement | null>(null);
    const canvasRef = useRef<HTMLCanvasElement | null>(null);

    const [stream, setStream] = useState<MediaStream | null>(null);
    const [cameraError, setCameraError] = useState<string | null>(null);
    const [facingMode, setFacingMode] = useState<'user' | 'environment'>('environment');
    const [isCapturing, setIsCapturing] = useState(false);

    const stopStream = useCallback(() => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            setStream(null);
        }
        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }
    }, [stream]);

    const startCamera = useCallback(async (mode: 'user' | 'environment') => {
        setCameraError(null);
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            setStream(null);
        }

        try {
            const constraints: MediaStreamConstraints = {
                video: {
                    facingMode: mode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            };

            const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
            setStream(mediaStream);

            if (videoRef.current) {
                videoRef.current.srcObject = mediaStream;
                await videoRef.current.play();
            }
        } catch (err: unknown) {
            console.error('Camera access error:', err);
            // Fallback to any available video device
            try {
                const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                setStream(fallbackStream);
                if (videoRef.current) {
                    videoRef.current.srcObject = fallbackStream;
                    await videoRef.current.play();
                }
            } catch (fallbackErr) {
                console.error('Fallback camera error:', fallbackErr);
                setCameraError('Gagal mengakses kamera. Pastikan izin kamera telah diberikan di browser atau gunakan opsi Unggah File.');
            }
        }
    }, [stream]);

    useEffect(() => {
        if (open) {
            startCamera(facingMode);
        } else {
            stopStream();
        }
        return () => {
            stopStream();
        };
    }, [open, facingMode]);

    const handleFlipCamera = () => {
        const nextMode = facingMode === 'environment' ? 'user' : 'environment';
        setFacingMode(nextMode);
    };

    const handleTakePhoto = () => {
        if (!videoRef.current || !canvasRef.current || isCapturing) return;

        setIsCapturing(true);
        const video = videoRef.current;
        const canvas = canvasRef.current;

        const w = video.videoWidth || 1280;
        const h = video.videoHeight || 720;

        canvas.width = w;
        canvas.height = h;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            setIsCapturing(false);
            return;
        }

        // Draw video frame
        if (facingMode === 'user') {
            // Mirror selfie
            ctx.save();
            ctx.translate(w, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, w, h);
            ctx.restore();
        } else {
            ctx.drawImage(video, 0, 0, w, h);
        }

        // Add official watermark badge at bottom
        const bannerH = Math.max(50, Math.round(h * 0.08));
        ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
        ctx.fillRect(0, h - bannerH, w, bannerH);

        // Watermark text
        const fontSize = Math.max(14, Math.round(bannerH * 0.32));
        ctx.font = `600 ${fontSize}px sans-serif`;
        ctx.fillStyle = '#ffffff';
        ctx.textBaseline = 'middle';

        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
        const timeStr = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });

        const leftText = `SMK N 1 BANTUL • DOKUMENTASI KBM (${dateStr} ${timeStr} WIB)`;
        ctx.fillText(leftText, 20, h - bannerH / 2);

        if (classNameInfo) {
            ctx.textAlign = 'right';
            ctx.fillText(classNameInfo, w - 20, h - bannerH / 2);
            ctx.textAlign = 'left';
        }

        canvas.toBlob(
            (blob) => {
                if (blob) {
                    const fileName = `kbm_${Date.now()}.jpg`;
                    const file = new File([blob], fileName, { type: 'image/jpeg' });
                    const previewUrl = URL.createObjectURL(blob);
                    onCapture(file, previewUrl);
                    stopStream();
                    onOpenChange(false);
                }
                setIsCapturing(false);
            },
            'image/jpeg',
            0.88
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl p-0 overflow-hidden bg-slate-950 text-white border-slate-800">
                <DialogHeader className="p-4 pb-2 border-b border-slate-800 bg-slate-900/80">
                    <div className="flex items-center justify-between">
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-slate-100">
                            <Camera className="h-5 w-5 text-primary" />
                            {title}
                        </DialogTitle>
                    </div>
                    <DialogDescription className="text-xs text-slate-400">
                        Arahkan kamera ke aktivitas pembelajaran atau guru mengajar di kelas, lalu klik Ambil Foto.
                    </DialogDescription>
                </DialogHeader>

                <div className="relative aspect-video bg-black flex items-center justify-center overflow-hidden">
                    {cameraError ? (
                        <div className="p-6 text-center space-y-3 max-w-sm">
                            <AlertCircle className="h-10 w-10 text-rose-500 mx-auto" />
                            <p className="text-sm text-slate-200">{cameraError}</p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => startCamera(facingMode)}
                                className="gap-2 text-xs border-slate-700 bg-slate-800 hover:bg-slate-700 text-white"
                            >
                                <RefreshCw className="h-3.5 w-3.5" />
                                Coba Lagi
                            </Button>
                        </div>
                    ) : (
                        <>
                            <video
                                ref={videoRef}
                                playsInline
                                muted
                                className={`w-full h-full object-cover ${facingMode === 'user' ? 'scale-x-[-1]' : ''}`}
                            />

                            {/* Viewfinder Grid Overlay */}
                            <div className="absolute inset-0 pointer-events-none border border-white/10 grid grid-cols-3 grid-rows-3">
                                <div className="border-r border-b border-white/10" />
                                <div className="border-r border-b border-white/10" />
                                <div className="border-b border-white/10" />
                                <div className="border-r border-b border-white/10" />
                                <div className="border-r border-b border-white/10" />
                                <div className="border-b border-white/10" />
                                <div className="border-r border-white/10" />
                                <div className="border-r border-white/10" />
                                <div />
                            </div>

                            {/* Class Info Pill */}
                            {classNameInfo && (
                                <div className="absolute top-3 left-3 bg-black/60 backdrop-blur-xs px-2.5 py-1 rounded-full text-[11px] font-medium border border-white/10">
                                    {classNameInfo}
                                </div>
                            )}

                            {/* Camera Toggle Button */}
                            <button
                                type="button"
                                onClick={handleFlipCamera}
                                className="absolute top-3 right-3 h-9 w-9 rounded-full bg-black/60 backdrop-blur-xs border border-white/10 flex items-center justify-center text-white hover:bg-black/80 transition-colors"
                                title="Ganti Kamera (Depan / Belakang)"
                            >
                                <SwitchCamera className="h-4 w-4" />
                            </button>
                        </>
                    )}
                </div>

                {/* Footer Controls */}
                <div className="p-4 bg-slate-900/90 border-t border-slate-800 flex items-center justify-between gap-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onOpenChange(false)}
                        className="text-xs text-slate-400 hover:text-white hover:bg-slate-800"
                    >
                        Batal
                    </Button>

                    <Button
                        type="button"
                        disabled={!stream || !!cameraError || isCapturing}
                        onClick={handleTakePhoto}
                        className="gap-2 px-6 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold shadow-lg text-sm"
                    >
                        <Camera className="h-4 w-4" />
                        {isCapturing ? 'Memproses...' : 'Ambil Foto'}
                    </Button>
                </div>

                {/* Hidden canvas for snapshot rendering */}
                <canvas ref={canvasRef} className="hidden" />
            </DialogContent>
        </Dialog>
    );
}

