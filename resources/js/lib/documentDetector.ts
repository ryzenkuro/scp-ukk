import {
    unpack_cascade,
    run_cascade,
    cluster_detections,
    rgba_to_grayscale,
    ClassifyRegionFunction,
} from './pico';

let cachedClassifier: ClassifyRegionFunction | null = null;

async function getClassifier(): Promise<ClassifyRegionFunction | null> {
    if (cachedClassifier) return cachedClassifier;
    try {
        const res = await fetch('/facefinder');
        if (!res.ok) return null;
        const buffer = await res.arrayBuffer();
        const bytes = new Int8Array(buffer);
        cachedClassifier = unpack_cascade(bytes);
        return cachedClassifier;
    } catch (err) {
        console.warn('Gagal memuat facefinder untuk deteksi dokumen:', err);
        return null;
    }
}

export interface DocumentScanResult {
    isValidDocument: boolean;
    isFaceDetected: boolean;
    errorMessage?: string;
}

/**
 * Deteksi piksel apakah tergolong pigmen warna kulit manusia alami (standar RGB Skin Model).
 */
function isSkinPixel(r: number, g: number, b: number): boolean {
    return (
        r > 95 &&
        g > 40 &&
        b > 20 &&
        Math.max(r, g, b) - Math.min(r, g, b) > 15 &&
        Math.abs(r - g) > 15 &&
        r > g &&
        r > b
    );
}

/**
 * Menganalisis metrik global gambar: rasio kertas terang (>185 lum) dan rasio warna kulit.
 */
function analyzeImageMetrics(data: Uint8ClampedArray): {
    whiteRatio: number;
    skinRatio: number;
} {
    let whiteCount = 0;
    let skinCount = 0;
    const totalPixels = data.length / 4;

    for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        const lum = 0.299 * r + 0.587 * g + 0.114 * b;
        if (lum > 185) {
            whiteCount++;
        }
        if (isSkinPixel(r, g, b)) {
            skinCount++;
        }
    }

    return {
        whiteRatio: totalPixels > 0 ? whiteCount / totalPixels : 0,
        skinRatio: totalPixels > 0 ? skinCount / totalPixels : 0,
    };
}

/**
 * Menghitung rasio warna kulit spesifik di dalam kotak calon wajah (ROI Face Bounding Box).
 */
function calculateBoxSkinRatio(
    data: Uint8ClampedArray,
    width: number,
    height: number,
    centerR: number,
    centerC: number,
    size: number
): number {
    const r1 = Math.max(0, Math.floor(centerR - size / 2));
    const r2 = Math.min(height - 1, Math.ceil(centerR + size / 2));
    const c1 = Math.max(0, Math.floor(centerC - size / 2));
    const c2 = Math.min(width - 1, Math.ceil(centerC + size / 2));

    let boxPixels = 0;
    let boxSkinPixels = 0;

    for (let y = r1; y <= r2; y++) {
        for (let x = c1; x <= c2; x++) {
            const idx = (y * width + x) * 4;
            const r = data[idx];
            const g = data[idx + 1];
            const b = data[idx + 2];
            boxPixels++;
            if (isSkinPixel(r, g, b)) {
                boxSkinPixels++;
            }
        }
    }

    return boxPixels > 0 ? boxSkinPixels / boxPixels : 0;
}

/**
 * Mendeteksi apakah file yang diunggah adalah dokumen/surat yang sah,
 * atau merupakan foto wajah/selfie yang diunggah secara keliru ke kolom surat keterangan.
 * Menggunakan pendekatan multi-layer cerdas:
 * 1. Layer 1: Document Paper & Global Skin Pre-Filter (Fast-Pass dokumen)
 * 2. Layer 2: Text/Texture Noise Suppression (Mencegah false-positive baris teks)
 * 3. Layer 3: Face ROI Skin-Tone Verification (Hanya mendeteksi wajah dengan warna kulit asli)
 */
export async function validateDocumentFile(file: File): Promise<DocumentScanResult> {
    // 1. Jika file adalah PDF -> Sah sebagai dokumen
    if (file.type === 'application/pdf') {
        return {
            isValidDocument: true,
            isFaceDetected: false,
        };
    }

    // 2. Jika bukan gambar (misal tipe berkas lain)
    if (!file.type.startsWith('image/')) {
        return {
            isValidDocument: false,
            isFaceDetected: false,
            errorMessage: 'Format berkas tidak didukung. Harap unggah PDF, JPG, PNG, atau WEBP.',
        };
    }

    // 3. Jika gambar -> Validasi cerdas multi-layer
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = async (e) => {
            const img = new Image();
            img.onload = async () => {
                try {
                    // Skala gambar ke resolusi downscale terkalibrasi (~320px)
                    const dw = 320;
                    const dh = Math.max(180, Math.round((img.height / img.width) * dw));

                    const canvas = document.createElement('canvas');
                    canvas.width = dw;
                    canvas.height = dh;
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    if (!ctx) {
                        return resolve({ isValidDocument: true, isFaceDetected: false });
                    }

                    ctx.drawImage(img, 0, 0, dw, dh);
                    const imgData = ctx.getImageData(0, 0, dw, dh);

                    // ========================================================
                    // LAYER 1: Document Paper Pre-Filter (Fast-Pass)
                    // Dokumen surat fisik/digital memiliki mayoritas latar kertas
                    // terang (>185 lum) dan minim warna kulit manusia (<6%).
                    // ========================================================
                    const { whiteRatio, skinRatio } = analyzeImageMetrics(imgData.data);
                    if (whiteRatio >= 0.45 && skinRatio < 0.06) {
                        return resolve({
                            isValidDocument: true,
                            isFaceDetected: false,
                        });
                    }

                    // Muat classifier wajah Pico jika gambar bukan dokumen kertas dominan
                    const classifier = await getClassifier();
                    if (!classifier) {
                        return resolve({ isValidDocument: true, isFaceDetected: false });
                    }

                    const gray = rgba_to_grayscale(imgData.data, dh, dw);
                    const rawDetections = run_cascade(
                        { pixels: gray, nrows: dh, ncols: dw, ldim: dw },
                        classifier,
                        {
                            shiftfactor: 0.1,
                            minsize: 28,
                            maxsize: Math.round(dw * 0.85),
                            scalefactor: 1.15,
                        }
                    );

                    // ========================================================
                    // LAYER 2: Text/Texture Noise Suppression
                    // Baris-baris teks pada kertas sering memicu ribuan deteksi acak (>250),
                    // berbeda dengan foto wajah tunggal manusia yang terkonsentrasi.
                    // ========================================================
                    if (rawDetections.length > 250) {
                        return resolve({
                            isValidDocument: true,
                            isFaceDetected: false,
                        });
                    }

                    const detections = cluster_detections(rawDetections, 0.2);

                    // ========================================================
                    // LAYER 3: Face ROI Skin-Tone Verification (Ground Truth)
                    // Calon wajah dominan harus berukuran proporsional (s >= 18% lebar),
                    // memiliki skor >= 12, DAN wajib memuat pigmen warna kulit manusia (>=10%)
                    // di dalam kotak wajah untuk membedakan wajah asli vs teks/stempel.
                    // ========================================================
                    const prominentFace = detections.find(([r, c, s, score]) => {
                        if (score < 12.0 || s < dw * 0.18) return false;
                        const boxSkinRatio = calculateBoxSkinRatio(imgData.data, dw, dh, r, c, s);
                        return boxSkinRatio >= 0.10;
                    });

                    if (prominentFace) {
                        return resolve({
                            isValidDocument: false,
                            isFaceDetected: true,
                            errorMessage:
                                'Berkas yang dipilih terdeteksi sebagai foto wajah/selfie, bukan dokumen surat keterangan. Harap unggah foto fisik surat keterangan dokter atau surat izin dari orang tua.',
                        });
                    }

                    return resolve({
                        isValidDocument: true,
                        isFaceDetected: false,
                    });
                } catch (err) {
                    console.error('Error saat memindai dokumen:', err);
                    resolve({ isValidDocument: true, isFaceDetected: false });
                }
            };
            img.onerror = () => {
                resolve({
                    isValidDocument: false,
                    isFaceDetected: false,
                    errorMessage: 'Gagal membaca file gambar.',
                });
            };
            img.src = e.target?.result as string;
        };
        reader.onerror = () => {
            resolve({
                isValidDocument: false,
                isFaceDetected: false,
                errorMessage: 'Gagal memproses file.',
            });
        };
        reader.readAsDataURL(file);
    });
}

