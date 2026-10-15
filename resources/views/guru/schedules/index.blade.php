<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Jadwal Mengajar Mandiri') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Sistem Blok Teaching Factory (Putaran 1 & Putaran 2)</p>
            </div>
            <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                ← Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Banner Notifikasi Flash -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-lg shadow-sm">
                    <p class="text-emerald-700 font-medium text-sm">✅ {{ session('success') }}</p>
                </div>
            @endif

            <!-- Banner Error Tabrakan / Conflict Prevention -->
            @if ($errors->has('conflict'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                    <div class="flex items-start">
                        <span class="text-red-600 text-xl me-3">⚠️</span>
                        <div>
                            <h4 class="text-red-800 font-bold text-sm">Validasi Bentrok Jadwal (Conflict Prevention)</h4>
                            <p class="text-red-700 text-sm mt-1 font-medium">{{ $errors->first('conflict') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->has('locked'))
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-lg shadow-sm">
                    <p class="text-amber-800 font-medium text-sm">🔒 {{ $errors->first('locked') }}</p>
                </div>
            @endif

            <!-- Status Kunci Jadwal & Tab Pemilihan Putaran -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700">Pilih Tinjauan Putaran:</span>
                    <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50">
                        <a href="{{ route('guru.schedules.index', ['cycle' => 'Putaran 1']) }}" 
                           class="px-4 py-1.5 text-xs font-bold rounded-md transition {{ $selectedCycle === 'Putaran 1' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:text-gray-900' }}">
                            Putaran 1 @if($config->active_cycle === 'Putaran 1') (Aktif) @endif
                        </a>
                        <a href="{{ route('guru.schedules.index', ['cycle' => 'Putaran 2']) }}" 
                           class="px-4 py-1.5 text-xs font-bold rounded-md transition {{ $selectedCycle === 'Putaran 2' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:text-gray-900' }}">
                            Putaran 2 @if($config->active_cycle === 'Putaran 2') (Aktif) @endif
                        </a>
                    </div>
                </div>

                <div>
                    @if ($config->is_schedule_locked)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                            🔒 Jadwal Dikunci Waka (Read Only)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            🔓 Pengisian Mandiri Aktif
                        </span>
                    @endif
                </div>
            </div>

            <!-- Form Tambah Jadwal Baru (Hanya Tampil Jika Jadwal Belum Dikunci) -->
            @if (! $config->is_schedule_locked)
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4 border-b pb-3">
                        <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                            <span>➕</span> Tambah Jadwal Mengajar Mandiri
                        </h3>
                        <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-2.5 py-1 rounded">
                            Target: {{ $selectedCycle }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('guru.schedules.store') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @csrf
                        <input type="hidden" name="cycle_type" value="{{ $selectedCycle }}">

                        <!-- Mata Pelajaran -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Mata Pelajaran</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Contoh: Pemrograman Web" required 
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('subject') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Kelas -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Kelas</label>
                            <select name="class_id" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($classes as $c)
                                    <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->class_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Ruangan -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Ruangan / Lab</label>
                            <select name="room_id" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach ($rooms as $r)
                                    <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>
                                        {{ $r->room_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Hari -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Hari</label>
                            <select name="day_of_week" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Hari --</option>
                                @foreach ($days as $day)
                                    <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                        {{ $day }}
                                    </option>
                                @endforeach
                            </select>
                            @error('day_of_week') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Jam Mulai</label>
                            <input type="time" name="start_time" value="{{ old('start_time', '08:00') }}" required 
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('start_time') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Jam Selesai</label>
                            <input type="time" name="end_time" value="{{ old('end_time', '10:00') }}" required 
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('end_time') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tombol Submit -->
                        <div class="md:col-span-2 flex items-end">
                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-lg transition shadow-sm">
                                💾 Simpan Jadwal & Validasi Bentrok
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-red-50 border border-red-200 p-4 rounded-xl text-center">
                    <p class="text-sm font-semibold text-red-800">
                        🔒 Form Tambah Jadwal Dinonaktifkan: Waka Kurikulum telah mengunci jadwal operasional.
                    </p>
                </div>
            @endif

            <!-- Tabel Jadwal Saya -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">Jadwal Mengajar Saya ({{ $selectedCycle }})</h4>
                        <p class="text-xs text-gray-500">Daftar kelas yang Anda ampu pada siklus ini</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">
                        {{ $mySchedules->count() }} Jadwal
                    </span>
                </div>

                @if ($mySchedules->isEmpty())
                    <div class="p-8 text-center text-gray-500 text-sm">
                        Belum ada jadwal mengajar yang diinput pada {{ $selectedCycle }}.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 text-xs uppercase">Hari</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 text-xs uppercase">Waktu</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 text-xs uppercase">Mata Pelajaran</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 text-xs uppercase">Kelas</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 text-xs uppercase">Ruangan</th>
                                    <th class="px-6 py-3 text-right font-semibold text-gray-600 text-xs uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($mySchedules as $s)
                                    <tr>
                                        <td class="px-6 py-3.5 font-bold text-indigo-700">{{ $s->day_of_week }}</td>
                                        <td class="px-6 py-3.5 font-mono text-xs text-gray-800">
                                            {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                                        </td>
                                        <td class="px-6 py-3.5 font-medium text-gray-900">{{ $s->subject }}</td>
                                        <td class="px-6 py-3.5">
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded">
                                                {{ $s->schoolClass->class_name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-gray-600">{{ $s->room->room_name }}</td>
                                        <td class="px-6 py-3.5 text-right">
                                            @if (! $config->is_schedule_locked)
                                                <form method="POST" action="{{ route('guru.schedules.destroy', $s->id) }}" onsubmit="return confirm('Hapus jadwal ini?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-semibold transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Terkunci</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Bagian Jadwal Keseluruhan Guru (Monitoring Bentrok Terbuka) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">Jadwal Seluruh Sekolah ({{ $selectedCycle }})</h4>
                        <p class="text-xs text-gray-500">Lihat jadwal guru lain untuk referensi pemilihan ruangan dan jam kosong</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">
                        Total {{ $allSchedules->count() }} Terdaftar
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Hari</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Jam</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Guru Pengampu</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Mata Pelajaran</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Kelas</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($allSchedules as $as)
                                <tr class="{{ $as->teacher_id === auth()->id() ? 'bg-indigo-50/50' : '' }}">
                                    <td class="px-4 py-2 font-semibold text-gray-800">{{ $as->day_of_week }}</td>
                                    <td class="px-4 py-2 font-mono text-gray-700">{{ \Carbon\Carbon::parse($as->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($as->end_time)->format('H:i') }}</td>
                                    <td class="px-4 py-2 font-medium {{ $as->teacher_id === auth()->id() ? 'text-indigo-700 font-bold' : 'text-gray-900' }}">
                                        {{ $as->teacher->fullname }}
                                        @if($as->teacher_id === auth()->id()) (Saya) @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-700">{{ $as->subject }}</td>
                                    <td class="px-4 py-2">{{ $as->schoolClass->class_name }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $as->room->room_name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada jadwal yang diinput di {{ $selectedCycle }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
