<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ __('Portal Perizinan Siswa') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Pengajuan surat keterangan sakit atau izin & pemantauan status kehadiran</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isParent ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                {{ $isParent ? '👨‍👩‍👦 Orang Tua Siswa' : '🎓 Siswa' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert Success -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-sm flex items-start gap-3">
                    <span class="text-xl">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-800">Berhasil!</h4>
                        <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Flash Alert Error -->
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="text-xl">⚠️</span>
                        <div>
                            <h4 class="text-sm font-bold text-red-800">Terdapat kesalahan pada input pengajuan:</h4>
                            <ul class="list-disc list-inside text-xs text-red-700 mt-1 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Info Profil Akun Siswa/Anak -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl {{ $isParent ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($user->fullname, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ $user->fullname }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Username: <span class="font-mono font-semibold text-gray-700">{{ $user->username }}</span>
                            @if ($isParent && $student)
                                | Menghubungkan Siswa: <span class="font-semibold text-indigo-600">{{ $student->fullname }}</span>
                            @endif
                            @if ($student?->schoolClass)
                                | Kelas: <span class="font-semibold text-emerald-600">{{ $student->schoolClass->class_name }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400">Total Pengajuan</span>
                    <p class="text-xl font-extrabold text-gray-800">{{ $leaveRequests->count() }} Surat</p>
                </div>
            </div>

            <!-- Grid 2 Kolom: Form Pengajuan & Riwayat Surat -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Formulir Pengajuan Izin Baru -->
                <div class="lg:col-span-5 bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                        <span class="text-xl">📝</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Form Pengajuan Izin / Sakit</h3>
                            <p class="text-xs text-gray-500">Unggah bukti surat keterangan dokter atau permohonan resmi</p>
                        </div>
                    </div>

                    @if ($isParent && ! $student)
                        <div class="p-4 bg-amber-50 rounded-xl text-xs text-amber-800 border border-amber-200">
                            ⚠️ Akun Anda belum terhubung dengan data profil siswa. Silakan hubungi Waka Kurikulum atau Tim Kesiswaan.
                        </div>
                    @else
                        <form method="POST" action="{{ route('portal.leave-requests.store') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            @if ($isParent && $student)
                                <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-100 text-xs">
                                    <span class="font-semibold text-indigo-900">Mengajukan Izin Untuk:</span>
                                    <p class="text-indigo-700 font-bold mt-0.5">{{ $student->fullname }} ({{ $student->schoolClass?->class_name ?? 'Tanpa Kelas' }})</p>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mulai Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                                        class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sampai Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required
                                        class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Alasan / Keterangan <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Jelaskan alasan izin (misal: Sakit demam berdarah / Acara keluarga mendesak)..."
                                    class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Unggah Surat Bukti (Dokter / Orang Tua) <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 flex justify-center px-4 pt-4 pb-4 border-2 border-dashed border-gray-200 rounded-xl hover:border-indigo-400 transition bg-slate-50/50">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-xs text-gray-600 justify-center">
                                            <label for="document" class="relative cursor-pointer bg-white rounded-md font-semibold text-indigo-600 hover:text-indigo-500 focus-within:outline-none px-2 py-0.5 border border-gray-200 shadow-2xs">
                                                <span>Pilih Berkas</span>
                                                <input id="document" name="document" type="file" required accept="image/*,application/pdf" class="sr-only">
                                            </label>
                                        </div>
                                        <p class="text-[11px] text-gray-400">JPG, PNG, atau PDF (Maksimal 2 MB)</p>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                                <span>📤 Kirim Pengajuan Surat Izin</span>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Riwayat Surat Perizinan -->
                <div class="lg:col-span-7 bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📋</span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Riwayat Pengajuan Izin</h3>
                                <p class="text-xs text-gray-500">Status verifikasi oleh Wali Kelas atau Guru BK</p>
                            </div>
                        </div>
                    </div>

                    @if ($leaveRequests->isEmpty())
                        <div class="text-center py-12 px-4">
                            <span class="text-4xl block mb-2">📭</span>
                            <h4 class="text-sm font-semibold text-gray-800">Belum Ada Pengajuan Surat Izin</h4>
                            <p class="text-xs text-gray-500 mt-1">Gunakan formulir di sebelah kiri untuk membuat pengajuan izin baru.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($leaveRequests as $leave)
                                <div class="p-4 rounded-xl border border-gray-100 hover:border-gray-200 bg-gray-50/50 transition">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-gray-800">
                                                📅 {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                                @if ($leave->start_date !== $leave->end_date)
                                                    s/d {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                                @endif
                                            </span>
                                            <span class="text-[10px] text-gray-400">
                                                ({{ \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1 }} Hari)
                                            </span>
                                        </div>

                                        <!-- Badge Status Approval -->
                                        <div>
                                            @if ($leave->status_approval === 'Pending')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    Menunggu Verifikasi
                                                </span>
                                            @elseif ($leave->status_approval === 'Approved')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Disetujui
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                    Ditolak
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Keterangan Alasan -->
                                    <p class="text-xs text-gray-700 mt-2 bg-white p-3 rounded-lg border border-gray-100">
                                        <span class="font-semibold text-gray-500 block text-[11px] mb-0.5">Alasan / Catatan:</span>
                                        {{ $leave->reason }}
                                    </p>

                                    <!-- Detail Pemohon, Dokumen & Verifikator -->
                                    <div class="mt-3 pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-3">
                                            <span class="text-gray-500">
                                                Diajukan oleh: <strong class="text-gray-700">{{ $leave->applicant->fullname }}</strong>
                                                ({{ $leave->applicant->role->role_name }})
                                            </span>

                                            <!-- Tombol Pratinjau Surat Dokumen -->
                                            <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank"
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-semibold underline text-xs">
                                                📎 Lihat Surat Bukti
                                            </a>
                                        </div>

                                        @if ($leave->verifier)
                                            <span class="text-gray-500 text-[11px]">
                                                Diverifikasi oleh: <strong class="text-gray-700">{{ $leave->verifier->fullname }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
