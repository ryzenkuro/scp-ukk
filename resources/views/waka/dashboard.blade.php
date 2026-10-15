<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Panel Waka Kurikulum - School Class Progress') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                Waka Kurikulum
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded shadow-sm">
                    <div class="flex items-center">
                        <span class="text-emerald-700 font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                    <ul class="list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Section 1: Konfigurasi Sistem (Cycle Toggle & Lock Schedule) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Card Cycle Toggle -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">Siklus Blok Pembelajaran</span>
                            <span class="px-3 py-1 text-sm font-bold rounded-full {{ $config->active_cycle === 'Putaran 1' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $config->active_cycle }}
                            </span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $config->active_cycle }} Aktif</h3>
                        <p class="text-sm text-gray-600 mb-6">
                            Mengatur siklus pembelajaran blok 2-mingguan. Seluruh pencarian jadwal, dashboard guru, dan presensi mengikuti putaran yang aktif.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('waka.config.toggle-cycle') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg transition shadow-sm">
                            🔄 Ganti ke {{ $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1' }}
                        </button>
                    </form>
                </div>

                <!-- Card Lock Schedule -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">Status Kunci Jadwal (Lock Schedule)</span>
                            <span class="px-3 py-1 text-sm font-bold rounded-full {{ $config->is_schedule_locked ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
                            </span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ $config->is_schedule_locked ? 'Jadwal Dikunci' : 'Jadwal Terbuka' }}
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">
                            {{ $config->is_schedule_locked 
                                ? 'Guru TIDAK DAPAT menambah, mengedit, atau menghapus jadwal mandiri (form dan endpoint API terkunci).' 
                                : 'Masa input mandiri aktif: Guru bebas mengatur jadwal mengajar sesuai SK pembagian tugas.' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('waka.config.toggle-lock') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 {{ $config->is_schedule_locked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-medium text-sm rounded-lg transition shadow-sm">
                            {{ $config->is_schedule_locked ? '🔓 Buka Kunci Jadwal' : '🔒 Kunci Semua Jadwal' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Section 2: Ringkasan Master Data -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold uppercase text-gray-500">Total Kelas</span>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $classes->count() }}</p>
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach ($classes as $c)
                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">{{ $c->class_name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold uppercase text-gray-500">Total Ruangan Lab/Teori</span>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $rooms->count() }}</p>
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach ($rooms as $r)
                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">{{ $r->room_name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold uppercase text-gray-500">Total Akun Terdaftar</span>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ \App\Models\User::count() }}</p>
                    <p class="text-xs text-gray-500 mt-3">Mencakup Waka, Guru, Kesiswaan, Siswa, & Ortu</p>
                </div>
            </div>

            <!-- Section 3: Formulir Tambah Master Data Ringkas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Tambah Kelas -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">➕ Tambah Kelas</h4>
                    <form method="POST" action="{{ route('waka.classes.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <input type="text" name="class_name" placeholder="Nama Kelas (contoh: XI RPL 2)" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">Simpan Kelas</button>
                    </form>
                </div>

                <!-- Tambah Ruangan -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">➕ Tambah Ruangan</h4>
                    <form method="POST" action="{{ route('waka.rooms.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <input type="text" name="room_name" placeholder="Nama Ruang (contoh: Lab RPL 3)" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">Simpan Ruangan</button>
                    </form>
                </div>

                <!-- Tambah User Baru -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">➕ Tambah Pengguna</h4>
                    <form method="POST" action="{{ route('waka.users.store') }}" class="space-y-3">
                        @csrf
                        <select name="role_id" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="fullname" placeholder="Nama Lengkap" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" name="username" placeholder="Username" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="password" name="password" placeholder="Password (min 4 kar.)" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <select name="class_id" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Kelas (Opsional/Siswa) --</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">Simpan Pengguna</button>
                    </form>
                </div>
            </div>

            <!-- Section 4: Tabel Pengguna Terdaftar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900 text-sm">Daftar Akun Pengguna</h4>
                    <span class="text-xs text-gray-500">Menampilkan 10 data terbaru</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 text-xs uppercase">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 text-xs uppercase">Username</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 text-xs uppercase">Peran (Role)</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 text-xs uppercase">Kelas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $user->fullname }}</td>
                                    <td class="px-6 py-3 text-gray-600 font-mono text-xs">{{ $user->username }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full font-medium 
                                            @if($user->role->role_name === 'waka_kurikulum') bg-indigo-100 text-indigo-700
                                            @elseif($user->role->role_name === 'guru') bg-blue-100 text-blue-700
                                            @elseif(in_array($user->role->role_name, ['wali_kelas', 'guru_bk'])) bg-amber-100 text-amber-700
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ $user->role->role_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 text-xs">{{ $user->schoolClass?->class_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $users->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
