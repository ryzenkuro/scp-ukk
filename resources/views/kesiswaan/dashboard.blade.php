<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ __('Panel Verifikasi Perizinan Siswa') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Verifikasi surat permohonan izin & sakit dari siswa maupun orang tua
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isHomeroom ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800' }}">
                    {{ $isHomeroom ? '🏫 Wali Kelas: ' . ($user->schoolClass?->class_name ?? 'Belum Ditentukan') : '🛡️ Guru BK (Semua Kelas)' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert Success -->
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl shadow-xs flex items-start gap-3">
                    <span class="text-xl">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-800">Berhasil!</h4>
                        <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Flash Alert Error -->
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-xs">
                    <div class="flex items-start gap-3">
                        <span class="text-xl">⚠️</span>
                        <div>
                            <h4 class="text-sm font-bold text-red-800">Terjadi kendala:</h4>
                            <ul class="list-disc list-inside text-xs text-red-700 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stat Cards Barisan Ringkasan Status -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('kesiswaan.dashboard', ['status' => 'All']) }}"
                    class="bg-white p-5 rounded-2xl shadow-xs border transition hover:border-indigo-300 {{ $selectedStatus === 'All' ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-gray-100' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Total Pengajuan</span>
                        <span class="text-lg">📁</span>
                    </div>
                    <p class="text-2xl font-black text-gray-900 mt-2">{{ $stats['total'] }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">Seluruh berkas masuk</p>
                </a>

                <a href="{{ route('kesiswaan.dashboard', ['status' => 'Pending']) }}"
                    class="bg-white p-5 rounded-2xl shadow-xs border transition hover:border-amber-300 {{ $selectedStatus === 'Pending' ? 'ring-2 ring-amber-500 border-amber-500' : 'border-gray-100' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-700 uppercase">Perlu Verifikasi</span>
                        <span class="text-lg">⏳</span>
                    </div>
                    <p class="text-2xl font-black text-amber-600 mt-2">{{ $stats['pending'] }}</p>
                    <p class="text-[11px] text-amber-600 font-medium mt-1">Menunggu keputusan</p>
                </a>

                <a href="{{ route('kesiswaan.dashboard', ['status' => 'Approved']) }}"
                    class="bg-white p-5 rounded-2xl shadow-xs border transition hover:border-emerald-300 {{ $selectedStatus === 'Approved' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-gray-100' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-700 uppercase">Disetujui</span>
                        <span class="text-lg">✅</span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2">{{ $stats['approved'] }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">Siap sinkron presensi</p>
                </a>

                <a href="{{ route('kesiswaan.dashboard', ['status' => 'Rejected']) }}"
                    class="bg-white p-5 rounded-2xl shadow-xs border transition hover:border-red-300 {{ $selectedStatus === 'Rejected' ? 'ring-2 ring-red-500 border-red-500' : 'border-gray-100' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-red-700 uppercase">Ditolak</span>
                        <span class="text-lg">❌</span>
                    </div>
                    <p class="text-2xl font-black text-red-600 mt-2">{{ $stats['rejected'] }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">Tidak memenuhi syarat</p>
                </a>
            </div>

            <!-- Tabel Daftar Surat Izin Siswa -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Daftar Surat Izin Siswa
                            @if ($selectedStatus !== 'All')
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 ml-1">
                                    Status: {{ $selectedStatus }}
                                </span>
                            @endif
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $isHomeroom ? 'Menampilkan khusus siswa perwalian kelas ' . ($user->schoolClass?->class_name ?? '-') : 'Menampilkan siswa dari seluruh kelas binaan BK' }}
                        </p>
                    </div>

                    <!-- Filter Dropdown / Tab Cepat -->
                    <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-xl border border-gray-200 text-xs font-medium">
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'All']) }}"
                            class="px-3 py-1.5 rounded-lg transition {{ $selectedStatus === 'All' ? 'bg-white shadow-2xs text-gray-900 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
                            Semua
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Pending']) }}"
                            class="px-3 py-1.5 rounded-lg transition {{ $selectedStatus === 'Pending' ? 'bg-white shadow-2xs text-amber-700 font-bold' : 'text-gray-500 hover:text-amber-700' }}">
                            Pending ({{ $stats['pending'] }})
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Approved']) }}"
                            class="px-3 py-1.5 rounded-lg transition {{ $selectedStatus === 'Approved' ? 'bg-white shadow-2xs text-emerald-700 font-bold' : 'text-gray-500 hover:text-emerald-700' }}">
                            Disetujui
                        </a>
                        <a href="{{ route('kesiswaan.dashboard', ['status' => 'Rejected']) }}"
                            class="px-3 py-1.5 rounded-lg transition {{ $selectedStatus === 'Rejected' ? 'bg-white shadow-2xs text-red-700 font-bold' : 'text-gray-500 hover:text-red-700' }}">
                            Ditolak
                        </a>
                    </div>
                </div>

                @if ($leaveRequests->isEmpty())
                    <div class="text-center py-16 px-4">
                        <span class="text-4xl block mb-2">📭</span>
                        <h4 class="text-sm font-semibold text-gray-800">Tidak Ada Data Pengajuan Izin</h4>
                        <p class="text-xs text-gray-500 mt-1">Belum ada siswa yang mengajukan perizinan pada kategori status ini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50/75 text-gray-600 text-xs font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Siswa & Kelas</th>
                                    <th class="px-6 py-3.5 text-left">Pemohon</th>
                                    <th class="px-6 py-3.5 text-left">Rentang Waktu</th>
                                    <th class="px-6 py-3.5 text-left">Alasan & Dokumen</th>
                                    <th class="px-6 py-3.5 text-left">Status Saat Ini</th>
                                    <th class="px-6 py-3.5 text-center">Aksi Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($leaveRequests as $leave)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <!-- Siswa & Kelas -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {{ strtoupper(substr($leave->student->fullname, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900 block text-xs sm:text-sm">{{ $leave->student->fullname }}</span>
                                                    <span class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                                        {{ $leave->student->schoolClass?->class_name ?? 'Tanpa Kelas' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Pemohon -->
                                        <td class="px-6 py-4 text-xs text-gray-600">
                                            <span class="font-medium text-gray-800 block">{{ $leave->applicant->fullname }}</span>
                                            <span class="text-[11px] text-gray-400 capitalize">
                                                ({{ str_replace('_', ' ', $leave->applicant->role->role_name) }})
                                            </span>
                                        </td>

                                        <!-- Rentang Waktu -->
                                        <td class="px-6 py-4 text-xs">
                                            <span class="font-mono font-bold text-gray-800 block">
                                                {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                            </span>
                                            @if ($leave->start_date !== $leave->end_date)
                                                <span class="text-gray-400 text-[11px]">s/d</span>
                                                <span class="font-mono font-bold text-gray-800 block">
                                                    {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                                </span>
                                            @endif
                                            <span class="text-[10px] text-indigo-600 font-semibold block mt-0.5">
                                                {{ \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1 }} Hari
                                            </span>
                                        </td>

                                        <!-- Alasan & Dokumen -->
                                        <td class="px-6 py-4">
                                            <p class="text-xs text-gray-700 max-w-xs line-clamp-2" title="{{ $leave->reason }}">
                                                {{ $leave->reason }}
                                            </p>
                                            <div class="mt-1.5">
                                                <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded transition">
                                                    📄 Pratinjau Surat
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="px-6 py-4">
                                            @if ($leave->status_approval === 'Pending')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    Pending
                                                </span>
                                            @elseif ($leave->status_approval === 'Approved')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Disetujui
                                                </span>
                                                <span class="block text-[10px] text-gray-400 mt-1">
                                                    Oleh: {{ $leave->verifier?->fullname ?? '-' }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                    Ditolak
                                                </span>
                                                <span class="block text-[10px] text-gray-400 mt-1">
                                                    Oleh: {{ $leave->verifier?->fullname ?? '-' }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Aksi Verifikasi (Approve / Reject) -->
                                        <td class="px-6 py-4 text-center">
                                            @if ($leave->status_approval === 'Pending')
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- Tombol Approve -->
                                                    <form method="POST" action="{{ route('kesiswaan.leave-requests.verify', $leave->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="Approved">
                                                        <button type="submit"
                                                            onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI izin siswa {{ $leave->student->fullname }}?')"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-2xs"
                                                            title="Setujui Izin">
                                                            ✓ Setujui
                                                        </button>
                                                    </form>

                                                    <!-- Tombol Reject -->
                                                    <form method="POST" action="{{ route('kesiswaan.leave-requests.verify', $leave->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="Rejected">
                                                        <button type="submit"
                                                            onclick="return confirm('Apakah Anda yakin ingin MENOLAK izin siswa {{ $leave->student->fullname }}?')"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-lg transition shadow-2xs"
                                                            title="Tolak Izin">
                                                            ✗ Tolak
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">
                                                    Selesai diverifikasi
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="p-4 border-t border-gray-100">
                        {{ $leaveRequests->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
