<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WakaDashboardController extends Controller
{
    /**
     * Tampilan utama Dashboard Waka Kurikulum
     */
    public function index(): View
    {
        $config = SystemConfiguration::current();
        $classes = SchoolClass::all();
        $rooms = Room::all();
        $roles = Role::all();
        $users = User::with(['role', 'schoolClass'])->latest()->paginate(10);

        return view('waka.dashboard', compact('config', 'classes', 'rooms', 'roles', 'users'));
    }

    /**
     * Tambah Master Kelas
     */
    public function storeClass(Request $request): RedirectResponse
    {
        $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name',
        ]);

        SchoolClass::create([
            'class_name' => $request->class_name,
        ]);

        return back()->with('success', "Kelas {$request->class_name} berhasil ditambahkan!");
    }

    /**
     * Tambah Master Ruangan
     */
    public function storeRoom(Request $request): RedirectResponse
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create([
            'room_name' => $request->room_name,
        ]);

        return back()->with('success', "Ruangan {$request->room_name} berhasil ditambahkan!");
    }

    /**
     * Tambah Pengguna Baru
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:4',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        User::create([
            'role_id' => $request->role_id,
            'class_id' => $request->class_id,
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', "Pengguna {$request->username} berhasil ditambahkan!");
    }
}
