<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Models\Schedule;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;

        // Validasi Anti-Bentrok 3 Dimensi (BR-001)
        $conflict = Schedule::where('cycle_type', $data['cycle_type'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->where(function ($q) use ($data) {
                $q->where('room_id', $data['room_id'])
                  ->orWhere('class_id', $data['class_id'])
                  ->orWhere('teacher_id', $data['teacher_id']);
            })->first();

        if ($conflict) {
            $conflictDetails = [];
            if ($conflict->room_id == $data['room_id']) {
                $conflictDetails[] = "Ruangan {$conflict->room->room_name}";
            }
            if ($conflict->class_id == $data['class_id']) {
                $conflictDetails[] = "Kelas {$conflict->schoolClass->class_name}";
            }
            if ($conflict->teacher_id == $data['teacher_id']) {
                $conflictDetails[] = "Guru {$conflict->teacher->fullname}";
            }

            $detailString = implode(', ', $conflictDetails);

            Notification::make()
                ->danger()
                ->title('Jadwal Bentrok! (BR-001)')
                ->body("Terdapat tabrakan jadwal pada {$detailString} pada hari {$data['day_of_week']} jam {$conflict->start_time} - {$conflict->end_time} ({$data['cycle_type']}).")
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
