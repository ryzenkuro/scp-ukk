<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Presensi - {{ $schoolClass->class_name }} ({{ $startDate }} s.d. {{ $endDate }})</title>

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css'])

    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @media print {
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .page-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body class="antialiased text-gray-900 min-h-screen py-6 sm:py-10">

    <!-- Floating Top Bar (Disembunyikan saat cetak) -->
    <div class="no-print fixed top-0 inset-x-0 z-50 bg-gray-900/90 backdrop-blur-md text-white py-3 px-4 shadow-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.attendance.index', ['class_id' => $schoolClass->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-xs font-semibold transition">
                <span>←</span> Kembali
            </a>
            <span class="text-xs text-gray-300 hidden sm:inline">
                Pratinjau Cetak Standar Kertas A4 (SMK Negeri 1 Bantul)
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg shadow-md transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Sekarang (Ctrl + P)</span>
            </button>
        </div>
    </div>

    <!-- Halaman Dokumen A4 -->
    <div class="page-container max-w-4xl mx-auto bg-white p-8 sm:p-12 shadow-xl border border-gray-200 rounded-2xl mt-12 sm:mt-8">

        <!-- KOP SURAT RESMI SEKOLAH (SMK NEGERI 1 BANTUL) -->
        <header class="border-b-4 border-double border-gray-900 pb-3 mb-6">
            <div class="flex items-center justify-between text-center">
                <div class="w-16 h-16 shrink-0 flex items-center justify-center text-3xl font-bold bg-gray-100 rounded-full border border-gray-300">
                    🏫
                </div>
                <div class="flex-1 px-4 text-center">
                    <h3 class="text-xs sm:text-sm font-semibold tracking-wider uppercase text-gray-800">
                        PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA
                    </h3>
                    <h4 class="text-xs sm:text-sm font-semibold tracking-wider uppercase text-gray-800">
                        DINAS PENDIDIKAN, PEMUDA, DAN OLAHRAGA
                    </h4>
                    <h1 class="text-lg sm:text-2xl font-black uppercase text-gray-900 tracking-wide mt-0.5">
                        SMK NEGERI 1 BANTUL
                    </h1>
                    <p class="text-[10px] sm:text-xs text-gray-600 mt-1 leading-relaxed">
                        Jalan Parangtritis Km 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715 | Telp: (0274) 367156<br />
                        Laman: www.smkn1bantul.sch.id • Pos-el: info@smkn1bantul.sch.id
                    </p>
                </div>
                <div class="w-16 h-16 shrink-0 flex items-center justify-center text-3xl font-bold bg-gray-100 rounded-full border border-gray-300">
                    🎓
                </div>
            </div>
        </header>

        <!-- JUDUL & IDENTITAS DOKUMEN -->
        <div class="text-center mb-6">
            <h2 class="text-base sm:text-lg font-black uppercase text-gray-900 tracking-wide underline underline-offset-4">
                LAPORAN REKAPITULASI PRESENSI KEHADIRAN SISWA
            </h2>
            <p class="text-xs text-gray-700 mt-1 font-medium">
                Tahun Ajaran 2025/2026 — Sistem Informasi Manajemen Pembelajaran Blok
            </p>
        </div>

        <!-- METADATA LAPORAN -->
        <div class="grid grid-cols-2 text-xs text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <div class="space-y-1">
                <p>
                    <span class="font-bold inline-block w-28">Kelas Rombel</span>: 
                    <span class="font-extrabold uppercase">{{ $schoolClass->class_name }}</span>
                </p>
                <p>
                    <span class="font-bold inline-block w-28">Wali Kelas</span>: 
                    <span>{{ $waliKelas->fullname ?? 'Belum Ditentukan' }}</span>
                </p>
            </div>
            <div class="space-y-1 text-right sm:text-left">
                <p>
                    <span class="font-bold inline-block w-28">Periode Rekap</span>: 
                    <span>{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y') }}</span>
                </p>
                <p>
                    <span class="font-bold inline-block w-28">Dicetak Pada</span>: 
                    <span>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                </p>
            </div>
        </div>

        <!-- TABEL REKAPITULASI PRESENSI SISWA -->
        <div class="mb-8">
            <table class="w-full text-left border-collapse border border-gray-900 text-[11px] leading-tight">
                <thead>
                    <tr class="bg-gray-100 text-gray-900 border-b border-gray-900 font-extrabold text-center uppercase">
                        <th class="border border-gray-900 py-2 px-1 w-8">No</th>
                        <th class="border border-gray-900 py-2 px-2 w-24">NIS</th>
                        <th class="border border-gray-900 py-2 px-3 text-left">Nama Siswa</th>
                        <th class="border border-gray-900 py-2 px-2 w-12 bg-gray-50">H</th>
                        <th class="border border-gray-900 py-2 px-2 w-12 bg-gray-50">S</th>
                        <th class="border border-gray-900 py-2 px-2 w-12 bg-gray-50">I</th>
                        <th class="border border-gray-900 py-2 px-2 w-12 bg-gray-50">A</th>
                        <th class="border border-gray-900 py-2 px-2 w-14">Total</th>
                        <th class="border border-gray-900 py-2 px-2 w-20">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportData as $idx => $row)
                        <tr class="text-gray-900 border-b border-gray-900">
                            <td class="border border-gray-900 py-1.5 px-1 text-center font-bold">
                                {{ $idx + 1 }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 font-mono text-center">
                                {{ $row['student']->username }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-3 font-semibold uppercase">
                                {{ $row['student']->fullname }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center font-bold">
                                {{ $row['h'] }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center">
                                {{ $row['s'] }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center">
                                {{ $row['i'] }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center">
                                {{ $row['a'] }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center font-bold">
                                {{ $row['total'] }}
                            </td>
                            <td class="border border-gray-900 py-1.5 px-2 text-center font-extrabold">
                                {{ $row['percentage'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-gray-900 py-4 text-center text-gray-500 italic">
                                Belum ada siswa terdaftar pada kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 border-t-2 border-gray-900 font-black text-gray-900">
                        <td colspan="3" class="border border-gray-900 py-2 px-3 text-right uppercase tracking-wider">
                            Akumulasi & Rata-rata Kelas:
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black">
                            {{ $summary['total_h'] }}
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black">
                            {{ $summary['total_s'] }}
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black">
                            {{ $summary['total_i'] }}
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black">
                            {{ $summary['total_a'] }}
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black">
                            {{ $summary['total_records'] }}
                        </td>
                        <td class="border border-gray-900 py-2 px-2 text-center font-black text-indigo-900">
                            {{ $summary['average_percentage'] }}%
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Keterangan Singkatan -->
            <div class="mt-2 text-[10px] text-gray-600 flex items-center justify-between">
                <div>
                    <strong>Keterangan:</strong> 
                    H = Hadir | S = Sakit | I = Izin | A = Alpha (Tanpa Keterangan)
                </div>
                <div>
                    <strong>Rumus Kehadiran:</strong> (Hadir / Total Pertemuan) × 100%
                </div>
            </div>
        </div>

        <!-- KOLOM TANDA TANGAN GANDA RESMI -->
        <div class="grid grid-cols-2 gap-8 text-xs text-gray-900 pt-6 avoid-break">
            <!-- Tanda Tangan Kiri: Wali Kelas -->
            <div class="text-center">
                <p class="font-medium text-gray-700">Mengetahui,</p>
                <p class="font-extrabold uppercase mt-0.5">Wali Kelas {{ $schoolClass->class_name }}</p>
                <div class="h-20 sm:h-24 flex items-end justify-center">
                    <!-- Ruang tanda tangan / paraf -->
                </div>
                <p class="font-extrabold underline uppercase">
                    {{ $waliKelas->fullname ?? '......................................................' }}
                </p>
                <p class="text-[11px] text-gray-700 mt-0.5">
                    NIP. {{ $waliKelas && is_numeric($waliKelas->username) ? $waliKelas->username : '..................................................' }}
                </p>
            </div>

            <!-- Tanda Tangan Kanan: Waka Kurikulum -->
            <div class="text-center">
                <p class="font-medium text-gray-700">Bantul, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</p>
                <p class="font-extrabold uppercase mt-0.5">Waka Urusan Kurikulum</p>
                <div class="h-20 sm:h-24 flex items-end justify-center">
                    <!-- Ruang tanda tangan / paraf -->
                </div>
                <p class="font-extrabold underline uppercase">
                    {{ $waka->fullname ?? '......................................................' }}
                </p>
                <p class="text-[11px] text-gray-700 mt-0.5">
                    NIP. {{ $waka && is_numeric($waka->username) ? $waka->username : '..................................................' }}
                </p>
            </div>
        </div>

    </div>

</body>
</html>
