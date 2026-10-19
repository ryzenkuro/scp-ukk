<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Toggle Siklus Blok -->
        <x-filament::section>
            <x-slot name="heading">
                Siklus Pembelajaran Blok
            </x-slot>
            <x-slot name="description">
                Atur siklus pembelajaran 2-mingguan. Seluruh jadwal guru dan presensi otomatis mengikuti siklus aktif ini.
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase">Status Siklus Saat Ini</span>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $config->active_cycle }}
                        </div>
                    </div>
                    <span class="px-3 py-1 text-xs font-bold rounded-full {{ $config->active_cycle === 'Putaran 1' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' }}">
                        Aktif
                    </span>
                </div>

                <x-filament::button wire:click="toggleCycle" icon="heroicon-m-arrow-path" color="info" size="lg" class="w-full">
                    Ganti Siklus ke {{ $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1' }}
                </x-filament::button>
            </div>
        </x-filament::section>

        <!-- Card 2: Lock Schedule -->
        <x-filament::section>
            <x-slot name="heading">
                Status Kunci Jadwal (Lock Schedule)
            </x-slot>
            <x-slot name="description">
                Kunci jadwal guru setelah batas waktu input berakhir agar jadwal tidak dapat dimanipulasi secara mandiri.
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-xl {{ $config->is_schedule_locked ? 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800/30' : 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/30' }} border">
                    <div>
                        <span class="text-xs font-semibold {{ $config->is_schedule_locked ? 'text-red-600' : 'text-emerald-600' }} uppercase">Status Pengisian Jadwal</span>
                        <div class="text-2xl font-bold {{ $config->is_schedule_locked ? 'text-red-800 dark:text-red-400' : 'text-emerald-800 dark:text-emerald-400' }} mt-1">
                            {{ $config->is_schedule_locked ? 'Terkunci 🔒' : 'Terbuka 🔓' }}
                        </div>
                    </div>
                    <span class="text-xs {{ $config->is_schedule_locked ? 'text-red-700' : 'text-emerald-700' }} font-medium">
                        {{ $config->is_schedule_locked ? 'Guru Tidak Bisa Edit' : 'Masa Input Aktif' }}
                    </span>
                </div>

                @if($config->is_schedule_locked)
                    <x-filament::button wire:click="toggleLock" icon="heroicon-m-lock-open" color="success" size="lg" class="w-full">
                        Buka Kunci Jadwal (Izinkan Guru Input)
                    </x-filament::button>
                @else
                    <x-filament::button wire:click="toggleLock" icon="heroicon-m-lock-closed" color="danger" size="lg" class="w-full">
                        Kunci Seluruh Jadwal Sekarang
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
