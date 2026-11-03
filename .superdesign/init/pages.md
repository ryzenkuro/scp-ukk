# Pages — dependency trees (Blade)

Blade "dependencies" = root layout component + x-components used + included partials. Every authed page tree therefore includes:
`x-app-layout` → `layouts/app.blade.php` → [`layouts/navigation.blade.php` → x-nav-link, x-dropdown, x-dropdown-link, x-responsive-nav-link, x-application-logo] + [`layouts/bottom-navigation.blade.php`]. Guest pages: `x-guest-layout` → `layouts/guest.blade.php` → x-application-logo.

Feature pages are written as raw HTML inside `x-app-layout` (they do NOT reuse the Breeze primitives beyond the layout).

## /login — `resources/views/auth/login.blade.php` — READ FULLY
Guest layout. Centered white card: session status line; form POST /login: label+input `username` (text, autofocus), label+input `password`, Remember-me checkbox (indigo accent), footer row: "Forgot your password?" link + dark Log in button. Labels rendered lowercase "username" (literal `__('username')`).
Deps: x-guest-layout, x-auth-session-status, x-input-label, x-text-input, x-input-error, x-primary-button.

## /guru/dashboard — `resources/views/guru/dashboard.blade.php` (218 lines) — READ FULLY
Page-header band (white): title "👨‍🏫 Portal Guru - School Class Progress" + subtitle "Sistem Manajemen Pembelajaran Blok Teaching Factory" + right pill badge "Guru Mata Pelajaran" (indigo-100).
Body (max-w-7xl, space-y-6):
1. OVERDUE ALERT (conditional `$overdueSchedules`): red card `bg-red-50 border-l-4 border-red-600 rounded-2xl` with ⚠️, count badge, per-session lines, CTA button red-600.
2. WELCOME CARD: white rounded-2xl; "Selamat Datang, {name}!"; Siklus Aktif chip (indigo) + lock-status chip (red 🔒 / emerald 🔓); 3 action buttons right: 📅 Atur Jadwal (gray), 📝 Jurnal & Presensi (indigo-600), 📊 Rekap Presensi (emerald outline).
3. "🕒 Sesi Mengajar Hari Ini" card: header + count chip; responsive grid 1/2/3 of SESSION CARDS — each: status chip (✅ Jurnal Terisi emerald / ⏳ Belum Diisi amber / ⚠️ Terlambat red+animate-pulse+ring), time mono chip, subject bold, class • room, footer CTA button (emerald "Lihat Rekap Presensi →" / indigo|red "Isi Jurnal & Presensi →"); empty state 🏖️.
4. Two QUICK CARDS grid: 📅 Manajemen Jadwal Mandiri (dark button), 📝 Buka Kelas & Jurnal Mengajar (indigo button); each w/ count/category chip.
Deps: x-app-layout (+ its tree above). Raw HTML only.

## /portal/dashboard — `resources/views/portal/dashboard.blade.php` (271 lines) — READ FULLY
Header band: "Portal Perizinan Siswa" + subtitle; right pill 🎓 Siswa / 👨‍👩‍👦 Orang Tua (emerald|amber).
Body:
1. Flash cards: success emerald `border-l-4`; validation errors red list.
2. PROFILE CARD: initials avatar chip 48px (emerald/amber), fullname, username mono, linked student + class, right "Total Pengajuan N Surat".
3. 12-col grid: LEFT col-5 FORM card "Form Pengajuan Izin / Sakit": select leave_type (4 emoji options: 🩹 Sakit / 📝 Izin / ⏱️ Pulang Cepat / 🏆 Kegiatan Luar), date range (2 inputs), CONDITIONAL amber time block (Alpine x-show for Pulang Cepat|Kegiatan Luar), reason textarea, DASHED FILE DROPZONE (SVG icon, "Pilih Berkas" indigo button, JPG/PNG/PDF ≤2MB), full-width indigo submit 📤. Parent-without-student → amber warning replaces form.
   RIGHT col-7 HISTORY card "Riwayat Pengajuan Izin": list of request rows — leave-type badge (rose/amber/purple/blue ternary), date range mono, ⏰ time, duration, STATUS PILL right (amber pulse "Menunggu Verifikasi" / emerald "Disetujui" / red "Ditolak"), reason box white, footer: applicant + 📎 Lihat Surat Bukti link + verifier. Empty: 📭 state.
Deps: x-app-layout tree; Alpine inline (x-data leaveType, x-model, x-show).

## /kesiswaan/dashboard — `resources/views/kesiswaan/dashboard.blade.php` (301 lines) — NOT fully read
Pending/verified leave verification queue; reuses same badge ternaries; filter tabs + approve/reject PATCH forms.
Deps: x-app-layout tree, raw HTML (incl. x-w-xs chips).

## /guru/schedules/index — `guru/schedules/index.blade.php` — NOT fully read
Weekly block-schedule list + add/delete forms (locked-state aware).
Deps: x-app-layout tree.

## /guru/journals/index — `guru/journals/index.blade.php` (295 lines) — NOT fully read
Session/journal list w/ status chips + filter selects (x-w-md/x-w-xs), CTA per row.
Deps: x-app-layout tree.

## /guru/journals/create — `guru/journals/create.blade.php` — NOT fully read
Journal fill form: material textarea (x-h-64), attendance checkboxes, photo upload.
Deps: x-app-layout tree.

## /guru/journals/show — `guru/journals/show.blade.php` — NOT fully read
Journal detail + attendance recap.
Deps: x-app-layout tree.

## /waka/dashboard — `waka/dashboard.blade.php` (326 lines) — NOT fully read
Cycle toggle, lock toggle, master-data mini-forms (class/room/user), CSV import block.
Deps: x-app-layout tree.

## /reports/attendance — `reports/attendance_index.blade.php` — NOT fully read
Attendance recap table + filters + print link.
Deps: x-app-layout tree.

## /reports/attendance/print — `reports/attendance_print.blade.php` — standalone document (NO app layout), A4 print styling.

## /profile — `profile/edit.blade.php` — Breeze defaults.
Deps: x-app-layout tree + @include partials: `profile/partials/update-profile-information-form`, `update-password-form`, `delete-user-form` (latter uses x-modal, x-danger-button, x-secondary-button).

## Key-screen context candidates (payload sets for drafts)
- login: design-system.md (redesign) or init/theme.md (repro), layouts/guest.blade.php, auth/login.blade.php, components: auth-session-status, input-label, text-input, input-error, primary-button, application-logo, tailwind.config.js. All files < 900 lines → pass whole.
- guru/dashboard: layouts/app + navigation + bottom-navigation, guru/dashboard.blade.php, tailwind.config.js.
- portal/dashboard: layouts/app + navigation + bottom-navigation, portal/dashboard.blade.php, tailwind.config.js.
