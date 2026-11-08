<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AkunRoleController extends Controller
{
    /**
     * Tampilkan data manajemen akun & role pengguna
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $roleId = $request->input('role_id');
        $status = $request->input('status'); // 'active', 'inactive', 'all'

        $query = User::with(['role', 'student'])
            ->orderBy('role_id')
            ->orderBy('username');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId && $roleId !== 'all') {
            $query->where('role_id', (int) $roleId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::orderBy('id')->get();
        $currentWaka = User::where('role_id', 1)->first() ?? auth()->user();

        return Inertia::render('Waka/AkunRole/Index', [
            'users' => $users,
            'roles' => $roles,
            'currentWaka' => $currentWaka,
            'filters' => [
                'search' => $search,
                'role_id' => $roleId ?? 'all',
                'status' => $status ?? 'all',
            ],
        ]);
    }

    /**
     * Alih tugas & perbarui data Waka Kurikulum (Admin Utama)
     */
    public function gantiWaka(Request $request): RedirectResponse
    {
        $wakaUser = User::where('role_id', 1)->first() ?? auth()->user();

        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($wakaUser->id),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($wakaUser->id),
            ],
            'nip' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:6'],
            'must_change_credentials' => ['boolean'],
        ], [
            'fullname.required' => 'Nama lengkap pejabat Waka baru wajib diisi.',
            'username.required' => 'Username Waka baru wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan akun lain.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
            'email.required' => 'Email resmi Waka baru wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar pada akun lain.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $wakaUser->update([
            'fullname' => $validated['fullname'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'],
            'nip' => $validated['nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'must_change_credentials' => $request->boolean('must_change_credentials', false),
            'is_active' => true,
        ]);

        // Perbarui instance user pada session jika user yang sedang login adalah waka tersebut
        if (auth()->id() === $wakaUser->id) {
            auth()->setUser($wakaUser);
        }

        return redirect()->route('waka.akun.index')->with(
            'success',
            "Jabatan Waka Kurikulum resmi dialihkan ke {$validated['fullname']}. Data akun dan kredensial baru telah aktif."
        );
    }

    /**
     * Tambah akun pengguna baru secara manual
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'nip' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'must_change_credentials' => ['boolean'],
        ], [
            'username.unique' => 'Username ini sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
            'email.unique' => 'Email ini sudah terdaftar pada akun lain.',
        ]);

        $password = !empty($validated['password']) ? $validated['password'] : 'smkn1bantul2026';

        User::create([
            'fullname' => $validated['fullname'],
            'username' => strtolower($validated['username']),
            'role_id' => (int) $validated['role_id'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'password' => Hash::make($password),
            'must_change_credentials' => $request->boolean('must_change_credentials', true),
            'is_active' => true,
        ]);

        return back()->with('success', "Akun pengguna {$validated['username']} berhasil ditambahkan.");
    }

    /**
     * Toggle status aktif akun pengguna
     */
    public function toggleActive(User $user): RedirectResponse
    {
        // Proteksi keamanan: akun administrator / waka / akun aktif tidak boleh dinonaktifkan
        if ($user->id === auth()->id() || $user->username === 'admin' || (int) $user->role_id === 1 || $user->role?->name === 'waka') {
            return back()->with('error', 'Akun Administrator / Waka Kurikulum tidak dapat dinonaktifkan demi menjaga stabilitas sistem.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->username} berhasil {$statusText}.");
    }

    /**
     * Reset kredensial akun ke default (smkn1bantul2026 + must_change_credentials)
     */
    public function resetCredentials(User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make('smkn1bantul2026'),
            'must_change_credentials' => true,
        ]);

        return back()->with('success', "Kredensial akun {$user->username} berhasil di-reset. Kata sandi disetel ulang ke smkn1bantul2026 dan wajib diganti saat login.");
    }

    /**
     * Hapus akun pengguna (seluruh akun selain akun diri sendiri yang sedang login)
     */
    public function destroy(User $user): RedirectResponse
    {
        // Proteksi keamanan: tidak boleh menghapus akun admin atau akun yang sedang aktif digunakan
        if ($user->id === auth()->id() || $user->username === 'admin' || (int) $user->role_id === 1 || $user->role?->name === 'waka') {
            return back()->with('error', 'Akun Administrator / Waka Kurikulum tidak dapat dihapus demi integritas sistem.');
        }

        $username = $user->username;
        $fullname = $user->fullname ?? $user->username;

        DB::transaction(function () use ($user) {
            // Bersihkan penugasan wali kelas & relasi kelas binaan jika merupakan guru
            DB::table('homeroom_assignments')->where('user_id', $user->id)->delete();
            DB::table('user_school_classes')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        return back()->with('success', "Akun {$username} ({$fullname}) berhasil dihapus.");
    }
}
