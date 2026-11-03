<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Services\ScheduleImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Putaran'),
            'putaran_1' => Tab::make('Putaran 1')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 1')),
            'putaran_2' => Tab::make('Putaran 2')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 2')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Jadwal')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('import_csv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Berkas Jadwal (.csv)')
                        ->disk('local')
                        ->directory('temp-schedule-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required()
                        ->helperText('Unggah berkas CSV sesuai format template. Sistem otomatis memvalidasi bentrok 3D & auto-create akun guru baru.'),
                ])
                ->action(function (array $data, ScheduleImportService $importer): void {
                    $relativePath = $data['file'];
                    $absolutePath = Storage::disk('local')->path($relativePath);

                    if (! file_exists($absolutePath)) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal mengunggah file.')
                            ->send();
                        return;
                    }

                    $uploadedFile = new UploadedFile(
                        $absolutePath,
                        basename($absolutePath),
                        mime_content_type($absolutePath) ?: 'text/csv',
                        null,
                        true
                    );

                    $result = $importer->importCsv($uploadedFile);

                    // Bersihkan berkas sementara
                    Storage::disk('local')->delete($relativePath);

                    if (! empty($result['errors'])) {
                        Notification::make()
                            ->warning()
                            ->title("Import Selesai dengan Catatan ({$result['success_count']} berhasil)")
                            ->body(implode("\n", array_slice($result['errors'], 0, 5)))
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->success()
                            ->title('Import Jadwal Berhasil!')
                            ->body("Sebanyak {$result['success_count']} jadwal pembelajaran berhasil ditambahkan.")
                            ->send();
                    }
                }),

            Actions\Action::make('download_template')
                ->label('Unduh Template CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $headers = [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="template_import_jadwal.csv"',
                    ];

                    $content = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                             . "Budi Santoso S.Kom,198001012005011001,Pemodelan Perangkat Lunak,XII RPL 1,Lab RPL 1,Senin,07:00:00,09:30:00,Putaran 1\n"
                             . "Siti Aminah M.Kom,198502022010012002,Basis Data,XII RPL 1,Lab RPL 2,Senin,09:45:00,12:15:00,Putaran 1\n";

                    return response()->stream(function () use ($content) {
                        echo $content;
                    }, 200, $headers);
                }),
        ];
    }
}
