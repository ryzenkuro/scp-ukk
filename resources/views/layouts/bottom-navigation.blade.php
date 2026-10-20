@auth
    @php
        $userRole = Auth::user()->role?->role_name;
    @endphp

    <!-- Sticky Bottom Navigation Bar Khusus Tampilan Smartphone (Mobile-First) -->
    <nav class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 shadow-xl px-2 py-1.5 transition-all">
        <div class="grid {{ $userRole === 'guru' ? 'grid-cols-4' : 'grid-cols-3' }} items-center text-center">

            @if ($userRole === 'guru')
                <!-- Guru: Beranda -->
                <a href="{{ route('guru.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>

                <!-- Guru: Jadwal -->
                <a href="{{ route('guru.schedules.index') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.schedules.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📅</span>
                    <span class="text-[10px] mt-0.5">Jadwal</span>
                </a>

                <!-- Guru: Jurnal & Presensi -->
                <a href="{{ route('guru.journals.index') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.journals.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📝</span>
                    <span class="text-[10px] mt-0.5">Jurnal</span>
                </a>

                <!-- Guru: Profil -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>

            @elseif (in_array($userRole, ['wali_kelas', 'guru_bk']))
                <!-- Kesiswaan: Beranda -->
                <a href="{{ route('kesiswaan.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('kesiswaan.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>

                <!-- Kesiswaan: Verifikasi Izin -->
                <a href="{{ route('kesiswaan.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('kesiswaan.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📋</span>
                    <span class="text-[10px] mt-0.5">Verifikasi</span>
                </a>

                <!-- Kesiswaan: Profil -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>

            @elseif (in_array($userRole, ['siswa', 'orang_tua']))
                <!-- Siswa / Ortu: Beranda -->
                <a href="{{ route('portal.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('portal.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>

                <!-- Siswa / Ortu: Pengajuan Izin -->
                <a href="{{ route('portal.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('portal.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📝</span>
                    <span class="text-[10px] mt-0.5">Ajukan Izin</span>
                </a>

                <!-- Siswa / Ortu: Profil -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>

            @elseif ($userRole === 'waka_kurikulum')
                <!-- Waka: Panel Admin Filament -->
                <a href="/admin" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl text-gray-500 hover:text-gray-800 transition">
                    <span class="text-xl">⚙️</span>
                    <span class="text-[10px] mt-0.5">Filament</span>
                </a>

                <!-- Waka: Dashboard Blade -->
                <a href="{{ route('waka.dashboard') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('waka.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📊</span>
                    <span class="text-[10px] mt-0.5">Dashboard</span>
                </a>

                <!-- Waka: Profil -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>
            @endif

        </div>
    </nav>
@endauth
