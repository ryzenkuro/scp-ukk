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
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Selamat Datang, {{ auth()->user()->fullname }}!</h3>
                <p class="text-sm text-gray-600">
                    Siklus Aktif Saat Ini: <span class="font-semibold text-indigo-600">{{ \App\Models\SystemConfiguration::current()->active_cycle }}</span>.
                </p>
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    ℹ️ Modul Manajemen Jadwal Mandiri & Buka Jurnal Kelas akan aktif di Fase 3 & Fase 5.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
