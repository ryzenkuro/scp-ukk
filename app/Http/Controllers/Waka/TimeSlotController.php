<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimeSlotController extends Controller
{
    /**
     * Tampilkan daftar master Jam Pembelajaran Sekolah
     */
    public function index(): Response
    {
        $config = SystemConfiguration::current();
        $timeSlots = TimeSlot::orderBy('order')->orderBy('start_time')->get();

        return Inertia::render('Waka/JamPelajaran/Index', [
            'timeSlots' => $timeSlots,
            'activeCycle' => (int) ($config->active_cycle ?? 1),
        ]);
    }

    /**
     * Simpan jam pembelajaran baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_break' => ['boolean'],
            'order' => ['required', 'integer', 'min:1'],
        ], [
            'end_time.after' => 'Jam selesai harus lebih akhir daripada jam mulai.',
        ]);

        TimeSlot::create([
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_break' => $request->boolean('is_break'),
            'order' => $validated['order'],
        ]);

        return back()->with('success', "Jam Pembelajaran \"{$validated['name']}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui jam pembelajaran
     */
    public function update(Request $request, TimeSlot $timeSlot): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
            'is_break' => ['boolean'],
            'order' => ['required', 'integer', 'min:1'],
        ], [
            'end_time.after' => 'Jam selesai harus lebih akhir daripada jam mulai.',
        ]);

        $timeSlot->update([
            'name' => $validated['name'],
            'start_time' => substr($validated['start_time'], 0, 5),
            'end_time' => substr($validated['end_time'], 0, 5),
            'is_break' => $request->boolean('is_break'),
            'order' => $validated['order'],
        ]);

        return back()->with('success', "Jam Pembelajaran \"{$timeSlot->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus jam pembelajaran (terisolasi hanya pada master jam pembelajaran)
     */
    public function destroy(TimeSlot $timeSlot): RedirectResponse
    {
        $name = $timeSlot->name;

        DB::transaction(function () use ($timeSlot) {
            // Null-kan referensi slot pada jadwal agar tidak menghapus atau mengubah data jadwal sekolah
            DB::table('schedules')->where('start_slot_id', $timeSlot->id)->update(['start_slot_id' => null]);
            DB::table('schedules')->where('end_slot_id', $timeSlot->id)->update(['end_slot_id' => null]);

            $timeSlot->delete();
        });

        return back()->with('success', "Jam Pembelajaran \"{$name}\" berhasil dihapus.");
    }

    /**
     * Download template import master jam pembelajaran Excel
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Urutan',
            'Nama Jam / Sesi',
            'Jam Mulai',
            'Jam Selesai',
            'Tipe',
        ], null, 'A1');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template_import_jam_pelajaran.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download contoh file import master jam pembelajaran
     */
    public function contoh(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Urutan',
            'Nama Jam / Sesi',
            'Jam Mulai',
            'Jam Selesai',
            'Tipe',
        ], null, 'A1');

        $sheet->fromArray([
            [1, 'Jam Ke-1', '07:00', '07:45', 'Jam KBM'],
            [2, 'Jam Ke-2', '07:45', '08:30', 'Jam KBM'],
            [3, 'Jam Ke-3', '08:30', '09:15', 'Jam KBM'],
            [4, 'Istirahat Pagi', '09:15', '09:45', 'Istirahat'],
            [5, 'Jam Ke-4', '09:45', '10:30', 'Jam KBM'],
            [6, 'Jam Ke-5', '10:30', '11:15', 'Jam KBM'],
            [7, 'Jam Ke-6', '11:15', '12:00', 'Jam KBM'],
        ], null, 'A2');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'contoh_import_jam_pelajaran_7_sesi.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import master jam pembelajaran dari file Excel / CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'replace_existing' => ['sometimes', 'boolean'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.',
            ]);
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Gagal membaca file Excel/CSV: ' . $e->getMessage(),
            ]);
        }

        if (empty($sheetRows)) {
            throw ValidationException::withMessages([
                'file' => 'File Excel/CSV kosong.',
            ]);
        }

        $headerAliases = [
            'urutan' => 'order',
            'no' => 'order',
            'order' => 'order',
            'nama jam / sesi' => 'name',
            'nama jam' => 'name',
            'nama sesi' => 'name',
            'nama' => 'name',
            'name' => 'name',
            'sesi' => 'name',
            'jam mulai' => 'start_time',
            'mulai' => 'start_time',
            'start' => 'start_time',
            'start_time' => 'start_time',
            'jam selesai' => 'end_time',
            'selesai' => 'end_time',
            'end' => 'end_time',
            'end_time' => 'end_time',
            'tipe' => 'type',
            'type' => 'type',
            'keterangan' => 'type',
            'istirahat' => 'type',
            'is_break' => 'type',
        ];

        $colIdx = [];
        $dataRows = [];

        foreach ($sheetRows as $idx => $row) {
            $matched = [];
            foreach ($row as $ci => $val) {
                $norm = mb_strtolower(trim((string) $val));
                $norm = ltrim($norm, "\xEF\xBB\xBF");
                if (isset($headerAliases[$norm])) {
                    $matched[$headerAliases[$norm]] = $ci;
                }
            }
            if (isset($matched['name']) && (isset($matched['start_time']) || isset($matched['order']))) {
                $colIdx = $matched;
                $dataRows = array_slice($sheetRows, $idx + 1);
                break;
            }
        }

        if (empty($colIdx)) {
            $defaultOrder = ['order', 'name', 'start_time', 'end_time', 'type'];
            foreach ($defaultOrder as $k => $f) {
                $colIdx[$f] = $k;
            }
            $dataRows = array_slice($sheetRows, 1);
        }

        $importedCount = 0;
        $shouldReplace = $request->boolean('replace_existing');

        DB::transaction(function () use ($dataRows, $colIdx, $shouldReplace, &$importedCount) {
            if ($shouldReplace) {
                DB::table('schedules')->update([
                    'start_slot_id' => null,
                    'end_slot_id' => null,
                ]);
                TimeSlot::query()->delete();
            }

            $currentMaxOrder = TimeSlot::max('order') ?? 0;

            foreach ($dataRows as $idx => $row) {
                $name = trim((string) ($row[$colIdx['name']] ?? ''));
                if ($name === '') {
                    continue;
                }

                $rawOrder = trim((string) ($row[$colIdx['order']] ?? ''));
                $order = is_numeric($rawOrder) ? (int) $rawOrder : ($currentMaxOrder + $idx + 1);

                $rawStart = trim((string) ($row[$colIdx['start_time']] ?? '07:00'));
                $rawEnd = trim((string) ($row[$colIdx['end_time']] ?? '07:45'));

                // Normalisasi jam:menit
                $formatTime = function ($str, $default) {
                    if (preg_match('/(\d{1,2})[:.](\d{2})/', $str, $m)) {
                        return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
                    }
                    return $default;
                };

                $startTime = $formatTime($rawStart, '07:00:00');
                $endTime = $formatTime($rawEnd, '07:45:00');

                $typeVal = mb_strtolower(trim((string) ($row[$colIdx['type']] ?? '')));
                $isBreak = str_contains($typeVal, 'istirahat')
                    || str_contains($typeVal, 'break')
                    || str_contains(mb_strtolower($name), 'istirahat')
                    || $typeVal === '1'
                    || $typeVal === 'true';

                TimeSlot::updateOrCreate(
                    ['name' => $name],
                    [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_break' => $isBreak,
                        'order' => $order,
                    ]
                );

                $importedCount++;
            }
        });

        return back()->with('success', "{$importedCount} data master jam pembelajaran berhasil diimpor dari file.");
    }

    /**
     * Reset seluruh master jam pembelajaran secara keseluruhan dari 0
     */
    public function resetAll(): RedirectResponse
    {
        $count = TimeSlot::count();
        if ($count === 0) {
            return back()->with('info', 'Tidak ada data jam pembelajaran yang perlu di-reset.');
        }

        DB::transaction(function () {
            // Null-kan referensi slot pada jadwal agar data jadwal sekolah tetap aman dan tidak terhapus
            DB::table('schedules')->update([
                'start_slot_id' => null,
                'end_slot_id' => null,
            ]);

            TimeSlot::query()->delete();
        });

        return back()->with('success', "Seluruh data master jam pembelajaran ({$count} sesi) berhasil di-reset secara keseluruhan dari 0.");
    }
}
