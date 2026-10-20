<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <a href="{{ route('guru.journals.index') }}" class="hover:underline">Jurnal Mengajar</a>
                    <span>/</span>
                    <span class="text-gray-700 font-semibold">Rekap Detail Jurnal #{{ $journal->id }}</span>
                </div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>📖</span> Detail Jurnal Pembelajaran & Rekap Presensi
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('guru.journals.index') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    ← Kembali ke Daftar Sesi
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition">
                    <span>🖨️</span> Cetak Berita Acara
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-sm flex items-center gap-2">
                    <span class="text-emerald-600 text-lg">✅</span>
                    <p class="text-emerald-800 font-medium text-sm">{{ session('success') }}</p>
                </div>
            @endif

            @if (session('info'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm flex items-center gap-2">
                    <span class="text-blue-600 text-lg">ℹ️</span>
                    <p class="text-blue-800 font-medium text-sm">{{ session('info') }}</p>
                </div>
            @endif

            <!-- Banner Info Sesi Jurnal -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900 text-white p-6 rounded-2xl shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            {{ $journal->schedule->cycle_type ?? 'Putaran' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            Status: Selesai Diisi
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold mt-2 text-white">{{ $journal->schedule->subject }}</h3>
                    <p class="text-xs text-indigo-200 mt-1">
                        Guru Pengampu: <strong class="text-white">{{ $journal->schedule->teacher->fullname ?? auth()->user()->fullname }}</strong>
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs bg-white/10 backdrop-blur-sm p-4 rounded-xl border border-white/10">
                    <div>
                        <span class="text-indigo-200 block">Tanggal:</span>
                        <strong class="text-white">{{ \Carbon\Carbon::parse($journal->date)->translatedFormat('d M Y') }}</strong>
                    </div>
                    <div>
                        <span class="text-indigo-200 block">Kelas:</span>
                        <strong class="text-white">{{ $journal->schedule->schoolClass->class_name ?? 'N/A' }}</strong>
                    </div>
                    <div>
                        <span class="text-indigo-200 block">Ruangan:</span>
                        <strong class="text-white">{{ $journal->schedule->room->room_name ?? 'N/A' }}</strong>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- KOLOM KIRI (1 Kolom): Materi Ajar & Foto Bukti Realtime -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Foto Bukti Pembelajaran Realtime -->
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                        <h4 class="font-bold text-sm text-gray-900 mb-3 flex items-center gap-2">
                            <span>📷</span> Foto Bukti Kelas Realtime
                        </h4>
                        @if ($journal->photo_path)
                            <div class="rounded-xl overflow-hidden border border-gray-200 shadow-xs relative group">
                                <img src="{{ asset('storage/' . $journal->photo_path) }}" 
                                     alt="Foto Bukti Pembelajaran" 
                                     class="w-full h-56 object-cover hover:scale-105 transition duration-300">
                                <a href="{{ asset('storage/' . $journal->photo_path) }}" 
                                   target="_blank" 
                                   class="absolute bottom-2 right-2 px-2.5 py-1 bg-black/70 hover:bg-black text-white text-[11px] font-semibold rounded-lg backdrop-blur-xs transition">
                                    🔍 Perbesar Foto
                                </a>
                            </div>
                        @else
                            <div class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-gray-400 text-xs">
                                Tidak ada foto kegiatan yang terlampir.
                            </div>
                        @endif
                    </div>

                    <!-- Materi Pembelajaran -->
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                        <h4 class="font-bold text-sm text-gray-900 mb-2 flex items-center gap-2">
                            <span>📚</span> Ringkasan Materi Pembelajaran
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-xl text-xs text-gray-800 leading-relaxed border border-gray-100 whitespace-pre-line">
                            {{ $journal->topic_description }}
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN (2 Kolom): Rekapitulasi Presensi & Daftar Siswa -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Kartu Statistik Kehadiran -->
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                        <h4 class="font-bold text-sm text-gray-900 mb-4 flex items-center justify-between">
                            <span class="flex items-center gap-2"><span>📊</span> Rekap Kehadiran Siswa</span>
                            <span class="text-xs text-gray-500 font-normal">Total Siswa: <strong>{{ $stats['total'] }}</strong></span>
                        </h4>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Hadir -->
                            <div class="bg-emerald-50 border border-emerald-100 p-3.5 rounded-xl text-center">
                                <span class="text-xs font-semibold text-emerald-700 block">Hadir</span>
                                <span class="text-2xl font-extrabold text-emerald-800">{{ $stats['hadir'] }}</span>
                                <span class="text-[10px] text-emerald-600 block mt-0.5">
                                    {{ $stats['total'] > 0 ? round(($stats['hadir'] / $stats['total']) * 100) : 0 }}%
                                </span>
                            </div>

                            <!-- Izin -->
                            <div class="bg-blue-50 border border-blue-100 p-3.5 rounded-xl text-center">
                                <span class="text-xs font-semibold text-blue-700 block">Izin</span>
                                <span class="text-2xl font-extrabold text-blue-800">{{ $stats['izin'] }}</span>
                                <span class="text-[10px] text-blue-600 block mt-0.5">
                                    {{ $stats['total'] > 0 ? round(($stats['izin'] / $stats['total']) * 100) : 0 }}%
                                </span>
                            </div>

                            <!-- Sakit -->
                            <div class="bg-amber-50 border border-amber-100 p-3.5 rounded-xl text-center">
                                <span class="text-xs font-semibold text-amber-700 block">Sakit</span>
                                <span class="text-2xl font-extrabold text-amber-800">{{ $stats['sakit'] }}</span>
                                <span class="text-[10px] text-amber-600 block mt-0.5">
                                    {{ $stats['total'] > 0 ? round(($stats['sakit'] / $stats['total']) * 100) : 0 }}%
                                </span>
                            </div>

                            <!-- Alpha -->
                            <div class="bg-red-50 border border-red-100 p-3.5 rounded-xl text-center">
                                <span class="text-xs font-semibold text-red-700 block">Alpha</span>
                                <span class="text-2xl font-extrabold text-red-800">{{ $stats['alpha'] }}</span>
                                <span class="text-[10px] text-red-600 block mt-0.5">
                                    {{ $stats['total'] > 0 ? round(($stats['alpha'] / $stats['total']) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Presensi Siswa -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b bg-gray-50/50 flex items-center justify-between">
                            <h4 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                                <span>👥</span> Daftar Presensi Siswa Kelas
                            </h4>
                            <span class="text-xs text-gray-500">{{ $attendances->count() }} Data Tercatat</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-700 uppercase font-semibold text-[11px] border-b">
                                    <tr>
                                        <th class="py-3 px-3 w-12 text-center">No</th>
                                        <th class="py-3 px-4">Nama Siswa</th>
                                        <th class="py-3 px-4">NIS / Akun</th>
                                        <th class="py-3 px-4 text-center">Status Presensi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($attendances as $idx => $att)
                                        <tr class="hover:bg-gray-50/70 transition">
                                            <td class="py-2.5 px-3 text-center text-gray-500 font-semibold">{{ $idx + 1 }}</td>
                                            <td class="py-2.5 px-4 font-bold text-gray-900">{{ $att->student->fullname ?? 'Siswa #'.$att->student_id }}</td>
                                            <td class="py-2.5 px-4 font-mono text-gray-500 text-[11px]">{{ $att->student->username ?? '-' }}</td>
                                            <td class="py-2.5 px-4 text-center">
                                                @if ($att->status === 'Hadir')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        ✅ Hadir
                                                    </span>
                                                @elseif ($att->status === 'Izin')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                                        ℹ️ Izin
                                                    </span>
                                                @elseif ($att->status === 'Sakit')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                        💊 Sakit
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-300">
                                                        ❌ Alpha
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-gray-400 text-xs">
                                                Tidak ada data presensi yang ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
