<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>👨‍🏫</span> {{ __('Portal Guru - School Class Progress') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Sistem Manajemen Pembelajaran Blok Teaching Factory</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                Guru Mata Pelajaran
            </span>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- ⚠️ ALERT REMINDER: Peringatan Keterlambatan Pengisian Jurnal (>30 Menit) -->
            @if ($overdueSchedules->isNotEmpty())
                <div class="bg-red-50 border-l-4 border-red-600 p-5 rounded-2xl shadow-sm border border-red-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="text-3xl shrink-0">⚠️</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-red-900 font-extrabold text-base">
                                        Peringatan Keterlambatan Pengisian Jurnal
                                    </h4>
                                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-red-200 text-red-900 border border-red-300">
                                        {{ $overdueSchedules->count() }} Sesi Terlambat (>30 Menit)
                                    </span>
                                </div>
                                <div class="mt-2 space-y-1.5">
                                    @foreach ($overdueSchedules as $overdue)
                                        <p class="text-red-800 text-xs sm:text-sm">
                                            Anda belum mengisi jurnal untuk mata pelajaran 
                                            <strong class="font-bold underline">{{ $overdue->subject }}</strong> di kelas 
                                            <strong class="font-bold">{{ $overdue->schoolClass->class_name }}</strong> 
                                            (Berakhir jam <strong class="font-mono">{{ substr($overdue->end_time, 0, 5) }}</strong>). 
                                            Harap segera isi jurnal!
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            @if ($overdueSchedules->count() === 1)
                                <a href="{{ route('guru.journals.create', $overdueSchedules->first()->id) }}" 
                                   class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                                    <span>📝</span> Buka Form Jurnal Sekarang →
                                </a>
                            @else
                                <a href="{{ route('guru.journals.index') }}" 
                                   class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                                    <span>📝</span> Buka Daftar Sesi Jurnal →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Card Profil & Status Siklus Global -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Selamat Datang, {{ $teacher->fullname }}!</h3>
                    <p class="text-sm text-gray-600 mt-1 flex flex-wrap items-center gap-2">
                        <span>Siklus Aktif:</span>
                        <span class="font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-md border border-indigo-200">
                            {{ $config->active_cycle }}
                        </span>
                        <span>• Status Jadwal:</span> 
                        @if ($config->is_schedule_locked)
                            <span class="text-red-700 font-bold bg-red-50 px-2.5 py-0.5 rounded-md border border-red-200">
                                🔒 Terkunci oleh Waka
                            </span>
                        @else
                            <span class="text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                                🔓 Masa Input Jadwal Aktif
                            </span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <a href="{{ route('guru.schedules.index') }}" class="w-full md:w-auto text-center inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition">
                        📅 Atur Jadwal
                    </a>
                    <a href="{{ route('guru.journals.index') }}" class="w-full md:w-auto text-center inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                        📝 Jurnal & Presensi
                    </a>
                </div>
            </div>

            <!-- Ringkasan Sesi Pembelajaran Hari Ini -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <div>
                        <h4 class="font-bold text-base text-gray-900 flex items-center gap-2">
                            <span>🕒</span> Sesi Mengajar Hari Ini ({{ $currentDayName }}, {{ \Carbon\Carbon::parse($today)->translatedFormat('d M Y') }})
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">Pantau pelaksanaan kelas dan pengisian presensi siswa hari ini.</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg">
                        {{ $todaySchedules->count() }} Sesi
                    </span>
                </div>

                @if ($todaySchedules->isEmpty())
                    <div class="text-center py-10 px-4 text-gray-500 text-xs">
                        <span class="text-3xl block mb-2">🏖️</span>
                        <p class="font-medium text-gray-700">Tidak ada jadwal mengajar pada hari {{ $currentDayName }} di {{ $config->active_cycle }}.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($todaySchedules as $sch)
                            @php
                                $journalToday = $sch->journals->first();
                                $isFilled = (bool) $journalToday;
                                $isOverdue = ! $isFilled && $sch->end_time <= $cutoffTime;
                            @endphp
                            <div class="rounded-xl border p-4 transition flex flex-col justify-between {{ $isFilled ? 'border-emerald-200 bg-emerald-50/20' : ($isOverdue ? 'border-red-300 bg-red-50/30 ring-1 ring-red-400' : 'border-gray-200 bg-white') }}">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        @if ($isFilled)
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                                                ✅ Jurnal Terisi
                                            </span>
                                        @elseif ($isOverdue)
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-red-100 text-red-800 animate-pulse">
                                                ⚠️ Terlambat (>30 Mnt)
                                            </span>
                                        @else
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                                                ⏳ Belum Diisi
                                            </span>
                                        @endif

                                        <span class="text-xs font-mono font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                                            {{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }}
                                        </span>
                                    </div>

                                    <h5 class="font-bold text-sm text-gray-900 mt-1">{{ $sch->subject }}</h5>
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ $sch->schoolClass->class_name }} • {{ $sch->room->room_name }}
                                    </p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-100">
                                    @if ($isFilled)
                                        <a href="{{ route('guru.journals.show', $journalToday->id) }}" class="text-center block w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">
                                            Lihat Rekap Presensi →
                                        </a>
                                    @else
                                        <a href="{{ route('guru.journals.create', $sch->id) }}" class="text-center block w-full py-1.5 {{ $isOverdue ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                            Isi Jurnal & Presensi →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Quick Cards Menu -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Modul Jadwal Mengajar -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl">📅</span>
                            <span class="text-xs font-bold px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-full">
                                {{ $mySchedulesCount }} Sesi Terdaftar
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Manajemen Jadwal Mandiri</h4>
                        <p class="text-xs text-gray-600">
                            Atur jadwal mengajar Anda sesuai SK. Dilengkapi validasi otomatis anti-bentrok ruangan, waktu, dan kelas.
                        </p>
                    </div>
                    <div class="mt-5">
                        <a href="{{ route('guru.schedules.index') }}" class="block text-center w-full py-2 bg-gray-900 hover:bg-black text-white text-xs font-semibold rounded-xl transition">
                            Buka Pengaturan Jadwal →
                        </a>
                    </div>
                </div>

                <!-- Modul Jurnal Mengajar Realtime -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl">📝</span>
                            <span class="text-xs font-bold px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full">
                                Modul Presensi & Jurnal
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Buka Kelas & Jurnal Mengajar</h4>
                        <p class="text-xs text-gray-600">
                            Isi materi mengajar harian, unggah foto kegiatan kelas realtime, dan lakukan sinkronisasi presensi otomatis siswa.
                        </p>
                    </div>
                    <div class="mt-5">
                        <a href="{{ route('guru.journals.index') }}" class="block text-center w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                            Buka Jurnal & Isi Presensi →
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
