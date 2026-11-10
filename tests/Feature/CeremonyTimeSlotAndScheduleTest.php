<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CeremonyTimeSlotAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_toggle_ceremony_per_cycle_independently(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.ceremony',
            'must_change_credentials' => false,
        ]);

        // 1. Aktifkan upacara untuk Putaran 1
        $response1 = $this->actingAs($waka)->post(route('waka.jam-pelajaran.toggle-ceremony'), [
            'cycle' => 1,
            'has_ceremony' => true,
        ]);

        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('system_configurations', [
            'ceremony_cycle_1' => 1,
            'ceremony_cycle_2' => 0,
        ]);

        // 2. Aktifkan upacara untuk Putaran 2
        $response2 = $this->actingAs($waka)->post(route('waka.jam-pelajaran.toggle-ceremony'), [
            'cycle' => 2,
            'has_ceremony' => true,
        ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('system_configurations', [
            'ceremony_cycle_1' => 1,
            'ceremony_cycle_2' => 1,
        ]);

        // 3. Matikan upacara untuk Putaran 1, Putaran 2 tetap aktif
        $response3 = $this->actingAs($waka)->post(route('waka.jam-pelajaran.toggle-ceremony'), [
            'cycle' => 1,
            'has_ceremony' => false,
        ]);

        $response3->assertRedirect();
        $this->assertDatabaseHas('system_configurations', [
            'ceremony_cycle_1' => 0,
            'ceremony_cycle_2' => 1,
        ]);
    }

    public function test_jam_pelajaran_index_receives_ceremony_settings(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.ceremony2',
            'must_change_credentials' => false,
        ]);

        $config = SystemConfiguration::current();
        $config->update([
            'ceremony_cycle_1' => true,
            'ceremony_cycle_2' => false,
        ]);

        $response = $this->actingAs($waka)->get(route('waka.jam-pelajaran.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Waka/JamPelajaran/Index')
            ->has('ceremonySettings')
            ->where('ceremonySettings.1', true)
            ->where('ceremonySettings.2', false)
        );
    }

    public function test_guru_and_waka_jadwal_receive_ceremony_config(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.ceremony3',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $guru = User::factory()->create([
            'role_id' => $guruRole->id,
            'username' => 'guru.ceremony',
            'must_change_credentials' => false,
        ]);

        $config = SystemConfiguration::current();
        $config->update([
            'ceremony_cycle_1' => true,
            'ceremony_cycle_2' => false,
        ]);

        // Check Guru Jadwal
        $resGuru = $this->actingAs($guru)->get(route('guru.jadwal'));
        $resGuru->assertOk();
        $resGuru->assertInertia(fn (Assert $page) => $page
            ->component('Guru/Jadwal')
            ->where('config.ceremony_cycle_1', true)
            ->where('config.ceremony_cycle_2', false)
        );

        // Check Waka Jadwal
        $resWaka = $this->actingAs($waka)->get(route('waka.jadwal.index'));
        $resWaka->assertOk();
        $resWaka->assertInertia(fn (Assert $page) => $page
            ->component('Waka/Jadwal/Index')
            ->where('config.ceremony_cycle_1', true)
            ->where('config.ceremony_cycle_2', false)
        );
    }
}
