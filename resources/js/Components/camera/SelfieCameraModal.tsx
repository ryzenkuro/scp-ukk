import React, { useRef, useState, useEffect, useCallback } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Camera, RefreshCw, AlertCircle } from 'lucide-react';
import {
  unpack_cascade,
  run_cascade,
  cluster_detections,
  rgba_to_grayscale,
  ClassifyRegionFunction,
} from '@/lib/pico';

interface SelfieCameraModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  studentName: string;
  studentClass: string;
  onCapture: (file: File, previewUrl: string) => void;
}

export function SelfieCameraModal({
  open,
  onOpenChange,
  studentName,
  studentClass,
  onCapture,
}: SelfieCameraModalProps) {
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const analysisCanvasRef = useRef<HTMLCanvasElement | null>(null);

  const [stream, setStream] = useState<MediaStream | null>(null);
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [isFaceDetected, setIsFaceDetected] = useState(false);
  const [isFlashActive, setIsFlashActive] = useState(false);

  const classifierRef = useRef<ClassifyRegionFunction | null>(null);
  const detectionCountRef = useRef(0);
  const isCapturingRef = useRef(false);
  const animFrameIdRef = useRef<number | null>(null);
  const lastAnalyzeTimeRef = useRef<number>(0);

  // 1. Muat model cascade facefinder (Pico.js)
  useEffect(() => {
    loadClassifier();
  }, []);

  const loadClassifier = async () => {
    if (classifierRef.current) return classifierRef.current;
    try {
      const res = await fetch('/facefinder');
      const buffer = await res.arrayBuffer();
      const bytes = new Int8Array(buffer);
      const classifier = unpack_cascade(bytes);
      classifierRef.current = classifier;
      return classifier;
    } catch (err) {
      console.error('Gagal memuat model deteksi wajah facefinder:', err);
      return null;
    }
  };

  // 2. Mulai / Hentikan stream kamera saat modal dibuka / ditutup
  useEffect(() => {
    if (open) {
      isCapturingRef.current = false;
      detectionCountRef.current = 0;
      setIsFaceDetected(false);
      setIsFlashActive(false);
      startCamera();
    } else {
      stopCamera();
    }

    return () => {
      stopCamera();
    };
  }, [open]);

  const startCamera = async () => {
    setCameraError(null);

    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('Browser Anda tidak mendukung akses kamera langsung.');
      }

      // Pastikan model facefinder siap sebelum / bersamaan dengan stream
      await loadClassifier();

      const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: { ideal: 1280 },
          height: { ideal: 720 },
        },
        audio: false,
      });

      setStream(mediaStream);

      if (videoRef.current) {
        videoRef.current.srcObject = mediaStream;
        try {
          await videoRef.current.play();
        } catch {
          // autoplay fallback
        }
      }

      // Mulai loop analisis segera
      if (animFrameIdRef.current) cancelAnimationFrame(animFrameIdRef.current);
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);

    } catch (err: any) {
      console.error('Gagal mengakses kamera:', err);
      let message = 'Tidak dapat membuka kamera. Pastikan izin kamera telah diberikan di browser.';
      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        message = 'Akses kamera ditolak oleh pengguna. Mohon izinkan akses kamera di pengaturan browser.';
      } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        message = 'Perangkat kamera tidak ditemukan pada komputer/ponsel ini.';
      }
      setCameraError(message);
    }
  };

  const stopCamera = () => {
    if (animFrameIdRef.current) {
      cancelAnimationFrame(animFrameIdRef.current);
      animFrameIdRef.current = null;
    }
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      setStream(null);
    }
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
  };

  // 3. Loop Pemindaian Wajah Asli (Evaluasi Topologi Fitur Wajah Manusia - Anti Tangan)
  const analyzeFrame = useCallback(() => {
    const video = videoRef.current;
    if (!video || isCapturingRef.current) {
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
      return;
    }

    // Jika video belum siap merender frame, tunggu frame berikutnya
    if (video.readyState < 2 || video.videoWidth === 0 || video.videoHeight === 0) {
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
      return;
    }

    const now = performance.now();
    // Jalankan inferensi setiap ~75ms (~13 FPS analisis) untuk efisiensi CPU dan akurasi tinggi
    if (now - lastAnalyzeTimeRef.current < 75) {
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
      return;
    }
    lastAnalyzeTimeRef.current = now;

    const classifier = classifierRef.current;
    if (!classifier) {
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
      return;
    }

    const vw = video.videoWidth;
    const vh = video.videoHeight;

    // Resolusi downscale terkalibrasi (240px lebar menjaga proporsi aspek rasio video)
    const dw = 240;
    const dh = Math.round((vh / vw) * dw);

    let analysisCanvas = analysisCanvasRef.current;
    if (!analysisCanvas) {
      analysisCanvas = document.createElement('canvas');
      analysisCanvas.width = dw;
      analysisCanvas.height = dh;
      analysisCanvasRef.current = analysisCanvas;
    } else if (analysisCanvas.width !== dw || analysisCanvas.height !== dh) {
      analysisCanvas.width = dw;
      analysisCanvas.height = dh;
    }

    const actx = analysisCanvas.getContext('2d', { willReadFrequently: true });
    if (!actx) {
      animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
      return;
    }

    // Gambar frame video ke canvas analisis dengan efek cermin
    actx.save();
    actx.translate(dw, 0);
    actx.scale(-1, 1);
    actx.drawImage(video, 0, 0, dw, dh);
    actx.restore();

    const imgData = actx.getImageData(0, 0, dw, dh);
    const grayPixels = rgba_to_grayscale(imgData.data, dh, dw);

    // Parameter pencarian wajah cascade (terkalibrasi untuk jarak selfie wajar)
    const cascadeParams = {
      shiftfactor: 0.1,
      minsize: 24, // Minimal ukuran wajah 24px (mencakup wajah di jarak kamera normal)
      maxsize: Math.round(dw * 0.85),
      scalefactor: 1.15,
    };

    // Jalankan classifier cascade
    const rawDetections = run_cascade(
      {
        pixels: grayPixels,
        nrows: dh,
        ncols: dw,
        ldim: dw,
      },
      classifier,
      cascadeParams
    );

    // Kelompokkan deteksi (clustering)
    const detections = cluster_detections(rawDetections, 0.2);

    let faceFound = false;
    const centerX = dw / 2;
    const centerY = dh / 2;
    // Radius oval yang disesuaikan presisi dengan lingkaran panduan visual w-48 h-60
    const rx = dw * 0.20; // Radius horizontal oval
    const ry = dh * 0.30; // Radius vertikal oval

    for (const det of detections) {
      const [r, c, s, score] = det;
      // 1. Verifikasi skor wajah manusia asli (wajah: 100~1000+, tangan: <=7.9)
      // 2. Verifikasi proporsi ukuran wajah pas di dalam lingkaran
      const isSizeValid = s >= dw * 0.16 && s <= dw * 0.60;

      if (score >= 12.0 && isSizeValid) {
        // 3. Formula matematis elips: titik pusat wajah (c, r) WAJIB berada di dalam batas lingkaran oval
        const normX = (c - centerX) / rx;
        const normY = (r - centerY) / ry;
        const insideOval = normX * normX + normY * normY <= 1.0;

        if (insideOval) {
          faceFound = true;
          break;
        }
      }
    }

    const REQUIRED_STABLE_FRAMES = 5; // ~0.4 detik konfirmasi wajah stabil

    if (faceFound) {
      detectionCountRef.current = Math.min(
        REQUIRED_STABLE_FRAMES,
        detectionCountRef.current + 1
      );
      setIsFaceDetected(true);
    } else {
      detectionCountRef.current = Math.max(0, detectionCountRef.current - 1);
      if (detectionCountRef.current === 0) {
        setIsFaceDetected(false);
      }
    }

    animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
  }, [studentName, studentClass]);

  // 4. Eksekusi pengambilan foto manual dengan watermark resmi SMK N 1 Bantul
  const handleManualCapture = () => {
    if (isCapturingRef.current) return;
    isCapturingRef.current = true;

    const video = videoRef.current;
    const canvas = canvasRef.current;
    if (!video || !canvas) return;

    // Aktifkan kilatan rana (shutter flash) sekejap
    setIsFlashActive(true);

    const width = video.videoWidth || 1280;
    const height = video.videoHeight || 720;
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // 1. Gambar frame video dengan efek cermin (horizontal flip)
    ctx.save();
    ctx.translate(width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, width, height);
    ctx.restore();

    // 2. Tambahkan watermark overlay transparan di bagian bawah
    const barHeight = Math.max(70, Math.floor(height * 0.12));
    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)'; // slate-900 semi-transparan
    ctx.fillRect(0, height - barHeight, width, barHeight);

    // Garis aksen atas watermark
    ctx.strokeStyle = '#0284c7'; // sky-600
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.moveTo(0, height - barHeight);
    ctx.lineTo(width, height - barHeight);
    ctx.stroke();

    // 3. Tuliskan teks identitas & timestamp pada watermark
    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
    const timeStr = now.toLocaleTimeString('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });

    const fontSizeTitle = Math.max(16, Math.floor(height * 0.032));
    const fontSizeSub = Math.max(12, Math.floor(height * 0.024));

    ctx.fillStyle = '#ffffff';
    ctx.font = `bold ${fontSizeTitle}px sans-serif`;
    ctx.fillText(
      `SMK N 1 BANTUL • ${studentName} (${studentClass})`,
      24,
      height - barHeight + fontSizeTitle + 12
    );

    ctx.fillStyle = '#94a3b8'; // slate-400
    ctx.font = `${fontSizeSub}px sans-serif`;
    ctx.fillText(
      `PENGAMBILAN REAL-TIME • ${dateStr}, ${timeStr} WIB`,
      24,
      height - barHeight + fontSizeTitle + fontSizeSub + 18
    );

    // 4. Ekspor canvas ke JPEG File & masukkan otomatis ke formulir
    setTimeout(() => {
      canvas.toBlob(
        (blob) => {
          if (!blob) return;

          const filename = `selfie_${Date.now()}.jpg`;
          const file = new File([blob], filename, { type: 'image/jpeg' });
          const previewUrl = URL.createObjectURL(blob);

          stopCamera();
          onCapture(file, previewUrl);
          onOpenChange(false);
        },
        'image/jpeg',
        0.92
      );
    }, 200);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-xl p-0 overflow-hidden border border-border">
        <DialogHeader className="p-4 border-b border-border bg-card">
          <DialogTitle className="flex items-center gap-2 text-base font-bold">
            <Camera className="h-5 w-5 text-primary" />
            Kamera Selfie Real-Time
          </DialogTitle>
          <DialogDescription className="text-xs">
            Arahkan kamera ke wajah Anda, lalu klik tombol <strong>Ambil Foto Sekarang</strong> di bawah.
          </DialogDescription>
        </DialogHeader>

        {/* Viewfinder Area */}
        <div className="relative bg-slate-950 flex items-center justify-center min-h-[340px] max-h-[460px] overflow-hidden">
          {cameraError ? (
            <div className="p-6 text-center max-w-md">
              <AlertCircle className="h-10 w-10 text-destructive mx-auto mb-2" />
              <p className="text-sm font-semibold text-white">Gagal Mengakses Kamera</p>
              <p className="text-xs text-slate-300 mt-1">{cameraError}</p>
              <Button
                variant="outline"
                size="sm"
                onClick={startCamera}
                className="mt-4 gap-1.5 text-xs text-white border-slate-700 bg-slate-800 hover:bg-slate-700 cursor-pointer"
              >
                <RefreshCw className="h-3.5 w-3.5" />
                Coba Lagi
              </Button>
            </div>
          ) : (
            <>
              {/* Video Element */}
              <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                onLoadedData={() => {
                  if (animFrameIdRef.current) cancelAnimationFrame(animFrameIdRef.current);
                  animFrameIdRef.current = requestAnimationFrame(analyzeFrame);
                }}
                className="w-full h-full object-cover transform -scale-x-100"
              />

              {/* Shutter Flash Effect Overlay */}
              {isFlashActive && (
                <div className="absolute inset-0 z-20 bg-white transition-opacity duration-200 pointer-events-none" />
              )}
            </>
          )}

          {/* Hidden Canvas for Watermarking */}
          <canvas ref={canvasRef} className="hidden" />
        </div>

        {/* Shutter Action Bar (Pengambilan Manual dengan Klik) */}
        {!cameraError && (
          <div className="p-4 bg-card border-t border-border flex items-center justify-center">
            <Button
              type="button"
              onClick={handleManualCapture}
              className="w-full sm:w-auto px-8 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs gap-2 rounded-full shadow-md cursor-pointer transition-transform active:scale-95"
            >
              <Camera className="h-4 w-4" />
              <span>Ambil Foto Sekarang</span>
            </Button>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
