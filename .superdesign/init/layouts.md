# Layouts — School Class Progress (SCP)

Two root layouts (Breeze): `x-app-layout` (authed) and `x-guest-layout` (auth). App layout composes: top nav (`layouts/navigation`) + optional `$header` white band + `$slot` main + mobile bottom nav (`layouts/bottom-navigation`). Nav is ROLE-CONDITIONAL server-side (`Auth::user()->role?->role_name`). Roles: `guru`, `wali_kelas`, `guru_bk`, `siswa`, `orang_tua`, `waka_kurikulum`, `super_admin`.

## resources/views/layouts/app.blade.php
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-24 sm:pb-8">
                {{ $slot }}
            </main>

            <!-- Sticky Bottom Navigation Bar (Mobile Devices) -->
            @include('layouts.bottom-navigation')
        </div>
    </body>
</html>
```

## resources/views/layouts/guest.blade.php
Auth pages: centered column on `bg-gray-100`, Laravel logo on top (w-20), white `sm:rounded-lg shadow-md` card `sm:max-w-md` holding the form slot.
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
```

## resources/views/layouts/navigation.blade.php
Desktop top nav: h-16 white bar, Laravel logo → dashboard, `x-nav-link` items **per role**: guru → Dashboard / Jadwal Mengajar / Jurnal & Presensi; wali_kelas,guru_bk → Verifikasi Perizinan; siswa,orang_tua → Pengajuan Izin; waka_kurikulum → "Panel Waka (Filament)" (/admin); staff roles also get Rekap Presensi. Right: user dropdown (Profile, Log Out). Mobile (<sm): hamburger (Alpine `x-data={open:false}`) → stacked `x-responsive-nav-link` list + user block (fullname, @username (role), Profile, Log Out).
```blade
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'guru.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->role?->role_name === 'guru')
                        <x-nav-link :href="route('guru.schedules.index')" :active="request()->routeIs('guru.schedules.*')">
                            {{ __('Jadwal Mengajar') }}
                        </x-nav-link>
                        <x-nav-link :href="route('guru.journals.index')" :active="request()->routeIs('guru.journals.*')">
                            {{ __('Jurnal & Presensi') }}
                        </x-nav-link>
                    @elseif (in_array(Auth::user()->role?->role_name, ['wali_kelas', 'guru_bk']))
                        <x-nav-link :href="route('kesiswaan.dashboard')" :active="request()->routeIs('kesiswaan.*')">
                            {{ __('Verifikasi Perizinan') }}
                        </x-nav-link>
                    @elseif (in_array(Auth::user()->role?->role_name, ['siswa', 'orang_tua']))
                        <x-nav-link :href="route('portal.dashboard')" :active="request()->routeIs('portal.*')">
                            {{ __('Pengajuan Izin') }}
                        </x-nav-link>
                    @elseif (Auth::user()->role?->role_name === 'waka_kurikulum')
                        <x-nav-link href="/admin">
                            {{ __('Panel Waka (Filament)') }}
                        </x-nav-link>
                    @endif

                    @if (in_array(Auth::user()->role?->role_name, ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum', 'super_admin']))
                        <x-nav-link :href="route('reports.attendance.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Rekap Presensi') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->fullname ?? Auth::user()->username }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'guru.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (Auth::user()->role?->role_name === 'guru')
                <x-responsive-nav-link :href="route('guru.schedules.index')" :active="request()->routeIs('guru.schedules.*')">
                    {{ __('Jadwal Mengajar') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('guru.journals.index')" :active="request()->routeIs('guru.journals.*')">
                    {{ __('Jurnal & Presensi') }}
                </x-responsive-nav-link>
            @elseif (in_array(Auth::user()->role?->role_name, ['wali_kelas', 'guru_bk']))
                <x-responsive-nav-link :href="route('kesiswaan.dashboard')" :active="request()->routeIs('kesiswaan.*')">
                    {{ __('Verifikasi Perizinan') }}
                </x-responsive-nav-link>
            @elseif (in_array(Auth::user()->role?->role_name, ['siswa', 'orang_tua']))
                <x-responsive-nav-link :href="route('portal.dashboard')" :active="request()->routeIs('portal.*')">
                    {{ __('Pengajuan Izin') }}
                </x-responsive-nav-link>
            @elseif (Auth::user()->role?->role_name === 'waka_kurikulum')
                <x-responsive-nav-link href="/admin">
                    {{ __('Panel Waka (Filament)') }}
                </x-responsive-nav-link>
            @endif

            @if (in_array(Auth::user()->role?->role_name, ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum', 'super_admin']))
                <x-responsive-nav-link :href="route('reports.attendance.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Rekap Presensi') }}
                </x-responsive-nav-link>
            @endif
        </div>

            <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->fullname }}</div>
                <div class="font-medium text-sm text-gray-500">@<span>{{ Auth::user()->username }}</span> ({{ Auth::user()->role->role_name ?? '' }})</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
```

## resources/views/layouts/bottom-navigation.blade.php
Mobile-only (`sm:hidden`) fixed bottom bar, `bg-white/95 backdrop-blur-md border-t shadow-xl`; grid-cols-4 for guru / grid-cols-3 others; role variants (guru: 🏠Beranda/📅Jadwal/📝Jurnal/👤Profil; wali&bk: 🏠/📋Verifikasi/👤; siswa&ortu: 🏠/📝Ajukan Izin/👤; waka: ⚙️Filament(/admin)/📊Dashboard/👤). Active = `text-indigo-600 font-bold`, inactive `text-gray-500`. Icons are EMOJI (`text-xl`) + label `text-[10px]`.
```blade
@auth
    @php
        $userRole = Auth::user()->role?->role_name;
    @endphp

    <nav class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 shadow-xl px-2 py-1.5 transition-all">
        <div class="grid {{ $userRole === 'guru' ? 'grid-cols-4' : 'grid-cols-3' }} items-center text-center">
            @if ($userRole === 'guru')
                <a href="{{ route('guru.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>
                <a href="{{ route('guru.schedules.index') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.schedules.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📅</span>
                    <span class="text-[10px] mt-0.5">Jadwal</span>
                </a>
                <a href="{{ route('guru.journals.index') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('guru.journals.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📝</span>
                    <span class="text-[10px] mt-0.5">Jurnal</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>
            @elseif (in_array($userRole, ['wali_kelas', 'guru_bk']))
                <a href="{{ route('kesiswaan.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('kesiswaan.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>
                <a href="{{ route('kesiswaan.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('kesiswaan.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📋</span>
                    <span class="text-[10px] mt-0.5">Verifikasi</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>
            @elseif (in_array($userRole, ['siswa', 'orang_tua']))
                <a href="{{ route('portal.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('portal.dashboard', 'dashboard') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">🏠</span>
                    <span class="text-[10px] mt-0.5">Beranda</span>
                </a>
                <a href="{{ route('portal.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('portal.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📝</span>
                    <span class="text-[10px] mt-0.5">Ajukan Izin</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>
            @elseif ($userRole === 'waka_kurikulum')
                <a href="/admin" class="flex flex-col items-center justify-center py-1 rounded-xl text-gray-500 hover:text-gray-800 transition">
                    <span class="text-xl">⚙️</span>
                    <span class="text-[10px] mt-0.5">Filament</span>
                </a>
                <a href="{{ route('waka.dashboard') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('waka.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">📊</span>
                    <span class="text-[10px] mt-0.5">Dashboard</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-1 rounded-xl transition {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                    <span class="text-xl">👤</span>
                    <span class="text-[10px] mt-0.5">Profil</span>
                </a>
            @endif
        </div>
    </nav>
@endauth
```
