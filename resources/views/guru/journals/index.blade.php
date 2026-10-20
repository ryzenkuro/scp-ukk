<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>📖</span> {{ __('Jurnal Mengajar & Presensi Kelas') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Buka kelas realtime, unggah foto bukti kegiatan pembelajaran, dan sinkronisasi presensi otomatis siswa.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    ← Dashboard
                </a>
                <a href="{{ route('guru.schedules.index') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg transition">
                    📅 Jadwal Mengajar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Notifications -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 text-lg">✅</span>
                        <p class="text-emerald-800 font-medium text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm flex items-center gap-2">
                    <span class="text-blue-600 text-lg">ℹ️</span>
                    <p class="text-blue-800 font-medium text-sm">{{ session('info') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm">
                    <div class="flex items-start gap-2">
                        <span class="text-red-600 text-lg">⚠️</span>
                        <div>
                            <h4 class="text-red-800 font-bold text-sm">Terjadi Kesalahan:</h4>
                            <ul class="list-disc list-inside text-xs text-red-700 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Header Info Card: Hari, Tanggal, & Putaran Aktif -->
            <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-indigo-950 text-white p-6 rounded-2xl shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            Siklus Aktif: {{ $config->active_cycle }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            ● Pembelajaran Berjalan
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold mt-2">Hari {{ $currentDayName }}, {{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}</h3>
                    <p class="text-xs text-indigo-200 mt-1">
                        Guru Pengampu: <span class="font-semibold text-white">{{ $teacher->fullname }}</span> ({{ $teacher->username }})
                    </p>
                </div>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-4 py-3 rounded-xl border border-white/10">
                    <div class="text-center">
                        <span class="text-xs text-indigo-200 block">Jadwal Hari Ini</span>
                        <span class="text-xl font-extrabold text-white">{{ $todaySchedules->count() }} Sesi</span>
                    </div>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div class="text-center">
                        <span class="text-xs text-indigo-200 block">Total Jadwal Siklus</span>
                        <span class="text-xl font-extrabold text-white">{{ $allMySchedules->count() }} Sesi</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: Jadwal Mengajar Hari Ini -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>🕒</span> Jadwal Mengajar Hari Ini ({{ $currentDayName }})
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pilih jadwal di bawah untuk membuka sesi kelas dan mengisi jurnal mengajar.</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-lg">
                        {{ $todaySchedules->count() }} Kelas Terjadwal
                    </span>
                </div>

                @if ($todaySchedules->isEmpty())
                    <div class="text-center py-12 px-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50">
                        <span class="text-4xl block mb-2">🏖️</span>
                        <h4 class="text-base font-bold text-gray-700">Tidak Ada Jadwal Mengajar Hari Ini</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                            Anda tidak memiliki jadwal mengajar pada hari {{ $currentDayName }} di {{ $config->active_cycle }}.
                            Silakan periksa jadwal hari lain atau tambahkan jadwal di modul manajemen jadwal.
                        </p>
                        <a href="{{ route('guru.schedules.index') }}" class="inline-flex items-center mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                            📅 Buka Manajemen Jadwal
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($todaySchedules as $sch)
                            @php
                                $journalToday = $sch->journals->first();
                                $isFilled = (bool) $journalToday;
                            @endphp
                            <div class="rounded-xl border {{ $isFilled ? 'border-emerald-200 bg-emerald-50/20' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-md' }} p-5 transition flex flex-col justify-between">
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-md {{ $isFilled ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $isFilled ? '✅ Jurnal Terisi' : '⏳ Belum Diisi' }}
                                        </span>
                                        <span class="text-xs font-mono font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                                            {{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }}
                                        </span>
                                    </div>

                                    <h4 class="font-bold text-base text-gray-900 mt-2">{{ $sch->subject }}</h4>
                                    
                                    <div class="mt-3 space-y-1.5 text-xs text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">🏫</span>
                                            <span>Kelas: <strong class="text-gray-800">{{ $sch->schoolClass->class_name ?? 'Kelas ?' }}</strong></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">📍</span>
                                            <span>Ruangan: <strong class="text-gray-800">{{ $sch->room->room_name ?? 'Ruang ?' }}</strong></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">🔄</span>
                                            <span>Putaran: <strong class="text-indigo-700">{{ $sch->cycle_type }}</strong></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-4 border-t border-gray-100">
                                    @if ($isFilled)
                                        <a href="{{ route('guru.journals.show', $journalToday->id) }}" 
                                           class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                            <span>👁️</span> Lihat Rekap Jurnal & Presensi
                                        </a>
                                    @else
                                        <a href="{{ route('guru.journals.create', $sch->id) }}" 
                                           class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                            <span>📝</span> Buka Kelas & Isi Jurnal
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- SECTION 2: Jadwal Hari Lain pada Putaran Ini (Quick Overview) -->
            @php
                $otherDaysSchedules = $allMySchedules->where('day_of_week', '!=', $currentDayName);
            @endphp
            @if ($otherDaysSchedules->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>📅</span> Jadwal Hari Lain pada {{ $config->active_cycle }}
                        </h4>
                        <span class="text-xs text-gray-500">{{ $otherDaysSchedules->count() }} Sesi</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach ($otherDaysSchedules as $oth)
                            <div class="p-3 rounded-lg border border-gray-100 bg-gray-50 text-xs">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-indigo-700">{{ $oth->day_of_week }}</span>
                                    <span class="text-gray-500 font-mono">{{ substr($oth->start_time, 0, 5) }}-{{ substr($oth->end_time, 0, 5) }}</span>
                                </div>
                                <div class="font-semibold text-gray-900 truncate">{{ $oth->subject }}</div>
                                <div class="text-gray-500 mt-0.5 truncate">{{ $oth->schoolClass->class_name }} • {{ $oth->room->room_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- SECTION 3: Riwayat Jurnal Mengajar Saya -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>📋</span> Riwayat Jurnal Mengajar & Presensi
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Daftar rekaman pembelajaran yang telah Anda simpan sebelumnya.</p>
                    </div>
                </div>

                @if ($myJournals->isEmpty())
                    <div class="text-center py-10 px-4 text-gray-500">
                        <span class="text-3xl block mb-2">📂</span>
                        <p class="text-xs">Belum ada riwayat jurnal mengajar yang tersimpan.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-50 text-gray-700 uppercase font-semibold text-[11px] border-y">
                                <tr>
                                    <th class="py-3 px-4">Tanggal & Waktu</th>
                                    <th class="py-3 px-4">Kelas & Ruang</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4">Ringkasan Topik Materi</th>
                                    <th class="py-3 px-4 text-center">Rekap Kehadiran</th>
                                    <th class="py-3 px-4 text-center">Foto Bukti</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($myJournals as $jnl)
                                    @php
                                        $attHadir = $jnl->attendances->where('status', 'Hadir')->count();
                                        $attIzin = $jnl->attendances->where('status', 'Izin')->count();
                                        $attSakit = $jnl->attendances->where('status', 'Sakit')->count();
                                        $attAlpha = $jnl->attendances->where('status', 'Alpha')->count();
                                    @endphp
                                    <tr class="hover:bg-gray-50/80 transition">
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-bold text-gray-900 block">
                                                {{ \Carbon\Carbon::parse($jnl->date)->translatedFormat('d M Y') }}
                                            </span>
                                            <span class="text-[11px] text-gray-500 font-mono">
                                                {{ substr($jnl->schedule->start_time ?? '', 0, 5) }} - {{ substr($jnl->schedule->end_time ?? '', 0, 5) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="font-semibold text-gray-800 block">
                                                {{ $jnl->schedule->schoolClass->class_name ?? 'N/A' }}
                                            </span>
                                            <span class="text-[11px] text-gray-500">
                                                {{ $jnl->schedule->room->room_name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">
                                            {{ $jnl->schedule->subject ?? '-' }}
                                        </td>
                                        <td class="py-3 px-4 max-w-xs truncate text-gray-700" title="{{ $jnl->topic_description }}">
                                            {{ \Illuminate\Support\Str::limit($jnl->topic_description, 50) }}
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1 text-[10px] font-bold">
                                                <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded" title="Hadir">H: {{ $attHadir }}</span>
                                                <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded" title="Izin">I: {{ $attIzin }}</span>
                                                <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded" title="Sakit">S: {{ $attSakit }}</span>
                                                <span class="px-1.5 py-0.5 bg-red-100 text-red-800 rounded" title="Alpha">A: {{ $attAlpha }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            @if ($jnl->photo_path)
                                                <a href="{{ asset('storage/' . $jnl->photo_path) }}" target="_blank" class="inline-block relative group">
                                                    <img src="{{ asset('storage/' . $jnl->photo_path) }}" alt="Foto Kegiatan" class="w-10 h-10 object-cover rounded-lg border border-gray-200 group-hover:scale-110 transition shadow-xs">
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-[11px]">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <a href="{{ route('guru.journals.show', $jnl->id) }}" 
                                               class="inline-flex items-center px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold rounded-md transition text-xs">
                                                Detail →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $myJournals->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
