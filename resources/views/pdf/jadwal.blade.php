<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Jadwal Pelajaran' }} - SMK Negeri 1 Bantul</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: #000 !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                page-break-after: always;
            }
            @page {
                size: A4 landscape;
                margin: 10mm 12mm;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #0f172a !important;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8fafc;
        }

        .kop-border {
            border-bottom: 3px double #0f172a;
        }
    </style>
</head>
<body class="p-4 sm:p-6 md:p-8 text-slate-900">

    <!-- Top Action Toolbar (Disembunyikan saat dicetak) -->
    <div class="no-print max-w-6xl mx-auto mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-800 font-bold">
                    A4
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">{{ $pageTitle ?? 'Pratinjau Dokumen Jadwal' }}</h2>
                    <p class="text-xs text-slate-500">Siap dicetak atau disimpan sebagai dokumen PDF (Layout A4 Landscape).</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Simpan / Cetak PDF
                </button>
                <button type="button" onclick="window.close()" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Container Dokumen Resmi A4 Landscape -->
    <div class="max-w-6xl mx-auto bg-white p-8 sm:p-10 shadow-md rounded-2xl border border-slate-200 print:shadow-none print:border-none print:p-0">
        
        <!-- Kop Surat Resmi SMK Negeri 1 Bantul -->
        <header class="kop-border pb-4 mb-6">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logosmk.png') }}" alt="Logo SMK N 1 Bantul" class="h-16 w-16 object-contain shrink-0" />
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-600">PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-600">DINAS PENDIDIKAN, PEMUDA, DAN OLAHRAGA</p>
                        <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-slate-900">SMK NEGERI 1 BANTUL</h1>
                        <p class="text-[11px] text-slate-500">Jl. Parangtritis Km. 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715 • Telp: (0274) 367156</p>
                    </div>
                </div>
                <div class="text-right hidden sm:block">
                    <span class="inline-block rounded-md bg-slate-100 border border-slate-300 px-3 py-1 text-xs font-bold text-slate-800">
                        DOKUMEN RESMI
                    </span>
                    <p class="text-[10px] text-slate-500 mt-1">Status: Terverifikasi Sistem</p>
                </div>
            </div>
        </header>

        <!-- Judul & Keterangan Dokumen -->
        <div class="text-center mb-6">
            <h2 class="text-lg sm:text-xl font-bold uppercase tracking-wide text-slate-900 underline decoration-2 underline-offset-4">
                {{ $documentTitle ?? 'JADWAL PELAJARAN SEKOLAH' }}
            </h2>
            <p class="text-xs font-semibold text-slate-700 mt-1.5">
                Tahun Ajaran: {{ $academicYear ?? '2026/2027' }} 
                @if(!empty($activeCycle)) • Putaran {{ $activeCycle }} @endif
                @if(!empty($filterClass)) • Kelas: <span class="font-bold">{{ $filterClass }}</span> @endif
                @if(!empty($filterTeacherName)) • Guru: <span class="font-bold">{{ $filterTeacherName }}</span> @endif
                @if(!empty($studentName)) • Siswa: <span class="font-bold">{{ $studentName }}</span> @endif
            </p>
            <p class="text-[10px] text-slate-500 mt-0.5">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y • H:i') }} WIB</p>
        </div>

        <!-- Tabel Jadwal Resmi -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border border-slate-900">
                <thead>
                    <tr class="bg-slate-100 text-slate-900 font-bold uppercase tracking-wider border-b border-slate-900">
                        <th class="border border-slate-900 px-3 py-2.5 text-center w-10">No</th>
                        <th class="border border-slate-900 px-3 py-2.5 w-24">Hari</th>
                        <th class="border border-slate-900 px-3 py-2.5 w-32">Waktu / Jam</th>
                        <th class="border border-slate-900 px-3 py-2.5">Mata Pelajaran</th>
                        <th class="border border-slate-900 px-3 py-2.5">Tenaga Pendidik</th>
                        <th class="border border-slate-900 px-3 py-2.5 w-24 text-center">Kelas</th>
                        <th class="border border-slate-900 px-3 py-2.5 w-28 text-center">Ruangan</th>
                        <th class="border border-slate-900 px-3 py-2.5 w-20 text-center">Putaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-900">
                    @if(!empty($hasCeremony))
                    <tr class="bg-red-50 text-red-950 font-semibold">
                        <td class="border border-slate-900 px-3 py-2 text-center font-bold text-red-700">*</td>
                        <td class="border border-slate-900 px-3 py-2 font-bold text-red-700">Senin</td>
                        <td class="border border-slate-900 px-3 py-2 whitespace-nowrap font-mono text-[11px] text-red-800">
                            07:15 - 08:00 WIB
                        </td>
                        <td class="border border-slate-900 px-3 py-2 font-bold text-red-900">Upacara Bendera</td>
                        <td class="border border-slate-900 px-3 py-2 text-red-800">Seluruh Guru & Tendik</td>
                        <td class="border border-slate-900 px-3 py-2 text-center text-red-800">Semua Kelas</td>
                        <td class="border border-slate-900 px-3 py-2 text-center text-red-800">Lapangan Upacara</td>
                        <td class="border border-slate-900 px-3 py-2 text-center text-red-800">Putaran {{ $activeCycle ?? 1 }}</td>
                    </tr>
                    @endif
                    @forelse($jadwals as $index => $j)
                    @if(!empty($hasCeremony) && $j->day_of_week === 'Senin' && substr($j->start_time, 0, 5) === '07:15')
                        @continue
                    @endif
                    <tr class="{{ $index % 2 === 1 ? 'bg-slate-50/70' : 'bg-white' }}">
                        <td class="border border-slate-900 px-3 py-2 text-center font-medium">{{ $index + 1 }}</td>
                        <td class="border border-slate-900 px-3 py-2 font-bold text-slate-900">{{ $j->day_of_week }}</td>
                        <td class="border border-slate-900 px-3 py-2 text-slate-800 whitespace-nowrap font-mono text-[11px]">
                            {{ substr($j->start_time, 0, 5) }} - {{ substr($j->end_time, 0, 5) }} WIB
                        </td>
                        <td class="border border-slate-900 px-3 py-2 font-bold text-slate-900">{{ $j->subject }}</td>
                        <td class="border border-slate-900 px-3 py-2 font-medium text-slate-800">{{ $j->teacher?->fullname ?? ($j->teacher?->username ?? 'Guru') }}</td>
                        <td class="border border-slate-900 px-3 py-2 text-center font-bold text-slate-900">{{ $j->class_name ?? ($j->schoolClass?->name ?? '-') }}</td>
                        <td class="border border-slate-900 px-3 py-2 text-center text-slate-700">{{ $j->room_name ?? ($j->room?->name ?? '-') }}</td>
                        <td class="border border-slate-900 px-3 py-2 text-center font-semibold text-slate-700">Putaran {{ $j->cycle_number ?? 1 }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="border border-slate-900 px-4 py-8 text-center text-slate-500 italic">
                            Tidak ada jadwal pelajaran yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Total Jam KBM -->
        <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs text-slate-600">
            <div>
                Total Jadwal Mengajar Terdata: <span class="font-bold text-slate-900">{{ count($jadwals) }} pertemuan</span>
            </div>
            <div>
                Kurikulum SMK Negeri 1 Bantul - Tahun Ajaran {{ $academicYear ?? '2026/2027' }}
            </div>
        </div>

        <!-- Kolom Tanda Tangan Resmi Pengesahan -->
        <div class="mt-12 pt-4 border-t border-slate-300 grid grid-cols-2 gap-8 text-xs text-slate-900">
            <div class="text-center">
                <p class="text-slate-500 mb-1">Mengetahui,</p>
                <p class="font-bold text-slate-800">Kepala SMK Negeri 1 Bantul</p>
                <div class="h-20"></div>
                <p class="font-bold underline text-slate-900">Mujari, M.Pd.</p>
                <p class="text-[10px] text-slate-500">NIP. 19680415 199412 1 002</p>
            </div>
            <div class="text-center">
                <p class="text-slate-500 mb-1">Bantul, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold text-slate-800">Waka Bidang Kurikulum</p>
                <div class="h-20"></div>
                <p class="font-bold underline text-slate-900">Drs. H. Hartono, M.Pd</p>
                <p class="text-[10px] text-slate-500">NIP. 19740315 200003 1 004</p>
            </div>
        </div>

    </div>

</body>
</html>

