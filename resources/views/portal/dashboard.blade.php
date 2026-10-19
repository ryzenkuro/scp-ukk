<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ __('Portal Layanan Siswa & Orang Tua') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Pengajuan Surat Izin & Pemantauan Kehadiran</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isParent ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                {{ $isParent ? '👨‍👩‍👦 Akun Orang Tua / Wali' : '🎓 Akun Siswa' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message Alerts -->
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

            <!-- Info Profil Siswa / Anak -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600 shrink-0">
                        {{ strtoupper(substr($student?->fullname ?? auth()->user()->fullname, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $isParent ? ($student?->fullname ?? 'Data Anak Belum Terhubung') : auth()->user()->fullname }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Username: <span class="font-mono text-gray-700">{{ $student?->username ?? auth()->user()->username }}</span>
                            | Kelas: <span class="font-semibold text-indigo-600">{{ $student?->schoolClass?->class_name ?? 'Belum terdaftar di kelas' }}</span>
                            @if($isParent)
                                <span class="ml-2 text-purple-700 font-medium">(Orang Tua: {{ auth()->user()->fullname }})</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium text-gray-600">
                        Total Izin: <strong class="text-gray-900">{{ $leaves->count() }}</strong>
                    </span>
                    <span class="px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-700">
                        Disetujui: <strong>{{ $leaves->where('status_approval', 'Approved')->count() }}</strong>
                    </span>
                    <span class="px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-xs font-medium text-amber-700">
                        Pending: <strong>{{ $leaves->where('status_approval', 'Pending')->count() }}</strong>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- Kolom 1: Formulir Pengajuan Izin -->
                <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 lg:col-span-1">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                        <div>
                            <h4 class="font-bold text-base text-gray-900">Form Surat Izin / Sakit</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Ajukan perizinan tidak masuk sekolah</p>
                        </div>
                        <span class="text-2xl">📝</span>
                    </div>

                    <form method="POST" action="{{ route('portal.leaves.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Tanggal Mulai -->
                        <div>
                            <label for="start_date" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Tanggal Mulai <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                                class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label for="end_date" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Tanggal Selesai <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required
                                class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        </div>

                        <!-- Alasan / Keterangan -->
                        <div>
                            <label for="reason" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Keterangan / Alasan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="reason" name="reason" rows="3" required placeholder="Contoh: Sakit demam dan flu, sedang istirahat di rumah sesuai anjuran dokter..."
                                class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">{{ old('reason') }}</textarea>
                        </div>

                        <!-- Upload Berkas Surat / Dokumen -->
                        <div>
                            <label for="document" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                Bukti Surat / Ket. Dokter <span class="text-red-500">*</span>
                            </label>
                            <input type="file" id="document" name="document" accept="image/jpeg,image/png,image/jpg,application/pdf" required
                                class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-xl cursor-pointer">
                            <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, atau PDF. Maks 2MB.</p>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-xs transition duration-150 flex items-center justify-center gap-2">
                            <span>🚀</span> Kirim Pengajuan Izin
                        </button>
                    </form>
                </div>

                <!-- Kolom 2: Riwayat Pengajuan Izin -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 lg:col-span-2 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-base text-gray-900">Riwayat Pengajuan Surat Izin</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Daftar izin yang pernah diajukan dan status verifikasinya</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-lg">
                            {{ $leaves->count() }} Data
                        </span>
                    </div>

                    @if ($leaves->isEmpty())
                        <div class="p-12 text-center">
                            <span class="text-4xl block mb-2">📭</span>
                            <h5 class="text-sm font-semibold text-gray-900">Belum Ada Pengajuan Surat</h5>
                            <p class="text-xs text-gray-500 mt-1">Gunakan formulir di sebelah kiri untuk mengajukan izin atau surat keterangan sakit.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50/70">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rentang Tanggal</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Alasan / Keterangan</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bukti Surat</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($leaves as $leave)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <!-- Rentang Tanggal -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-semibold text-gray-900">
                                                    {{ $leave->start_date->translatedFormat('d M Y') }}
                                                    @if($leave->start_date->ne($leave->end_date))
                                                        - {{ $leave->end_date->translatedFormat('d M Y') }}
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    Diajukan: {{ $leave->created_at->diffForHumans() }}
                                                    <span class="text-gray-400">({{ $leave->applicant->id === $leave->student->id ? 'Oleh Siswa' : 'Oleh Orang Tua' }})</span>
                                                </div>
                                            </td>

                                            <!-- Alasan -->
                                            <td class="px-6 py-4">
                                                <p class="text-xs text-gray-700 max-w-xs break-words leading-relaxed">{{ $leave->reason }}</p>
                                            </td>

                                            <!-- Bukti Surat -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-indigo-600 hover:text-indigo-800 text-xs font-semibold rounded-lg border border-gray-200 transition">
                                                    <span>📎</span> Lihat Dokumen
                                                </a>
                                            </td>

                                            <!-- Status Approval -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($leave->status_approval === 'Approved')
                                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Disetujui
                                                    </span>
                                                    @if($leave->verifier)
                                                        <p class="text-xs text-gray-400 mt-1">Oleh: {{ $leave->verifier->fullname }}</p>
                                                    @endif
                                                @elseif ($leave->status_approval === 'Rejected')
                                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span> Ditolak
                                                    </span>
                                                    @if($leave->verifier)
                                                        <p class="text-xs text-gray-400 mt-1">Oleh: {{ $leave->verifier->fullname }}</p>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span> Menunggu Verifikasi
                                                    </span>
                                                @endif
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
    </div>
</x-app-layout>
