<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Portal Siswa & Orang Tua') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                {{ auth()->user()->role->role_name }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Selamat Datang, {{ auth()->user()->fullname }}!</h3>
                <p class="text-sm text-gray-600">
                    Akun: <span class="font-semibold text-emerald-600">{{ auth()->user()->username }}</span>
                    @if(auth()->user()->schoolClass)
                        | Kelas: <span class="font-semibold text-emerald-600">{{ auth()->user()->schoolClass->class_name }}</span>
                    @endif
                </p>
                <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-800">
                    ℹ️ Modul Pengajuan Surat Izin & Pemantauan Kehadiran akan aktif di Fase 4.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
