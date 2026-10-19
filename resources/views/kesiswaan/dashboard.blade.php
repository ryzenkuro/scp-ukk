<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ __('Panel Tim Kesiswaan - Verifikasi Perizinan Siswa') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $isWaliKelas ? 'Perwalian: ' . (auth()->user()->schoolClass?->class_name ?? 'Belum terhubung kelas') : 'Bimbingan Konseling (Pemantauan Seluruh Kelas)' }}
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isWaliKelas ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                {{ $isWaliKelas ? '👨‍🏫 Wali Kelas' : '👩‍💼 Guru Bimbingan Konseling (BK)' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert Messages -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-xs">
                    <div class="flex items-center">
                        <span class="text-emerald-700 font-semibold text-sm">✓ {{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-xs">
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Section 1: Ringkasan Status Verifikasi (Stats Overview) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pending Card -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-amber-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Menunggu Verifikasi</span>
                        <p class="text-3xl font-extrabold text-amber-600 mt-1">{{ $totalPending }}</p>
                        <p class="text-xs text-gray-500 mt-1">Perlu tindakan persetujuan</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-2xl">
                        ⏳
                    </div>
                </div>

                <!-- Approved Card -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-emerald-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Telah Disetujui</span>
                        <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $totalApproved }}</p>
                        <p class="text-xs text-gray-500 mt-1">Otomatis sinkron ke presensi guru</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                        ✅
                    </div>
                </div>

                <!-- Rejected Card -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-red-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-red-600">Ditolak</span>
                        <p class="text-3xl font-extrabold text-red-600 mt-1">{{ $totalRejected }}</p>
                        <p class="text-xs text-gray-500 mt-1">Izin tidak valid / dokumen kurang</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-2xl">
                        ❌
                    </div>
                </div>
            </div>

            <!-- Section 2: Tabel Verifikasi Permohonan Izin -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-base text-gray-900">Daftar Pengajuan Surat Izin & Sakit</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $isWaliKelas ? 'Menampilkan permohonan dari siswa kelas perwalian Anda (' . (auth()->user()->schoolClass?->class_name ?? '-') . ')' : 'Menampilkan permohonan dari seluruh kelas (Bimbingan Konseling)' }}
                        </p>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-xl border border-gray-200">
                        <a href="{{ route('kesiswaan.dashboard') }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ empty($statusFilter) ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                            Semua ({{ $leaves->count() }})
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Pending']) }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $statusFilter === 'Pending' ? 'bg-white text-amber-700 shadow-xs' : 'text-gray-600 hover:text-amber-700' }}">
                            Pending ({{ $totalPending }})
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Approved']) }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $statusFilter === 'Approved' ? 'bg-white text-emerald-700 shadow-xs' : 'text-gray-600 hover:text-emerald-700' }}">
                            Disetujui
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Rejected']) }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $statusFilter === 'Rejected' ? 'bg-white text-red-700 shadow-xs' : 'text-gray-600 hover:text-red-700' }}">
                            Ditolak
                        </a>
                    </div>
                </div>

                @if ($leaves->isEmpty())
                    <div class="p-12 text-center">
                        <span class="text-4xl block mb-2">🎉</span>
                        <h5 class="text-sm font-semibold text-gray-900">Tidak Ada Data Pengajuan</h5>
                        <p class="text-xs text-gray-500 mt-1">Saat ini tidak ada surat perizinan siswa yang sesuai dengan filter yang dipilih.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50/70">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identitas Siswa</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rentang Izin</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan / Alasan</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bukti Surat</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status & Verifikator</th>
                                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($leaves as $leave)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <!-- Siswa -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-gray-900">{{ $leave->student->fullname }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                Kelas: <span class="font-semibold text-indigo-600">{{ $leave->student->schoolClass?->class_name ?? '-' }}</span>
                                                | NIS/Username: <span class="font-mono text-gray-700">{{ $leave->student->username }}</span>
                                            </div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                Diajukan oleh: <span class="font-medium text-gray-600">{{ $leave->applicant->id === $leave->student->id ? 'Siswa Sendiri' : 'Orang Tua (' . $leave->applicant->fullname . ')' }}</span>
                                            </div>
                                        </td>

                                        <!-- Rentang Tanggal -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-semibold text-gray-900">
                                                {{ $leave->start_date->translatedFormat('d M Y') }}
                                                @if($leave->start_date->ne($leave->end_date))
                                                    - {{ $leave->end_date->translatedFormat('d M Y') }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                Durasi: {{ $leave->start_date->diffInDays($leave->end_date) + 1 }} Hari
                                            </div>
                                        </td>

                                        <!-- Alasan -->
                                        <td class="px-6 py-4">
                                            <p class="text-xs text-gray-700 max-w-xs break-words leading-relaxed">{{ $leave->reason }}</p>
                                        </td>

                                        <!-- Bukti Surat -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 hover:text-indigo-900 text-xs font-semibold rounded-lg border border-indigo-200 transition">
                                                <span>📎</span> Lihat Berkas
                                            </a>
                                        </td>

                                        <!-- Status & Verifikator -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($leave->status_approval === 'Approved')
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Disetujui
                                                </span>
                                                @if($leave->verifier)
                                                    <p class="text-xs text-gray-500 mt-1">Oleh: {{ $leave->verifier->fullname }}</p>
                                                @endif
                                            @elseif ($leave->status_approval === 'Rejected')
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span> Ditolak
                                                </span>
                                                @if($leave->verifier)
                                                    <p class="text-xs text-gray-500 mt-1">Oleh: {{ $leave->verifier->fullname }}</p>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span> Menunggu Verifikasi
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Tindakan Approve / Reject -->
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @if ($leave->status_approval !== 'Approved')
                                                    <form method="POST" action="{{ route('kesiswaan.leaves.verify', $leave->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="Approved">
                                                        <button type="submit" onclick="return confirm('Apakah Anda yakin menyetujui pengajuan izin ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                                                            <span>✓</span> Setujui
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($leave->status_approval !== 'Rejected')
                                                    <form method="POST" action="{{ route('kesiswaan.leaves.verify', $leave->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="Rejected">
                                                        <button type="submit" onclick="return confirm('Apakah Anda yakin menolak pengajuan izin ini?')"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                                                            <span>✕</span> Tolak
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
