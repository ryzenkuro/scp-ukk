<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GantiWakaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['name' => 'waka', 'label' => 'Waka Kurikulum / Administrator']);
        Role::firstOrCreate(['id' => 2], ['name' => 'guru', 'label' => 'Tenaga Pendidik / Guru']);
    }

    public function test_waka_can_access_akun_management_page(): void
    {
        $waka = User::factory()->create([
            'role_id' => 1,
            'username' => 'waka',
            'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
        ]);

        $response = $this->actingAs($waka)->get(route('waka.akun.index'));
        $response->assertStatus(200);
    }

    public function test_waka_can_successfully_transfer_role_and_update_credentials(): void
    {
        $waka = User::factory()->create([
            'role_id' => 1,
            'username' => 'waka',
            'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
            'email' => 'waka@smkn1bantul.sch.id',
        ]);

        $payload = [
            'fullname' => 'Dra. Hj. Siti Nurhaliza, M.Pd',
            'nip' => '198002152005012003',
            'username' => 'waka.baru',
            'email' => 'waka.baru@smkn1bantul.sch.id',
            'phone' => '081234567899',
            'password' => 'secret123456',
            'must_change_credentials' => false,
        ];

        $response = $this->actingAs($waka)->post(route('waka.akun.ganti-waka'), $payload);

        $response->assertRedirect(route('waka.akun.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $waka->id,
            'role_id' => 1,
            'fullname' => 'Dra. Hj. Siti Nurhaliza, M.Pd',
            'username' => 'waka.baru',
            'email' => 'waka.baru@smkn1bantul.sch.id',
            'nip' => '198002152005012003',
            'phone' => '081234567899',
            'must_change_credentials' => false,
        ]);

        $updatedWaka = User::find($waka->id);
        $this->assertTrue(Hash::check('secret123456', $updatedWaka->password));
    }

    public function test_validation_fails_for_invalid_ganti_waka_payload(): void
    {
        $waka = User::factory()->create(['role_id' => 1, 'username' => 'waka']);

        $response = $this->actingAs($waka)->post(route('waka.akun.ganti-waka'), [
            'fullname' => '',
            'username' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors(['fullname', 'username', 'email', 'password']);
    }

    public function test_non_waka_cannot_call_ganti_waka(): void
    {
        $guru = User::factory()->create(['role_id' => 2, 'username' => 'guru1']);

        $response = $this->actingAs($guru)->post(route('waka.akun.ganti-waka'), [
            'fullname' => 'Hacker Waka',
            'username' => 'hacker',
            'email' => 'hacker@school.sch.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }
}

