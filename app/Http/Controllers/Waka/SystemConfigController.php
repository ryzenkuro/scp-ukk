<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * Mengubah siklus aktif (Putaran 1 <-> Putaran 2)
     */
    public function toggleCycle(Request $request): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $nextCycle = $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';

        $config->update([
            'active_cycle' => $nextCycle,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', "Siklus blok berhasil diubah menjadi: {$nextCycle}");
    }

    /**
     * Mengunci atau membuka kunci jadwal mengajar guru
     */
    public function toggleLock(Request $request): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $nextLocked = ! $config->is_schedule_locked;

        $config->update([
            'is_schedule_locked' => $nextLocked,
            'updated_by' => auth()->id(),
        ]);

        $status = $nextLocked ? 'DIKUNCI (Guru tidak dapat mengubah jadwal)' : 'DIBUKA (Guru dapat menginput jadwal)';

        return back()->with('success', "Status pengisian jadwal berhasil diubah: {$status}");
    }
}
