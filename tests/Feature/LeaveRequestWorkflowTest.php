<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    public function test_student_and_parent_leave_workflow_and_verification(): void
    {
        Storage::fake('public');

        $siswaRole = Role::where('role_name', 'siswa')->first();
        $ortuRole = Role::where('role_name', 'orang_tua')->first();
        $waliRole = Role::where('role_name', 'wali_kelas')->first();
        $bkRole = Role::where('role_name', 'guru_bk')->first();

        $class1 = SchoolClass::first();
        $class2 = SchoolClass::skip(1)->first();

        // 1. Ambil akun siswa1 (kelas 1) dan ortu1 (orang tua siswa1)
        $siswa1 = User::where('username', 'siswa1')->first();
        $ortu1 = User::where('username', 'ortu1')->first();
        $walikelas = User::where('username', 'walikelas')->first(); // Wali kelas 1
        $gurubk = User::where('username', 'gurubk')->first();

        // 2. Test Siswa Mengajukan Izin
        $fileSiswa = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $responseSiswa = $this->actingAs($siswa1)->post(route('portal.leave-requests.store'), [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Demam dan perlu istirahat dokter',
            'document' => $fileSiswa,
        ]);

        $responseSiswa->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'reason' => 'Demam dan perlu istirahat dokter',
            'status_approval' => 'Pending',
        ]);

        $leaveSiswa = LeaveRequest::where('student_id', $siswa1->id)->latest()->first();
        Storage::disk('public')->assertExists($leaveSiswa->document_path);

        // 3. Test Wali Kelas Meng-Approve Izin Siswa Perwaliannya
        $responseVerify = $this->actingAs($walikelas)->patch(route('kesiswaan.leave-requests.verify', $leaveSiswa->id), [
            'status' => 'Approved',
        ]);

        $responseVerify->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveSiswa->id,
            'status_approval' => 'Approved',
            'verified_by' => $walikelas->id,
        ]);

        // 4. Test Orang Tua Mengajukan Izin untuk Anaknya
        $fileOrtu = UploadedFile::fake()->image('surat_ortu.png');

        $responseOrtu = $this->actingAs($ortu1)->post(route('portal.leave-requests.store'), [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'reason' => 'Ada acara keluarga di luar kota',
            'document' => $fileOrtu,
        ]);

        $responseOrtu->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswa1->id,
            'applicant_id' => $ortu1->id,
            'reason' => 'Ada acara keluarga di luar kota',
            'status_approval' => 'Pending',
        ]);

        $leaveOrtu = LeaveRequest::where('applicant_id', $ortu1->id)->latest()->first();

        // 5. Test Guru BK Meng-Approve Izin
        $responseBk = $this->actingAs($gurubk)->patch(route('kesiswaan.leave-requests.verify', $leaveOrtu->id), [
            'status' => 'Approved',
        ]);

        $responseBk->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveOrtu->id,
            'status_approval' => 'Approved',
            'verified_by' => $gurubk->id,
        ]);
    }

    public function test_homeroom_teacher_cannot_verify_other_class_student(): void
    {
        Storage::fake('public');

        $siswa3 = User::where('username', 'siswa3')->first(); // siswa kelas XII RPL 2
        $walikelas1 = User::where('username', 'walikelas')->first(); // wali kelas XII RPL 1

        $leave = LeaveRequest::create([
            'student_id' => $siswa3->id,
            'applicant_id' => $siswa3->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Sakit kepala',
            'document_path' => 'leave_documents/dummy.png',
            'status_approval' => 'Pending',
        ]);

        // Wali kelas 1 mencoba memverifikasi siswa kelas 2 -> Wajib Ditolak 403 Forbidden
        $response = $this->actingAs($walikelas1)->patch(route('kesiswaan.leave-requests.verify', $leave->id), [
            'status' => 'Approved',
        ]);

        $response->assertStatus(403);
    }
}
