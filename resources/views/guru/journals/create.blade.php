<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <a href="{{ route('guru.journals.index') }}" class="hover:underline">Jurnal Mengajar</a>
                    <span>/</span>
                    <span class="text-gray-700 font-semibold">Buka Kelas & Isi Presensi</span>
                </div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>📝</span> Form Jurnal Mengajar & Presensi Kelas
                </h2>
            </div>
            <a href="{{ route('guru.journals.index') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                ← Kembali ke Daftar Sesi
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Ringkasan Informasi Sesi Kelas -->
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white p-6 rounded-2xl shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4 mb-4">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            {{ $schedule->cycle_type }}
                        </span>
                        <h3 class="text-xl font-bold mt-1 text-white">{{ $schedule->subject }}</h3>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-indigo-300 block">Tanggal Pelaksanaan:</span>
                        <span class="text-sm font-bold text-white">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <span class="text-gray-400 block mb-1">Kelas</span>
                        <strong class="text-base text-white">{{ $schedule->schoolClass->class_name ?? 'N/A' }}</strong>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <span class="text-gray-400 block mb-1">Ruangan</span>
                        <strong class="text-base text-white">{{ $schedule->room->room_name ?? 'N/A' }}</strong>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <span class="text-gray-400 block mb-1">Waktu Sesi</span>
                        <strong class="text-base text-white font-mono">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</strong>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <span class="text-gray-400 block mb-1">Total Siswa Terdaftar</span>
                        <strong class="text-base text-white">{{ $students->count() }} Siswa</strong>
                    </div>
                </div>
            </div>

            <!-- Error Validation List -->
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm">
                    <div class="flex items-start gap-2">
                        <span class="text-red-600 text-lg">⚠️</span>
                        <div>
                            <h4 class="text-red-800 font-bold text-sm">Mohon periksa input berikut:</h4>
                            <ul class="list-disc list-inside text-xs text-red-700 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form Utama Jurnal & Presensi -->
            <form method="POST" action="{{ route('guru.journals.store', $schedule->id) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- BAGIAN 1: Topik Pembelajaran & Upload Foto Kamera Realtime -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-5">
                    <div class="border-b pb-3">
                        <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                            <span>1️⃣</span> Materi Pembelajaran & Bukti Dokumentasi Kelas Realtime
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Isi ringkasan kompetensi dasar/materi yang disampaikan dan unggah foto kegiatan siswa di kelas.
                        </p>
                    </div>

                    <!-- Input Deskripsi Materi -->
                    <div>
                        <label for="topic_description" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Ringkasan Materi / Topik Pembelajaran <span class="text-red-500">*</span>
                        </label>
                        <textarea id="topic_description" name="topic_description" rows="3" required
                                  placeholder="Tuliskan topik bahasan, materi ajar, atau kompetensi keahlian yang disampaikan pada pertemuan ini (Minimal 5 karakter)..."
                                  class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">{{ old('topic_description') }}</textarea>
                        @error('topic_description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input Foto Realtime dengan Kamera Smartphone Support -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Foto Bukti Pembelajaran Realtime <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">
                            Dapat langsung memotret menggunakan kamera HP atau memilih berkas foto dari perangkat (Maks. 2MB, JPG/PNG/WebP).
                        </p>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <!-- Custom File Upload Button -->
                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-xl border border-indigo-200 transition">
                                <span class="text-lg">📷</span>
                                <span>Ambil Foto / Pilih Berkas</span>
                                <input type="file" 
                                       id="photo" 
                                       name="photo" 
                                       accept="image/*" 
                                       capture="environment" 
                                       required 
                                       class="hidden" 
                                       onchange="handlePhotoSelect(event)">
                            </label>
                            <span id="fileNameDisplay" class="text-xs text-gray-500 italic">Belum ada foto yang dipilih.</span>
                        </div>

                        <!-- Pratinjau Foto Live -->
                        <div id="photoPreviewContainer" class="hidden mt-4">
                            <div class="inline-block relative rounded-xl overflow-hidden border-2 border-indigo-500/50 shadow-md">
                                <img id="photoPreview" src="" alt="Pratinjau Foto" class="max-h-64 object-cover rounded-lg">
                                <div class="absolute bottom-0 inset-x-0 bg-black/60 backdrop-blur-xs text-white text-[11px] py-1 px-3 text-center">
                                    📸 Foto Siap Diunggah
                                </div>
                            </div>
                        </div>

                        @error('photo')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- BAGIAN 2: Daftar Presensi Siswa & Auto-Sync Izin Terverifikasi -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b pb-3">
                        <div>
                            <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                                <span>2️⃣</span> Presensi Siswa & Sinkronisasi Izin Otomatis
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Siswa dengan izin/sakit yang telah disetujui (Approved) oleh Kesiswaan/Wali Kelas otomatis disinkronkan dan dikunci.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-50 text-emerald-800 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir Default
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-50 text-amber-800 font-semibold">
                                🔒 Izin Auto-Sync
                            </span>
                        </div>
                    </div>

                    @if ($students->isEmpty())
                        <div class="text-center py-8 text-gray-500 text-xs">
                            <span class="text-3xl block mb-1">👥</span>
                            Tidak ada data siswa yang terdaftar di kelas ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-700 uppercase font-bold text-[11px] border-y">
                                    <tr>
                                        <th class="py-3 px-3 w-12 text-center">No</th>
                                        <th class="py-3 px-4">Nama Siswa</th>
                                        <th class="py-3 px-4">Status Verifikasi Kesiswaan</th>
                                        <th class="py-3 px-4 text-center">Pilihan Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($students as $index => $student)
                                        @php
                                            $leave = $approvedLeaves->get($student->id);
                                            $hasApprovedLeave = (bool) $leave;
                                            
                                            // Tentukan status default jika ada izin terverifikasi
                                            if ($hasApprovedLeave) {
                                                $autoStatus = str_contains(strtolower($leave->reason), 'sakit') ? 'Sakit' : 'Izin';
                                            } else {
                                                $autoStatus = 'Hadir';
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-50/70 transition {{ $hasApprovedLeave ? 'bg-amber-50/30' : '' }}">
                                            <td class="py-3 px-3 text-center font-semibold text-gray-500">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-gray-900">{{ $student->fullname }}</div>
                                                <div class="text-[11px] text-gray-500">NIS / Username: <span class="font-mono">{{ $student->username }}</span></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if ($hasApprovedLeave)
                                                    <div class="inline-flex flex-col gap-1">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                            🔒 Izin/Sakit Terverifikasi
                                                        </span>
                                                        <span class="text-[11px] text-amber-800 italic">
                                                            Alasan: "{{ $leave->reason }}"
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-500">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Tidak ada pengajuan izin
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                @if ($hasApprovedLeave)
                                                    <!-- Jika ada surat izin resmi yang Approved, kunci opsinya agar tidak diubah jadi Alpha -->
                                                    <input type="hidden" name="attendances[{{ $student->id }}]" value="{{ $autoStatus }}">
                                                    <div class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-900 font-bold px-3 py-1.5 rounded-lg border border-amber-300 text-xs shadow-2xs">
                                                        <span>🔒</span> {{ $autoStatus }} (Terkunci Sesuai Izin)
                                                    </div>
                                                @else
                                                    <!-- Opsi manual untuk siswa reguler -->
                                                    <div class="inline-flex items-center gap-3">
                                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                                            <input type="radio" 
                                                                   name="attendances[{{ $student->id }}]" 
                                                                   value="Hadir" 
                                                                   checked 
                                                                   class="text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                                                            <span class="font-bold text-emerald-700">Hadir</span>
                                                        </label>

                                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                                            <input type="radio" 
                                                                   name="attendances[{{ $student->id }}]" 
                                                                   value="Izin" 
                                                                   class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                                            <span class="font-bold text-blue-700">Izin</span>
                                                        </label>

                                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                                            <input type="radio" 
                                                                   name="attendances[{{ $student->id }}]" 
                                                                   value="Sakit" 
                                                                   class="text-amber-600 focus:ring-amber-500 h-4 w-4">
                                                            <span class="font-bold text-amber-700">Sakit</span>
                                                        </label>

                                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                                            <input type="radio" 
                                                                   name="attendances[{{ $student->id }}]" 
                                                                   value="Alpha" 
                                                                   class="text-red-600 focus:ring-red-500 h-4 w-4">
                                                            <span class="font-bold text-red-700">Alpha</span>
                                                        </label>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Tombol Submit Simpan Jurnal & Presensi -->
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-gray-500">
                        Pastikan seluruh data presensi dan foto kelas telah sesuai sebelum menekan tombol simpan.
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a href="{{ route('guru.journals.index') }}" class="w-full sm:w-auto text-center px-4 py-2.5 bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl border border-gray-300 transition">
                            Batal
                        </a>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                            <span>💾</span> Simpan Jurnal & Finalisasi Presensi
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <!-- Script JavaScript untuk Instant Live Image Preview -->
    <script>
        function handlePhotoSelect(event) {
            const input = event.target;
            const display = document.getElementById('fileNameDisplay');
            const previewContainer = document.getElementById('photoPreviewContainer');
            const preview = document.getElementById('photoPreview');

            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Cek ukuran file (Maks 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2MB.');
                    input.value = '';
                    display.textContent = 'Ukuran file melebihi 2MB. Silakan pilih foto lain.';
                    display.classList.add('text-red-600');
                    previewContainer.classList.add('hidden');
                    return;
                }

                display.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                display.classList.remove('text-red-600');
                display.classList.add('text-emerald-700', 'font-medium');

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                display.textContent = 'Belum ada foto yang dipilih.';
                previewContainer.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
