<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Panel Tim Kesiswaan (Wali Kelas & Guru BK)') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                {{ auth()->user()->role->role_name }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Selamat Datang, {{ auth()->user()->fullname }}!</h3>
                <p class="text-sm text-gray-600">
                    Kelas Bimbingan / Perwalian: <span class="font-semibold text-amber-600">{{ auth()->user()->schoolClass?->class_name ?? 'Semua Kelas (BK)' }}</span>.
                </p>
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    ℹ️ Modul Verifikasi Surat Izin / Sakit Siswa akan aktif di Fase 4.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
