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

        // Bersihkan data izin sisa testing sebelumnya agar tidak bentrok anti-overlap
        LeaveRequest::where('student_id', $siswa1->id)->delete();

        // 2. Test Siswa Mengajukan Izin (Jenis Sakit)
        $fileSiswa = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $responseSiswa = $this->actingAs($siswa1)->post(route('portal.leave-requests.store'), [
            'leave_type' => 'Sakit',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Demam dan perlu istirahat dokter',
            'document' => $fileSiswa,
        ]);

        $responseSiswa->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'leave_type' => 'Sakit',
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

        // 4. Test Proteksi Anti-Overlap Tanggal Izin Aktif Siswa (KF53-KF56 / FASE 08)
        // Siswa sudah punya izin Approved pada now() s/d now()+2
        // Coba ajukan izin baru yang bertabrakan pada rentang tersebut
        $fileOverlap = UploadedFile::fake()->image('surat_overlap.jpg');
        $responseOverlap = $this->actingAs($siswa1)->post(route('portal.leave-requests.store'), [
            'leave_type' => 'Izin',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Izin bertabrakan dengan izin sakit sebelumnya',
            'document' => $fileOverlap,
        ]);

        $responseOverlap->assertSessionHasErrors(['start_date']);

        // 5. Test Orang Tua Mengajukan Izin untuk Anaknya (Non-overlap, Rentang Bebas)
        $fileOrtu = UploadedFile::fake()->image('surat_ortu.png');

        $responseOrtu = $this->actingAs($ortu1)->post(route('portal.leave-requests.store'), [
            'leave_type' => 'Izin',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'reason' => 'Ada acara keluarga di luar kota',
            'document' => $fileOrtu,
        ]);

        $responseOrtu->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswa1->id,
            'applicant_id' => $ortu1->id,
            'leave_type' => 'Izin',
            'reason' => 'Ada acara keluarga di luar kota',
            'status_approval' => 'Pending',
        ]);

        $leaveOrtu = LeaveRequest::where('applicant_id', $ortu1->id)->latest()->first();

        // 6. Test Guru BK Meng-Approve Izin
        $responseBk = $this->actingAs($gurubk)->patch(route('kesiswaan.leave-requests.verify', $leaveOrtu->id), [
            'status' => 'Approved',
        ]);

        $responseBk->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveOrtu->id,
            'status_approval' => 'Approved',
            'verified_by' => $gurubk->id,
        ]);

        // 7. Test Pengajuan Pulang Cepat dengan Leave Time (KF55)
        $filePulangCepat = UploadedFile::fake()->create('surat_pulang.pdf', 300, 'application/pdf');
        $responsePulang = $this->actingAs($siswa1)->post(route('portal.leave-requests.store'), [
            'leave_type' => 'Pulang Cepat',
            'leave_time' => '11:30',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'reason' => 'Ada jadwal kontrol gigi ke rumah sakit',
            'document' => $filePulangCepat,
        ]);

        $responsePulang->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswa1->id,
            'leave_type' => 'Pulang Cepat',
            'leave_time' => '11:30:00',
            'status_approval' => 'Pending',
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
            'leave_type' => 'Sakit',
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
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

    public function test_homeroom_teacher_and_bk_can_reject_leave_and_record_verifier(): void
    {
        $siswa1 = User::where('username', 'siswa1')->first();
        $walikelas1 = User::where('username', 'walikelas')->first();

        $leave = LeaveRequest::create([
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'leave_type' => 'Izin',
            'start_date' => now()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'reason' => 'Izin tanpa keterangan jelas',
            'document_path' => 'leave_documents/dummy.png',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($walikelas1)->patch(route('kesiswaan.leave-requests.verify', $leave->id), [
            'status' => 'Rejected',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status_approval' => 'Rejected',
            'verified_by' => $walikelas1->id,
        ]);
    }
}
