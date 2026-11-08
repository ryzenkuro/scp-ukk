<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3DataMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_view_siswa_index(): void
    {
        $waka = User::where('username', 'admin')->first();

        $response = $this->actingAs($waka)->get('/waka/siswa');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Siswa/Index')
            ->has('siswas')
            ->has('kelasList')
            ->has('angkatanList')
        );
    }

    public function test_waka_can_store_siswa_and_it_creates_both_student_and_parent_accounts(): void
    {
        $waka = User::where('username', 'admin')->first();

        $response = $this->actingAs($waka)->post('/waka/siswa', [
            'nis' => '202611099',
            'name' => 'Kurniawan Pratama',
            'kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'angkatan' => 2026,
            'gender' => 'L',
            'phone' => '081234567899',
            'email' => 'kurniawan@siswa.sch.id',
            'alamat' => 'Bantul Barat',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Siswa dibuat
        $this->assertDatabaseHas('siswas', [
            'nis' => '202611099',
            'name' => 'KURNIAWAN PRATAMA',
            'status' => 'aktif',
        ]);

        $siswa = Siswa::where('nis', '202611099')->first();

        // 2. Akun Siswa (Username = NIS, must_change_credentials = true)
        $this->assertDatabaseHas('users', [
            'username' => '202611099',
            'role_id' => 3,
            'student_id' => $siswa->id,
            'must_change_credentials' => 1,
            'is_active' => 1,
        ]);

        // 3. Akun Orang Tua (Username = ortu_[NIS], must_change_credentials = true)
        $this->assertDatabaseHas('users', [
            'username' => 'ortu_202611099',
            'role_id' => 6,
            'student_id' => $siswa->id,
            'must_change_credentials' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_waka_can_update_siswa_and_it_syncs_account(): void
    {
        $waka = User::where('username', 'admin')->first();
        $siswa = Siswa::where('nis', '202611001')->first();

        $response = $this->actingAs($waka)->put("/waka/siswa/{$siswa->id}", [
            'name' => 'MUHYIYUDDIN REVISED',
            'kelas' => 'XII RPL 2',
            'jurusan' => 'RPL',
            'angkatan' => 2024,
            'gender' => 'L',
            'phone' => '081299990000',
            'email' => 'muhyi_new@siswa.sch.id',
            'alamat' => 'Yogyakarta Baru',
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', [
            'id' => $siswa->id,
            'name' => 'MUHYIYUDDIN REVISED',
        ]);

        $this->assertDatabaseHas('users', [
            'student_id' => $siswa->id,
            'role_id' => 3,
            'fullname' => 'MUHYIYUDDIN REVISED',
        ]);
    }

    public function test_naik_kelas_promotes_x_to_xi_and_xi_to_xii(): void
    {
        $waka = User::where('username', 'admin')->first();

        // Siswa X RPL 1 angkatan 2025
        $siswaX = Siswa::create([
            'nis' => '202510101',
            'name' => 'Siswa Kelas 10',
            'kelas' => 'X RPL 1',
            'angkatan' => 2025,
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        // Siswa XI RPL 1 angkatan 2025
        $siswaXI = Siswa::create([
            'nis' => '202510102',
            'name' => 'Siswa Kelas 11',
            'kelas' => 'XI RPL 1',
            'angkatan' => 2025,
            'gender' => 'P',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($waka)->post('/waka/siswa/naik-kelas', [
            'angkatan' => 2025,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswaX->refresh();
        $siswaXI->refresh();

        $this->assertEquals('XI RPL 1', $siswaX->kelas);
        $this->assertEquals('XII RPL 1', $siswaXI->kelas);
    }

    public function test_naik_kelulusan_moves_class_xii_to_alumni_and_deactivates_user_account(): void
    {
        $waka = User::where('username', 'admin')->first();

        // Ambil siswa kelas XII demo: MUHYIYUDDIN (202611001, kelas XII RPL 2, angkatan 2024)
        $siswaXII = Siswa::where('nis', '202611001')->first();
        $this->assertEquals('XII RPL 2', $siswaXII->kelas);
        $this->assertEquals('aktif', $siswaXII->status);

        $userStudent = User::where('username', '202611001')->first();
        $this->assertTrue($userStudent->is_active);

        // Jalankan Naik Kelas / Naik Kelulusan untuk Angkatan 2024
        $response = $this->actingAs($waka)->post('/waka/siswa/naik-kelas', [
            'angkatan' => 2024,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswaXII->refresh();
        $userStudent->refresh();

        // 1. Siswa status berubah jadi 'lulus'
        $this->assertEquals('lulus', $siswaXII->status);
        $this->assertEquals((string) date('Y'), $siswaXII->tahun_lulus);

        // 2. Data disalin ke tabel alumnis
        $this->assertDatabaseHas('alumnis', [
            'student_id' => $siswaXII->id,
            'nis' => '202611001',
            'name' => 'MUHYIYUDDIN',
            'kelas_terakhir' => 'XII RPL 2',
            'tahun_lulus' => (string) date('Y'),
        ]);

        // 3. Akun login siswa dinonaktifkan
        $this->assertFalse($userStudent->is_active);
    }

    public function test_alumni_retention_and_destroy_expired(): void
    {
        $waka = User::where('username', 'admin')->first();

        // Seeder sudah membuat:
        // 1. Fajar Nugroho (tahun 2026) -> Aktif (<= 2026 + 1 = 2027)
        // 2. Bambang Tri (tahun 2024) -> Expired (< 2026 - 1 = 2025)
        $activeAlumni = Alumni::where('nis', '202510001')->first();
        $expiredAlumni = Alumni::where('nis', '202310088')->first();

        $this->assertFalse($activeAlumni->is_expired);
        $this->assertTrue($expiredAlumni->is_expired);

        // Kunjungi halaman arsip alumni -> sistem otomatis membersihkan data alumni expired
        $response = $this->actingAs($waka)->get('/waka/alumni');
        $response->assertOk();

        // Pastikan alumni expired otomatis terhapus tanpa perlu intervensi manual
        $this->assertDatabaseMissing('alumnis', [
            'id' => $expiredAlumni->id,
        ]);

        // Pastikan alumni aktif tetap tersimpan
        $this->assertDatabaseHas('alumnis', [
            'id' => $activeAlumni->id,
        ]);

        // Pastikan endpoint hapus manual sudah ditiadakan (404)
        $this->actingAs($waka)->post('/waka/alumni/destroy-expired')->assertNotFound();
        $this->actingAs($waka)->delete('/waka/alumni/' . $activeAlumni->id)->assertNotFound();
    }

    public function test_waka_can_manage_guru_and_reset_password(): void
    {
        $waka = User::where('username', 'admin')->first();

        // Tambah guru baru
        $response = $this->actingAs($waka)->post('/waka/guru', [
            'username' => 'guru.informatika',
            'fullname' => 'Hendra Gunawan, M.T',
            'email' => 'hendra@smkn1bantul.sch.id',
            'phone' => '081234567800',
            'role_id' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guru = User::where('username', 'guru.informatika')->first();
        $this->assertNotNull($guru);
        $this->assertEquals(2, $guru->role_id);
        $this->assertTrue($guru->must_change_credentials);

        // Edit guru
        $this->actingAs($waka)->put("/waka/guru/{$guru->id}", [
            'fullname' => 'Hendra Gunawan, M.Kom (Revisi)',
            'email' => 'hendra.new@smkn1bantul.sch.id',
            'phone' => '081234567801',
            'is_active' => true,
        ]);

        $guru->refresh();
        $this->assertEquals('Hendra Gunawan, M.Kom (Revisi)', $guru->fullname);

        // Reset password
        $this->actingAs($waka)->post("/waka/guru/{$guru->id}/reset-password");
        $guru->refresh();
        $this->assertTrue($guru->must_change_credentials);
    }

    public function test_waka_can_manage_orang_tua(): void
    {
        $waka = User::where('username', 'admin')->first();
        $parent = User::where('username', 'ortu_202611001')->first();

        $this->assertNotNull($parent);
        $this->assertTrue($parent->must_change_credentials);

        // Kunjungi halaman index orang tua & pastikan stats aktivasi termuat
        $responseIndex = $this->actingAs($waka)->get('/waka/orang-tua');
        $responseIndex->assertOk();

        // Edit kontak orang tua
        $response = $this->actingAs($waka)->put("/waka/orang-tua/{$parent->id}", [
            'fullname' => 'Bapak Muhyiyuddin',
            'phone' => '081999888777',
            'email' => 'ortu.muhyi@gmail.com',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $parent->refresh();
        $this->assertEquals('Bapak Muhyiyuddin', $parent->fullname);

        // Reset password
        $this->actingAs($waka)->post("/waka/orang-tua/{$parent->id}/reset-password");
        $parent->refresh();
        $this->assertTrue($parent->must_change_credentials);
    }

    public function test_orang_tua_can_independently_activate_account(): void
    {
        $parent = User::where('username', 'ortu_202611001')->first();
        $student = User::where('username', '202611001')->first();

        // 1. Orang tua login dengan kredensial bawaan (ortu_[NIS] / smkn1bantul2026)
        $this->actingAs($parent);

        // 2. Karena must_change_credentials = true, akses diarahkan ke /auth/activate
        $this->assertTrue($parent->must_change_credentials);
        $response = $this->get('/dashboard');
        $response->assertRedirect('/auth/activate');

        // 3. Orang tua mengisi username baru & sandi baru pilihan sendiri
        $activateResponse = $this->post('/auth/activate', [
            'new_username' => 'wali_muhyi',
            'new_password' => 'SandiBaruOrtu123',
            'new_password_confirmation' => 'SandiBaruOrtu123',
        ]);
        $activateResponse->assertRedirect('/dashboard');

        // 4. Verifikasi akun orang tua berhasil aktivasi mandiri
        $parent->refresh();
        $this->assertEquals('wali_muhyi', $parent->username);
        $this->assertFalse($parent->must_change_credentials);

        // 5. Pastikan akun siswa 202611001 tetap terpisah dan tidak terpengaruh
        $student->refresh();
        $this->assertEquals('202611001', $student->username);
    }

    public function test_waka_can_toggle_active_and_reset_credentials_in_akun_role(): void
    {
        $waka = User::where('username', 'admin')->first();
        $studentUser = User::where('username', '202611001')->first();

        $this->assertTrue($studentUser->is_active);

        // Toggle nonaktif
        $response = $this->actingAs($waka)->post("/waka/akun/{$studentUser->id}/toggle-active");
        $response->assertRedirect();

        $studentUser->refresh();
        $this->assertFalse($studentUser->is_active);

        // Toggle aktif kembali
        $this->actingAs($waka)->post("/waka/akun/{$studentUser->id}/toggle-active");
        $studentUser->refresh();
        $this->assertTrue($studentUser->is_active);

        // Reset credentials
        $this->actingAs($waka)->post("/waka/akun/{$studentUser->id}/reset-credentials");
        $studentUser->refresh();
        $this->assertTrue($studentUser->must_change_credentials);
    }

    public function test_admin_account_cannot_be_toggled_inactive_or_deleted(): void
    {
        $waka = User::where('username', 'admin')->first();
        $this->assertTrue($waka->is_active);

        // Percobaan menonaktifkan akun admin harus ditolak
        $responseToggle = $this->actingAs($waka)->post("/waka/akun/{$waka->id}/toggle-active");
        $responseToggle->assertRedirect();
        $responseToggle->assertSessionHas('error');

        $waka->refresh();
        $this->assertTrue($waka->is_active);

        // Percobaan menghapus akun admin harus ditolak
        $responseDelete = $this->actingAs($waka)->delete("/waka/akun/{$waka->id}");
        $responseDelete->assertRedirect();
        $responseDelete->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $waka->id, 'username' => 'admin']);
    }

    public function test_waka_can_reset_all_teachers_from_zero(): void
    {
        $waka = User::where('username', 'admin')->first();

        $this->assertGreaterThan(0, User::whereIn('role_id', [2, 5])->where('username', '!=', 'admin')->count());

        $response = $this->actingAs($waka)->delete('/waka/guru/reset-semua');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, User::whereIn('role_id', [2, 5])->where('username', '!=', 'admin')->count());
        $this->assertDatabaseHas('users', ['username' => 'admin']);
    }

    public function test_waka_can_delete_all_students_at_once(): void
    {
        $waka = User::where('username', 'admin')->first();

        $this->assertGreaterThan(0, Siswa::count());

        $response = $this->actingAs($waka)->delete('/waka/data-siswa/hapus-per-kelas', [
            'kelas' => 'ALL',
            'force' => true,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, Siswa::count());
    }

    public function test_waka_can_store_new_account_in_akun_role(): void
    {
        $waka = User::where('username', 'admin')->first();

        $response = $this->actingAs($waka)->post('/waka/akun', [
            'fullname' => 'Petugas TU Baru',
            'username' => 'tu.baru',
            'role_id' => 2, // Guru / Staf
            'email' => 'tubaru@sekolah.sch.id',
            'phone' => '089988776655',
            'nip' => '198701012020011005',
            'password' => 'secret12345',
            'must_change_credentials' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newUser = User::where('username', 'tu.baru')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Petugas TU Baru', $newUser->fullname);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue($newUser->must_change_credentials);
        $this->assertTrue(Hash::check('secret12345', $newUser->password));
    }

    public function test_waka_can_delete_account_in_akun_role(): void
    {
        $waka = User::where('username', 'admin')->first();

        // Create temporary user to delete
        $tempUser = User::create([
            'fullname' => 'Akun Dihapus',
            'username' => 'akundihapus',
            'role_id' => 2,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', ['id' => $tempUser->id]);

        $response = $this->actingAs($waka)->delete("/waka/akun/{$tempUser->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $tempUser->id]);
    }

    public function test_waka_can_delete_student_and_parent_account_in_akun_role(): void
    {
        $waka = User::where('username', 'admin')->first();

        $studentUser = User::where('username', '202611001')->first();
        $this->assertNotNull($studentUser);

        $responseSiswa = $this->actingAs($waka)->delete("/waka/akun/{$studentUser->id}");
        $responseSiswa->assertRedirect();
        $responseSiswa->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);

        $parentUser = User::where('username', 'ortu_202611001')->first();
        $this->assertNotNull($parentUser);

        $responseOrtu = $this->actingAs($waka)->delete("/waka/akun/{$parentUser->id}");
        $responseOrtu->assertRedirect();
        $responseOrtu->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $parentUser->id]);
    }

    public function test_waka_cannot_delete_self_in_akun_role(): void
    {
        $waka = User::where('username', 'admin')->first();

        $response = $this->actingAs($waka)->delete("/waka/akun/{$waka->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $waka->id]);
    }
}

