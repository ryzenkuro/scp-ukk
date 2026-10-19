<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestVerificationTest extends TestCase
{
    public function test_siswa_dan_orang_tua_dapat_mengajukan_izin_dan_diverifikasi(): void
    {
        Storage::fake('public');

        $siswaRole = Role::firstOrCreate(['role_name' => 'siswa']);
        $ortuRole = Role::firstOrCreate(['role_name' => 'orang_tua']);
        $waliRole = Role::firstOrCreate(['role_name' => 'wali_kelas']);
        $bkRole = Role::firstOrCreate(['role_name' => 'guru_bk']);

        $classA = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 1']);
        $classB = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 2']);

        // Ortu & Siswa di Kelas A
        $ortu = User::where('username', 'ortu1')->first();
        $siswaA = User::where('username', 'siswa1')->first();

        // Siswa di Kelas B
        $siswaB = User::where('username', 'siswa3')->first();

        // Wali Kelas A & Guru BK
        $waliA = User::where('username', 'walikelas')->first();
        $guruBk = User::where('username', 'gurubk')->first();

        // 1. Siswa A mengajukan izin dengan bukti file valid
        $file = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $responseSiswa = $this->actingAs($siswaA)->post(route('portal.leaves.store'), [
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+2 days')),
            'reason'     => 'Demam tinggi dan istirahat dokter',
            'document'   => $file,
        ]);

        $responseSiswa->assertSessionHas('success');

        $leaveA = LeaveRequest::where('student_id', $siswaA->id)->latest()->first();
        $this->assertNotNull($leaveA);
        $this->assertEquals('Pending', $leaveA->status_approval);
        $this->assertEquals($siswaA->id, $leaveA->applicant_id);
        Storage::disk('public')->assertExists($leaveA->document_path);

        // 2. Ortu mengajukan izin untuk Siswa A
        $fileImg = UploadedFile::fake()->image('surat_ortu.jpg');

        $responseOrtu = $this->actingAs($ortu)->post(route('portal.leaves.store'), [
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d'),
            'reason'     => 'Izin ada keperluan keluarga penting',
            'document'   => $fileImg,
        ]);

        $responseOrtu->assertSessionHas('success');

        $leaveOrtu = LeaveRequest::where('applicant_id', $ortu->id)->latest()->first();
        $this->assertNotNull($leaveOrtu);
        $this->assertEquals($siswaA->id, $leaveOrtu->student_id);

        // 3. Validasi berkas ditolak jika file lebih dari 2MB
        $fileBig = UploadedFile::fake()->create('dokumen_besar.pdf', 3000, 'application/pdf');

        $responseBig = $this->actingAs($siswaA)->post(route('portal.leaves.store'), [
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d'),
            'reason'     => 'Izin sakit',
            'document'   => $fileBig,
        ]);

        $responseBig->assertSessionHasErrors(['document']);

        // 4. Wali Kelas A menyetujui izin Siswa A (Approve)
        $responseVerify = $this->actingAs($waliA)->patch(route('kesiswaan.leaves.verify', $leaveA->id), [
            'status' => 'Approved',
        ]);

        $responseVerify->assertSessionHas('success');
        $leaveA->refresh();
        $this->assertEquals('Approved', $leaveA->status_approval);
        $this->assertEquals($waliA->id, $leaveA->verified_by);

        // 5. Wali Kelas A DITOLAK saat mencoba verifikasi izin Siswa B (di luar kelas perwaliannya)
        $leaveB = LeaveRequest::create([
            'student_id'      => $siswaB->id,
            'applicant_id'    => $siswaB->id,
            'start_date'      => date('Y-m-d'),
            'end_date'        => date('Y-m-d'),
            'reason'          => 'Izin Siswa Kelas B',
            'document_path'   => 'documents/leaves/dummy.pdf',
            'status_approval' => 'Pending',
        ]);

        $responseCrossClass = $this->actingAs($waliA)->patch(route('kesiswaan.leaves.verify', $leaveB->id), [
            'status' => 'Approved',
        ]);
        $responseCrossClass->assertStatus(403);

        // 6. Guru BK dapat memverifikasi Siswa B dari kelas mana pun
        $responseBk = $this->actingAs($guruBk)->patch(route('kesiswaan.leaves.verify', $leaveB->id), [
            'status' => 'Rejected',
        ]);
        $responseBk->assertSessionHas('success');
        $leaveB->refresh();
        $this->assertEquals('Rejected', $leaveB->status_approval);
        $this->assertEquals($guruBk->id, $leaveB->verified_by);
    }
}
