<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Portal Guru - School Class Progress') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Guru Mata Pelajaran
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $config = \App\Models\SystemConfiguration::current();
                $mySchedulesCount = \App\Models\Schedule::where('teacher_id', auth()->id())
                    ->where('cycle_type', $config->active_cycle)
                    ->count();
            @endphp

            <!-- Card Profil & Status Siklus Global -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Selamat Datang, {{ auth()->user()->fullname }}!</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Siklus Aktif Sekolah: <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ $config->active_cycle }}</span>
                        | Status Jadwal: 
                        @if ($config->is_schedule_locked)
                            <span class="text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded">🔒 Terkunci oleh Waka</span>
                        @else
                            <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded">🔓 Masa Input Jadwal Aktif</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('guru.schedules.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        📅 Kelola Jadwal Mengajar
                    </a>
                </div>
            </div>

            <!-- Quick Cards Menu -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Modul Jadwal Mengajar -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl">📅</span>
                            <span class="text-xs font-bold px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full">
                                {{ $mySchedulesCount }} Kelas Terdaftar
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Manajemen Jadwal Mandiri</h4>
                        <p class="text-sm text-gray-600">
                            Atur jadwal mengajar Anda sesuai SK. Dilengkapi validasi otomatis anti-bentrok ruangan, waktu, dan kelas.
                        </p>
                    </div>
                    <div class="mt-5">
                        <a href="{{ route('guru.schedules.index') }}" class="block text-center w-full py-2 bg-gray-900 hover:bg-black text-white text-xs font-semibold rounded-lg transition">
                            Buka Pengaturan Jadwal →
                        </a>
                    </div>
                </div>

                <!-- Modul Jurnal Mengajar Realtime (Fase 5) -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl">📝</span>
                            <span class="text-xs font-bold px-2 py-1 bg-amber-100 text-amber-800 rounded-full">
                                Modul Presensi
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Buka Kelas & Jurnal Mengajar</h4>
                        <p class="text-sm text-gray-600">
                            Isi materi mengajar harian, unggah foto kegiatan kelas realtime, dan lakukan sinkronisasi presensi otomatis siswa.
                        </p>
                    </div>
                    <div class="mt-5">
                        <span class="block text-center w-full py-2 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed">
                            Akan Aktif di Fase 5 (Jurnal & Presensi)
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
