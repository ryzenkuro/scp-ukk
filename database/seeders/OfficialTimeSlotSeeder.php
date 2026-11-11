<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficialTimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standardSlots = [
            ['name' => 'Jam Ke-1', 'start_time' => '07:15:00', 'end_time' => '08:00:00', 'is_break' => false, 'order' => 1],
            ['name' => 'Jam Ke-2', 'start_time' => '08:00:00', 'end_time' => '08:45:00', 'is_break' => false, 'order' => 2],
            ['name' => 'Jam Ke-3', 'start_time' => '08:45:00', 'end_time' => '09:30:00', 'is_break' => false, 'order' => 3],
            ['name' => 'Istirahat Pagi', 'start_time' => '09:30:00', 'end_time' => '09:45:00', 'is_break' => true, 'order' => 4],
            ['name' => 'Jam Ke-4', 'start_time' => '09:45:00', 'end_time' => '10:30:00', 'is_break' => false, 'order' => 5],
            ['name' => 'Jam Ke-5', 'start_time' => '10:30:00', 'end_time' => '11:15:00', 'is_break' => false, 'order' => 6],
            ['name' => 'Jam Ke-6', 'start_time' => '11:15:00', 'end_time' => '12:00:00', 'is_break' => false, 'order' => 7],
            ['name' => 'Istirahat Siang', 'start_time' => '12:00:00', 'end_time' => '12:30:00', 'is_break' => true, 'order' => 8],
            ['name' => 'Jam Ke-7', 'start_time' => '12:30:00', 'end_time' => '13:15:00', 'is_break' => false, 'order' => 9],
            ['name' => 'Jam Ke-8', 'start_time' => '13:15:00', 'end_time' => '14:00:00', 'is_break' => false, 'order' => 10],
            ['name' => 'Jam Ke-9', 'start_time' => '14:00:00', 'end_time' => '14:45:00', 'is_break' => false, 'order' => 11],
            ['name' => 'Jam Ke-10', 'start_time' => '14:45:00', 'end_time' => '15:30:00', 'is_break' => false, 'order' => 12],
        ];

        DB::transaction(function () use ($standardSlots) {
            // Hapus referensi foreign key sementara jika diperlukan, atau bersihkan slot lama
            DB::table('schedules')->update([
                'start_slot_id' => null,
                'end_slot_id' => null,
            ]);

            TimeSlot::query()->delete();

            foreach ($standardSlots as $slot) {
                TimeSlot::create($slot);
            }

            // Tautkan kembali start_slot_id & end_slot_id pada jadwal yang ada
            $allSlots = TimeSlot::orderBy('order')->get();
            $schedules = DB::table('schedules')->get();

            foreach ($schedules as $sched) {
                $schedStart = substr($sched->start_time, 0, 5);
                $schedEnd = substr($sched->end_time, 0, 5);

                $startSlot = $allSlots->first(function ($s) use ($schedStart) {
                    return substr($s->start_time, 0, 5) === $schedStart;
                });

                $endSlot = $allSlots->first(function ($s) use ($schedEnd) {
                    return substr($s->end_time, 0, 5) === $schedEnd;
                });

                if (! $startSlot) {
                    // Cari slot terdekat yang mencakup start time
                    $startSlot = $allSlots->first(function ($s) use ($schedStart) {
                        return substr($s->start_time, 0, 5) <= $schedStart && substr($s->end_time, 0, 5) > $schedStart;
                    });
                }

                if (! $endSlot) {
                    // Cari slot terdekat yang mencakup end time
                    $endSlot = $allSlots->last(function ($s) use ($schedEnd) {
                        return substr($s->start_time, 0, 5) < $schedEnd && substr($s->end_time, 0, 5) >= $schedEnd;
                    }) ?? $startSlot;
                }

                if ($startSlot || $endSlot) {
                    DB::table('schedules')->where('id', $sched->id)->update([
                        'start_slot_id' => $startSlot?->id,
                        'end_slot_id' => $endSlot?->id,
                    ]);
                }
            }
        });
    }
}

