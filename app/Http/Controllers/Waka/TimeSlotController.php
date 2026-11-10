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
            'ceremonySettings' => [
                1 => (bool) $config->ceremony_cycle_1,
                2 => (bool) $config->ceremony_cycle_2,
            ],
        ]);
    }

    /**
     * Alihkan siklus aktif (Putaran 1 / Putaran 2)
     */
    public function switchCycle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cycle' => ['required', 'in:1,2'],
        ]);

        $config = SystemConfiguration::current();
        $config->update(['active_cycle' => (int) $validated['cycle']]);

        return back()->with('success', "Siklus pembelajaran aktif berhasil dialihkan ke Putaran {$validated['cycle']}.");
    }

    /**
     * Atur opsi on/off agenda Upacara Bendera per putaran
     */
    public function toggleCeremony(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cycle' => ['required', 'in:1,2'],
            'has_ceremony' => ['required', 'boolean'],
        ]);

        $cycle = (int) $validated['cycle'];
        $hasCeremony = (bool) $validated['has_ceremony'];

        $config = SystemConfiguration::current();
        if ($cycle === 2) {
            $config->update(['ceremony_cycle_2' => $hasCeremony]);
        } else {
            $config->update(['ceremony_cycle_1' => $hasCeremony]);
        }

        $statusText = $hasCeremony ? 'Diaktifkan (Jam Ke-1 Senin digantikan Upacara)' : 'Dinonaktifkan (KBM Normal)';

        return back()->with('success', "Pengaturan Upacara Bendera Putaran {$cycle} berhasil {$statusText}.");
    }

    /**
     * Sinkronisasi master jam pembelajaran otomatis dari jadwal sekolah yang aktif
     */
    public function syncFromSchedule(): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $activeCycle = (int) ($config->active_cycle ?? 1);

        // 12 Sesi Resmi Sekolah SMK N 1 Bantul (10 sesi KBM + 2 Istirahat)
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
            // Bersihkan slot lama dan isi 12 sesi standar resmi
            TimeSlot::query()->delete();
            foreach ($standardSlots as $slotData) {
                TimeSlot::create($slotData);
            }

            // Tautkan kembali start_slot_id & end_slot_id pada jadwal yang ada
            $allSlots = TimeSlot::all();
            $schedules = DB::table('schedules')->get();
            foreach ($schedules as $sched) {
                $startSlot = $allSlots->first(fn ($s) => substr($s->start_time, 0, 5) === substr($sched->start_time, 0, 5));
                $endSlot = $allSlots->first(fn ($s) => substr($s->end_time, 0, 5) === substr($sched->end_time, 0, 5)) ?? $startSlot;

                if ($startSlot || $endSlot) {
                    DB::table('schedules')->where('id', $sched->id)->update([
                        'start_slot_id' => $startSlot?->id,
                        'end_slot_id' => $endSlot?->id,
                    ]);
                }
            }
        });

        return back()->with('success', "Master Jam Pelajaran berhasil disinkronkan dari data Jadwal Sekolah Putaran {$activeCycle} (12 sesi jam belajar otomatis tersinkron).");
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
        $spreadsheet = new Spreadsheet;
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
        $spreadsheet = new Spreadsheet;
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
                'file' => 'Gagal membaca file Excel/CSV: '.$e->getMessage(),
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
                        return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
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
