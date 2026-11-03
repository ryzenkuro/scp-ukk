# Extractable Components — SCP

Patterns to extract as reusable DraftComponents. NOTE: the current source forms of StatusBadge/LeaveTypeBadge/FlashAlert/etc. are raw duplicated HTML inside views (portal/kesiswaan/waka/reports). For a full redesign, extract AFTER the new direction is approved (from the approved draft), so drafts reuse the NEW shell instead of the old emoji shell.

## AppTopNav
- Source: `resources/views/layouts/navigation.blade.php`
- Category: layout
- Description: Desktop top nav, h-16, white, role-conditional links, user dropdown (Profile / Log Out), mobile hamburger.
- Extractable props: `activeItem` (dashboard|jadwal|jurnal|verifikasi|izin|admin|reports), `role` (guru|wali_kelas|guru_bk|siswa|orang_tua|waka_kurikulum|super_admin), `userName`, `username`.
- Hardcoded: link labels (ID), logo position, indigo-400 active underline.

## MobileBottomNav
- Source: `resources/views/layouts/bottom-navigation.blade.php`
- Category: layout
- Description: Fixed mobile bottom bar (sm:hidden), role-based grid: guru 4 cols (Beranda/Jadwal/Jurnal/Profil), kesiswaan 3 (Beranda/Verifikasi/Profil), siswa-ortu 3 (Beranda/Ajukan Izin/Profil), waka 3 (Admin/Dashboard/Profil). Active = indigo-600 bold.
- Extractable props: `role`, `activeItem`.
- Hardcoded: labels, icons (currently emoji → must become lucide), bar styles.

## StatusBadge
- Source: inline ternary duplicated in `portal/dashboard.blade.php`, `kesiswaan/dashboard.blade.php`, waka/reports views
- Category: basic
- Description: Approval pill: Pending → amber pill + pulsing dot "Menunggu Verifikasi"; Approved → emerald "Disetujui"; Rejected → red "Ditolak".
- Extractable props: `status` (Pending|Approved|Rejected).
- Hardcoded: label strings (ID), dot animation, color pairs.

## LeaveTypeBadge
- Source: same inline duplication
- Category: basic
- Description: 4-type badge: Sakit rose, Izin blue, Pulang Cepat amber, Kegiatan Luar purple (emoji prefixes to be replaced by lucide icons: heart-pulse / file-text / timer / trophy).
- Extractable props: `leaveType` (Sakit|Izin|Pulang Cepat|Kegiatan Luar).

## FlashAlert
- Source: inline in portal/kesiswaan/waka views (`session('success')`, `$errors->any()`)
- Category: basic
- Description: Success (emerald, border-l-4) and error-list (red) alert cards; target for slide-down toast + AnimatePresence.
- Extractable props: `variant` (success|error), `title`, `messages[]`.

## SectionCard
- Source: repeated raw pattern `bg-white p-6 rounded-2xl shadow-sm border border-gray-100` + icon/title/subtitle header + trailing chip
- Category: basic
- Description: Standard white card container with header row.
- Extractable props: `icon`, `title`, `subtitle`, trailing slot, body slot.

## ProfileHeaderCard
- Source: `portal/dashboard.blade.php` (profile card), `guru/dashboard.blade.php` (welcome card)
- Category: basic
- Description: Identity strip: initials avatar, name, meta line (mono username, class), right-side stat ("Total Pengajuan N Surat") or action buttons.
- Extractable props: `name`, `meta[]`, `initials`, `accent` (emerald|amber|indigo), right slot.

## SessionCard
- Source: `guru/dashboard.blade.php` today-sessions grid
- Category: basic
- Description: Teaching-session card: status chip, time range mono, subject, class • room, CTA button (state-colored).
- Extractable props: `state` (filled|pending|overdue), `startTime`, `endTime`, `subject`, `className`, `roomName`, `ctaLabel`, `ctaHref`.

## FileDropzone
- Source: `portal/dashboard.blade.php` upload block
- Category: basic
- Description: Dashed-border dropzone: icon, "Pilih Berkas" trigger, format hint (JPG/PNG/PDF ≤ 2MB).
- Extractable props: `filename` (selected state), `accept`, `hint`.

## EmptyState
- Source: repeated (`🏖️` no-sessions, `📭` no-requests, etc.)
- Category: basic
- Description: Centered icon + title + hint block.
- Extractable props: `icon`, `title`, `hint`.

## ApprovalTab / StatusFilterTabs
- Source: `kesiswaan/dashboard.blade.php` filter area
- Category: basic
- Description: Status filter tabs (Pending/Approved/Rejected/all) — target of Motion `layoutId` sliding indicator.
- Extractable props: `activeTab`, `counts{}`.

## DataTable
- Source: `guru/journals/index.blade.php`, `reports/attendance_index.blade.php`, `kesiswaan/dashboard.blade.php`
- Category: basic
- Description: List/table of rows with chips, mono dates, action links; responsive → stacked cards on mobile.
- Extractable props: columns, rows, emptyState.
