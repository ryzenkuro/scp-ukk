<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\User;
use App\Services\MasterDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TwoStagePipelineMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $wakaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->wakaUser = User::where('role_id', 1)->first();
    }

    public function test_markdown_table_parser_accuracy(): void
    {
        $service = new MasterDataService;
        $md = "| No | Username | NIP | Nama Lengkap & Gelar |\n";
        $md .= "|:---:|:---:|:---:|:---|\n";
        $md .= "| 1 | `guru.01` | 196904041989031010 | RAHARJO S.IP., M.Pd. |\n";
        $md .= "| 2 | `guru.02` | 196906051994122003 | ARIS PURWANTINAH, S.Pd. |\n";

        $rows = $service->parseMarkdownTable($md);
        $this->assertCount(2, $rows);
        $this->assertArrayHasKey('nama lengkap & gelar', $rows[0]);
        $this->assertStringContainsString('RAHARJO', $rows[0]['nama lengkap & gelar']);
        $this->assertStringContainsString('ARIS', $rows[1]['nama lengkap & gelar']);
    }

    public function test_waka_can_download_master_markdown_for_all_entities(): void
    {
        $this->actingAs($this->wakaUser);

        // Siswa Master MD
        $responseSiswa = $this->get('/waka/siswa/master-md');
        $responseSiswa->assertStatus(200);
        $responseSiswa->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        // Guru Master MD
        $responseGuru = $this->get('/waka/guru/master-md');
        $responseGuru->assertStatus(200);
        $responseGuru->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        // Orang Tua Master MD
        $responseOrtu = $this->get('/waka/orang-tua/master-md');
        $responseOrtu->assertStatus(200);
        $responseOrtu->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        // Jadwal Master MD (Putaran 1 & 2)
        $responseJadwal1 = $this->get('/waka/jadwal/master-md?cycle=1');
        $responseJadwal1->assertStatus(200);
        $responseJadwal1->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        $responseJadwal2 = $this->get('/waka/jadwal/master-md?cycle=2');
        $responseJadwal2->assertStatus(200);
        $responseJadwal2->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }

    public function test_waka_can_import_student_via_markdown(): void
    {
        $this->actingAs($this->wakaUser);

        $mdContent = "| No | NIS | Username | Nama Lengkap Siswa | L/P | Jurusan | Angkatan |\n";
        $mdContent .= "|:---:|:---:|:---:|:---|:---:|:---:|:---:|\n";
        $mdContent .= "| 1 | 99001 | `99001` | AHMAD SISWA PERDANA | L | RPL | 2026 |\n";
        $mdContent .= "| 2 | 99002 | `99002` | SITI AISYAH NUR | P | RPL | 2026 |\n";

        $file = UploadedFile::fake()->createWithContent('master_siswa_test.md', $mdContent);

        $response = $this->post('/waka/siswa/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/waka/siswa-index');
        $this->assertDatabaseHas('siswas', ['nis' => '99001', 'name' => 'AHMAD SISWA PERDANA']);
        $this->assertDatabaseHas('users', ['username' => '99001', 'role_id' => 3]);
        $this->assertDatabaseHas('users', ['username' => 'ortu_99001', 'role_id' => 6]);
    }

    public function test_waka_can_import_guru_via_markdown(): void
    {
        $this->actingAs($this->wakaUser);

        $mdContent = "| No | Username Default | NIP | Nama Lengkap & Gelar | L/P | Golongan | Jabatan |\n";
        $mdContent .= "|:---:|:---:|:---:|:---|:---:|:---:|:---|\n";
        $mdContent .= "| 1 | `guru.95` | 198001012010011005 | GURU TEST TELADAN, M.Pd. | L | Penata/III/c | Guru Muda |\n";

        $file = UploadedFile::fake()->createWithContent('master_guru_test.md', $mdContent);

        $response = $this->post('/waka/guru/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/waka/guru-index');
        $this->assertDatabaseHas('users', [
            'username' => 'guru.95',
            'nip' => '198001012010011005',
            'fullname' => 'GURU TEST TELADAN, M.Pd.',
            'must_change_credentials' => true,
        ]);
    }

    public function test_waka_can_import_jadwal_via_markdown(): void
    {
        $this->actingAs($this->wakaUser);

        SchoolClass::firstOrCreate(['name' => 'X RPL 1'], ['grade' => 'X', 'jurusan' => 'RPL']);

        $mdContent = "| No | Hari | Rombel Kelas | Jam Pelajaran | Mata Pelajaran | Guru Pengampu | Ruang |\n";
        $mdContent .= "|:---:|:---:|:---|:---:|:---|:---|:---:|\n";
        $mdContent .= "| 1 | Senin | X RPL 1 | 07:15 - 09:30 | Pemrograman Dasar | Drs. H. Hartono, M.Pd (Waka Kurikulum) | LAB 16 |\n";

        $file = UploadedFile::fake()->createWithContent('master_jadwal_test.md', $mdContent);

        $response = $this->post('/waka/jadwal/import', [
            'file' => $file,
            'cycle_number' => 1,
        ]);

        $response->assertRedirect('/waka/jadwal-index?cycle=1');
        $this->assertDatabaseHas('schedules', [
            'cycle_number' => 1,
            'day_of_week' => 'Senin',
            'class_name' => 'X RPL 1',
            'subject' => 'Pemrograman Dasar',
            'room_name' => 'LAB 16',
        ]);
    }

    public function test_artisan_sync_master_data_command(): void
    {
        $this->artisan('app:sync-master-data', ['--only' => 'kelas'])
            ->assertSuccessful();

        $this->assertGreaterThan(0, SchoolClass::count());
    }

    public function test_master_guru_has_unique_usernames_and_accurate_schedule_mapping(): void
    {
        $jsonPath = database_path('data/master_guru.json');
        $this->assertFileExists($jsonPath);

        $teachers = json_decode(file_get_contents($jsonPath), true);
        $this->assertCount(91, $teachers);

        $usernames = array_column($teachers, 'username');
        $this->assertCount(count($teachers), array_unique($usernames), 'Usernames in master_guru.json must be 100% unique');

        // Tatik Wardayati must have kode 13 and guru.13
        $tatik = collect($teachers)->firstWhere('name', 'TATIK WARDAYATI, S.Pd');
        $this->assertNotNull($tatik);
        $this->assertEquals(13, $tatik['kode']);
        $this->assertEquals('guru.13', $tatik['username']);

        // Reny Murni must have kode 70 and guru.70
        $reny = collect($teachers)->firstWhere('name', 'RENY MURNI HIDAYATI, S.Pd.');
        $this->assertNotNull($reny);
        $this->assertEquals(70, $reny['kode']);
        $this->assertEquals('guru.70', $reny['username']);
    }
}
