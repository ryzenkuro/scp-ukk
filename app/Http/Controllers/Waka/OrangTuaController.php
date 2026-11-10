<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrangTuaController extends Controller
{
    /**
     * Download master data orang tua dalam format Markdown (.md)
     */
    public function downloadMaster(MasterDataService $masterDataService): StreamedResponse
    {
        $content = $masterDataService->getMasterMarkdownContent('ortu');

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            'master_data_orang_tua.md',
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    /**
     * Tampilkan data master orang tua siswa
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = User::with(['student'])
            ->where('role_id', 6) // Orang Tua
            ->orderBy('fullname');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('kelas', 'like', "%{$search}%");
                    });
            });
        }

        $orangTuas = $query->paginate(20)->withQueryString();

        // Metrik status aktivasi mandiri akun orang tua
        $total = User::where('role_id', 6)->count();
        $activated = User::where('role_id', 6)->where('must_change_credentials', false)->count();
        $pending = $total - $activated;

        return Inertia::render('Waka/OrangTua/Index', [
            'orangTuas' => $orangTuas,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => $total,
                'activated' => $activated,
                'pending' => $pending,
            ],
        ]);
    }

    /**
     * Perbarui data orang tua
     */
    public function update(Request $request, User $orangTua): RedirectResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $orangTua->update([
            'fullname' => $validated['fullname'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Data orang tua {$orangTua->fullname} berhasil diperbarui.");
    }

    /**
     * Reset password akun orang tua ke default (smkn1bantul2026) dan wajib aktivasi mandiri
     */
    public function resetPassword(User $orangTua): RedirectResponse
    {
        $orangTua->update([
            'password' => Hash::make('smkn1bantul2026'),
            'must_change_credentials' => true,
        ]);

        return back()->with('success', "Kata sandi akun {$orangTua->username} berhasil di-reset ke smkn1bantul2026.");
    }
}
