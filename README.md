# School Class Progress (SCP) 

> **Sistem Informasi Manajemen Pembelajaran Blok Teaching Factory (TeFa), Presensi & Jurnal Mengajar Terintegrasi**  
> Disiapkan untuk **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak (RPL) Paket 3 Tahun 2026**  
> Pengembang: **Faiz Baraka Putra** — **SMK Negeri 1 Bantul**  


---

## 📌 Daftar Isi
- [Tentang Sistem](#-tentang-sistem)
- [Fitur Utama Sistem](#-fitur-utama-sistem)
- [Matriks Hak Akses Pengguna (RBAC)](#-matriks-hak-akses-pengguna-rbac)
- [Akun Pengujian Default](#-akun-pengujian-default)
- [Tech Stack & Arsitektur](#-tech-stack--arsitektur)
- [Struktur Database & Algoritma Utama](#-struktur-database--algoritma-utama)
- [Panduan Instalasi & Menjalankan Aplikasi](#-panduan-instalasi--menjalankan-aplikasi)
- [Kalibrasi Data Master (Two-Stage Pipeline)](#-kalibrasi-data-master-two-stage-pipeline)
- [Automated Testing](#-automated-testing)
- [CI/CD & Deployment Pipeline](#-cicd--deployment-pipeline)
- [Lisensi & Hak Cipta](#-lisensi--hak-cipta)

---

## 📖 Tentang Sistem

**School Class Progress (SCP)** adalah platform command-center operasional sekolah berbasis web responsif dan REST API yang dirancang khusus untuk mengakomodasi model pembelajaran **Teaching Factory (TeFa)** di Sekolah Menengah Kejuruan (SMK) dengan skema **Jadwal Blok 2-Mingguan (Putaran 1 & Putaran 2)**.

SCP mengintegrasikan siklus penyusunan jadwal pelajaran mandiri guru, deteksi tabrakan waktu tiga dimensi, pencatatan jurnal mengajar harian berbasis foto bukti kegiatan, sistem peringatan dini keterlambatan jurnal, rekonsiliasi presensi otomatis dua arah dengan surat perizinan siswa, serta rekapitulasi laporan presensi siap cetak resmi A4.

---

## 🚀 Fitur Utama Sistem

Berdasarkan dokumen *Software Requirements Specification (SRS)* SCP Versi 1.0:

### 1. Autentikasi Dinamis & Rahasia Akademik (Academic Secrets)
- **Login Fleksibel**: Siswa masuk menggunakan NISN; Tenaga Pendidik dan staf masuk menggunakan NIP atau Username.
- **Pemulihan Kata Sandi Mandiri (Tanpa SMTP)**: Mekanisme reset kata sandi mandiri menggunakan verifikasi identitas (NISN/NIP) dan tanggal lahir tanpa ketergantungan pada server email keluar.
- **Proteksi Akses**: Penegakan hak akses bertingkat berbasis middleware peran (*Role-Based Access Control / RBAC*).

### 2. Saklar Putaran Blok (Cycle Toggle) & Penguncian Jadwal (Lock Schedule)
- **Saklar Putaran Global (1-Klik)**: Waka Kurikulum dapat mengganti siklus blok aktif antara **Putaran 1** dan **Putaran 2** secara terpusat. Seluruh tabel, jadwal harian, dan widget otomatis tersaring mengikuti putaran aktif.
- **Kunci Jadwal Mandiri**: Waka Kurikulum dapat mengunci pengisian jadwal mandiri guru setelah batas waktu berakhir. Saat terkunci, aksi tambah/edit/hapus jadwal dinonaktifkan di web dan ditolak dengan **HTTP 403 Forbidden** di REST API.

### 3. Algoritma Pencegahan Bentrok Jadwal 3 Dimensi (Conflict Prevention)
- Memeriksa irisan waktu secara matematis:
  $$\text{Bentrok} \iff (\text{Start}_A < \text{End}_B) \land (\text{End}_A > \text{Start}_B)$$
- Deteksi tumpang tindih dilakukan pada 3 dimensi sekaligus:
  1. **Dimensi Ruangan / Laboratorium** (`room_id`)
  2. **Dimensi Rombongan Belajar / Kelas** (`class_id`)
  3. **Dimensi Guru Pengampu** (`teacher_id`)
- Dilengkapi preset waktu kelipatan 15 menit dan kalkulasi durasi mengajar otomatis.

### 4. Import Jadwal Massal & Template Standar
- Mendukung import berkas kurikulum format `.xlsx`, `.xls`, `.csv`, hingga ekstraksi berkas `.pdf`.
- Dilengkapi template CSV standar siap unduh dan pembuatan akun otomatis untuk guru baru.

### 5. Jurnal Mengajar Harian & Bukti Foto Kelas
- Guru mencatat capaian materi pembelajaran dan kendala kelas secara real-time.
- Wajib melampirkan **foto bukti suasana kelas** (maksimal 2 MB, mendukung unggah berkas maupun kamera foto langsung).

### 6. Banner Peringatan Keterlambatan Jurnal (>30 Menit)
- Deteksi otomatis tanpa background cron job: jika jadwal guru hari ini telah selesai lebih dari 30 menit namun belum diisi jurnalnya, sistem memunculkan banner peringatan visual di bagian teratas dashboard guru lengkap dengan tombol **"Isi Jurnal Sekarang"**.
- Waka Kurikulum dan Admin dapat memantau daftar guru yang menunggak jurnal secara transparan.

### 7. Presensi & Sinkronisasi Dua Arah Otomatis (Two-Way Auto-Sync)
- **Arah 1 (Jurnal $\rightarrow$ Presensi)**: Saat jurnal disimpan, sistem otomatis membuat presensi satu kelas (default status **Hadir**), serta langsung menarik izin siswa berstatus **Approved** menjadi **Sakit** atau **Izin**.
- **Arah 2 (Perizinan $\rightarrow$ Presensi)**: Jika surat izin siswa baru disetujui oleh Wali Kelas atau Guru BK setelah jurnal dibuat, presensi pada tanggal tersebut otomatis diperbarui tanpa input ulang oleh guru.

### 8. Alur Pengajuan & Verifikasi Izin Siswa (Leave Request)
- Siswa / Orang Tua dapat mengajukan surat permohonan izin (**Sakit, Izin, Pulang Cepat, Kegiatan Luar**) disertai unggahan surat dokter/bukti fisik.
- Wali Kelas memverifikasi antrean siswa di rombel binaannya; Guru BK memverifikasi perizinan lintas rombel sekolah.

### 9. Rekapitulasi & Cetak Dokumen Laporan Presensi (Print Layout A4)
- Menghitung agregasi kehadiran siswa ($H, S, I, A$) dan persentase kehadiran:
  $$\text{Persentase Kehadiran} = \left(\frac{\text{Hadir}}{\text{Total Pertemuan}}\right) \times 100\%$$
- Halaman cetak resmi (*print layout*) berstandar surat kedinasan lengkap dengan kop sekolah dan kolom tanda tangan Wali Kelas & Waka Kurikulum.

### 10. Layanan REST API Mobile Terproteksi Sanctum
- Mendukung aplikasi klien mobile dengan Bearer Token autentikasi Laravel Sanctum:
  - `POST /api/login`: Login dinamis dengan NISN/NIP/Username.
  - `GET /api/me`: Mengambil profil pengguna yang terautentikasi.
  - `GET /api/schedules`: Mengambil jadwal mengajar guru pada putaran aktif.
  - `POST /api/schedules` & `DELETE /api/schedules/{id}`: CRUD jadwal mandiri dengan proteksi bentrok & proteksi kunci jadwal.
  - `POST /api/logout`: Pencabutan (*revocation*) token aktif.

---

## 👥 Matriks Hak Akses Pengguna (RBAC)

Sistem mengelola 6 peran pengguna (*Role-Based Access Control*):

| Modul / Fitur | Super Admin | Waka Kurikulum | Guru Pengampu | Wali Kelas | Guru BK | Siswa & Ortu |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Login Dinamis & Reset Rahasia Akademik | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Kelola Master Kelas, Ruangan, Mapel | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Saklar Putaran 1 & 2 (Cycle Toggle) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Kunci Jadwal Mandiri (Lock Schedule) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Import Jadwal Massal (Excel/CSV/PDF) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Input Jadwal Mandiri (+ Cek Bentrok) | ✅ | ✅ | ✅ *(Saat Terbuka)* | ❌ | ❌ | ❌ |
| Input Jurnal Mengajar + Foto Kelas | ✅ | 👁️ *(Monitoring)* | ✅ | ❌ | ❌ | ❌ |
| Alert Keterlambatan Jurnal (>30 mnt) | ✅ | ✅ *(Semua Guru)* | ✅ *(Jadwal Sendiri)* | ❌ | ❌ | ❌ |
| Pengajuan Surat Izin Online (+ Bukti) | ✅ | ❌ | ❌ | ✅ *(Bantuan)* | ❌ | ✅ |
| Verifikasi Izin (Approve / Reject) | ✅ | ❌ | ❌ | ✅ *(Rombel Binaan)* | ✅ *(Semua Rombel)* | ❌ |
| Rekapitulasi & Cetak Laporan A4 | ✅ | ✅ | ✅ *(Kelas Ajar)* | ✅ *(Rombel Binaan)* | ✅ | ❌ |
| Akses Layanan REST API Mobile | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |

---

## 🔐 Akun Pengujian Default

Akun berikut disiapkan otomatis melalui seeder database (`php artisan migrate:fresh --seed`):

| Peran (Role) | Username | Kata Sandi | Data Rahasia Akademik | Tugas & Tanggung Jawab Pengujian |
|---|---|---|---|---|
| **Super Admin** | `admin` *(atau Muzan)* | `smkn1bantul2026` | - | Hak akses penuh sistem, konfigurasi tingkat lanjut, audit log |
| **Waka Kurikulum** | `waka` | `smkn1bantul2026` | NIP: `197501012000031001`<br>Lahir: `1975-01-01` | Pengaturan putaran blok, kunci jadwal, master data, monitoring jurnal |
| **Guru Pengampu 1** | `guru1` | `smkn1bantul2026` | NIP: `198001012005011001`<br>Lahir: `1980-01-01` | Input jadwal mandiri, isi jurnal + foto suasana kelas, uji alert keterlambatan |
| **Guru Pengampu 2** | `guru2` | `smkn1bantul2026` | NIP: `198001012005011002`<br>Lahir: `1980-01-02` | Uji bentrok ruangan & kelas terhadap jadwal Guru 1 |
| **Wali Kelas** | `walikelas1` *(alias: walikelas)* | `smkn1bantul2026` | NIP: `198203032008011003`<br>Lahir: `1982-03-03` | Verifikasi approval surat izin kelas binaan, cetak rekap presensi A4 |
| **Guru BK** | `gurubk` | `smkn1bantul2026` | NIP: `198804042012012004`<br>Lahir: `1988-04-04` | Verifikasi izin lintas kelas, pemantauan siswa rawan alpa ( $\ge 3$ kali) |
| **Siswa Demo** | `202611001` *(atau siswa1)* | `smkn1bantul2026` | NISN: `202611001`<br>Lahir: `2008-05-14` | Pengajuan surat izin online, unggah surat dokter, pantau kehadiran |
| **Orang Tua Siswa** | `ortu_202611001` *(atau ortu1)* | `smkn1bantul2026` | NIK/NISN: `202611001`<br>Lahir: `1978-05-10` | Portal monitoring kehadiran anak, pengajuan izin atas nama anak |

> [!NOTE]
> Seluruh kata sandi bawaan adalah `smkn1bantul2026` (atau `password` pada profil uji tertentu). Pada akun yang diwajibkan aktivasi, pengguna akan diarahkan untuk memperbarui username dan kata sandi pada saat login pertama kali (`must_change_credentials = true`).

---

## 🛠️ Tech Stack & Arsitektur

### Backend Architecture
- **Framework**: Laravel 13.x (PHP 8.3 / PHP 8.4)
- **API Token Security**: Laravel Sanctum v4
- **Spreadsheet & PDF Processing**: `phpoffice/phpspreadsheet` & `smalot/pdfparser`
- **ORM & Database Driver**: Eloquent ORM dengan `pdo_mysql`

### Frontend Architecture
- **SPA Bridge**: Inertia.js v2 (`@inertiajs/react`)
- **View Library**: React 18 (TypeScript)
- **CSS Engine**: Tailwind CSS v3.4 + `@tailwindcss/forms`
- **UI Components & Icons**: Radix UI Primitives, Headless UI, Lucide React, React Icons
- **Client-Side Face Detection / Camera**: `picojs` & WebRTC Camera Modal

### Database & Infrastruktur
- **RDBMS**: MySQL 8.0+ / MariaDB 10.6+ (Composite Indexed)
- **Containerization**: Multi-stage Docker Alpine (Node 22 build $\rightarrow$ PHP 8.4-FPM + Nginx + Supervisord)
- **CI/CD & Orchestration**: GitHub Actions Pipeline, GitHub Container Registry (GHCR), K3s Lightweight Kubernetes VPS

---

## 💾 Struktur Database & Algoritma Utama

### Tabel Kunci Basis Data:
1. `roles`: Daftar 6 hak akses sistem.
2. `users`: Master akun pengguna, relasi role, NIP/NISN, nomor HP, foto profil, dan kredensial rahasia akademik.
3. `classes`: Rombongan belajar (X RPL 1, XI RPL 1, XII RPL 2, dsb.).
4. `rooms`: Ruangan teori & laboratorium praktik (Lab 16 RPL, Lab 18 RPL, Ruang Teori 1).
5. `system_configurations`: Tabel singleton konfigurasi (`active_cycle`, `is_schedule_locked`, `academic_year`).
6. `schedules`: Jadwal KBM dengan indeks komposit waktu (`teacher_id`, `class_id`, `room_id`, `day_of_week`, `cycle_number`, `start_time`, `end_time`).
7. `journals`: Catatan materi harian guru, deskripsi, kendala kelas, dan path berkas foto bukti kegiatan kelas.
8. `leave_requests`: Surat perizinan siswa dengan status (`Pending`, `Approved`, `Rejected`), tanggal, alasan, berkas bukti, dan `verified_by`.
9. `attendances`: Presensi harian per siswa per jurnal hasil sinkronisasi otomatis (`Hadir`, `Sakit`, `Izin`, `Alpha`).

---

## 💻 Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan Sistem
- PHP $\ge 8.3$ dengan ekstensi: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `intl`
- Composer 2.x
- Node.js $\ge 20.x$ & NPM
- MySQL 8.0+ atau MariaDB 10.6+

### Langkah Pemasangan:

1. **Clone Repository & Masuk ke Folder Proyek**:
   ```bash
   git clone https://github.com/ryzenkuro/scp-ukk.git
   cd scp-ukk
   ```

2. **Pasang Dependensi Backend & Frontend**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Berkas Lingkungan (`.env`)**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan konfigurasi koneksi database di file `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_scp_ukk
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Migrasi Database & Inisialisasi Seeder**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Hubungkan Symlink Penyimpanan Berkas**:
   ```bash
   php artisan storage:link
   ```

6. **Kompilasi Frontend & Jalankan Server**:
   ```bash
   # Terminal 1: Vite Dev Server
   npm run dev

   # Terminal 2: Laravel Server
   php artisan serve
   ```
   Aplikasi kini dapat diakses melalui browser di `http://127.0.0.1:8000`.

---

## 🔄 Kalibrasi Data Master (Two-Stage Pipeline)

Aplikasi memiliki pipeline kurasi data master resmi SMK Negeri 1 Bantul (91 Tenaga Pendidik resmi `guru.01` s/d `guru.91`, seluruh rombel kelas, siswa, orang tua, dan jadwal KBM putaran 1 & 2):

```bash
# 1. Sinkronkan seluruh data master resmi sekolah ke database
php artisan app:sync-master-data --only=all

# 2. Atau sinkronkan entitas tertentu:
php artisan app:sync-master-data --only=guru     # Khusus 91 guru resmi
php artisan app:sync-master-data --only=kelas    # Khusus rombel kelas
php artisan app:sync-master-data --only=siswa    # Khusus data siswa
php artisan app:sync-master-data --only=jadwal   # Khusus jadwal KBM putaran 1 & 2

# 3. Segarkan cache aplikasi
php artisan optimize:clear
```

---

## 🧪 Automated Testing

Sistem dilengkapi suite automated feature test komprehensif menggunakan PHPUnit:

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan pengujian spesifik rubrik UKK:
php artisan test --filter=Phase4JadwalTest                     # Validasi bentrok 3D & kunci jadwal
php artisan test --filter=AttendanceLeaveAndJournalSyncTest    # Validasi sinkronisasi 2 arah presensi & izin
php artisan test --filter=PackageBSelfieCameraTest             # Validasi bukti foto jurnal & kamera selfie
php artisan test --filter=PackageERestApiTest                  # Pengujian REST API mobile & token Sanctum
php artisan test --filter=TwoStagePipelineMasterDataTest       # Pengujian pipeline data master
```

---

## 🚀 CI/CD & Deployment Pipeline

Pipeline deployment otomatis dikonfigurasi melalui GitHub Actions (`.github/workflows/deploy.yml`):
1. **Build Multi-Stage**: Kompilasi aset Vite React dan pembuatan image Docker berbasis Alpine PHP 8.4.
2. **Push ke GHCR**: Penyimpanan image aman pada GitHub Container Registry.
3. **Automated Deploy via SSH**:
   - Rolling update deployment Kubernetes K3s pada namespace `ukk-production`.
   - Eksekusi otomatis migrasi database (`php artisan migrate --force`).
   - Eksekusi otomatis sinkronisasi master data (`php artisan app:sync-master-data`).
   - Pembersihan dan penyegaran cache aplikasi (`php artisan optimize:clear`).

---

## 📄 Lisensi & Hak Cipta

Dokumentasi dan perangkat lunak ini dikembangkan untuk keperluan Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak 2026.  
Hak Cipta © 2026 **Faiz Baraka Putra** — **SMK Negeri 1 Bantul**. Seluruh hak cipta dilindungi undang-undang.
