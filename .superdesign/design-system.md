# Design System — School Class Progress (SCP) · NEW (approved direction)

Target stack for implementation: Blade shell + **React islands** (same routes/controllers/POST endpoints), Tailwind v3 with real tokens, **lucide** icons, **Motion for React** micro-motion. Filament `/admin` is REMOVED; admin CRUD is rebuilt as React pages in this same system. This document is the single source of visual truth for all drafts.

## 1. Product context
- Indonesian school "Teaching Factory" ops app. Key users/roles: **guru** (weekly block schedules, daily teaching journal + student attendance, often on phone right after class), **siswa/orang_tua** (submit sick/leave letters with photo/PDF proof, track approval), **wali_kelas & guru_bk** (kesiswaan: verify/approve/reject leave requests), **waka_kurikulum** (master data: users/teachers/students/rooms/classes, cycle + schedule-lock config, CSV import), **super_admin** (same admin, e.g. "Muzan").
- Key pages: login (username+password, closed registration), guru dashboard (overdue-journal alerts, today's sessions, quick cards), jadwal, jurnal (list/create/show w/ attendance), kesiswaan verification queue, portal izin (form + history), waka dashboard, reports rekap + A4 print, profile, + admin CRUD (users/teachers/students/rooms/classes/schedules/journals/config).
- JTBD speed paths: guru fills journal in <2 min; kesiswaan triages pending list at a glance; parent submits proof in one hand on mobile.
- Platform: responsive web, **mobile-first for guru/portal/kesiswaan** (bottom nav < sm), desktop content column max-w-7xl with top nav.

## 2. Color tokens (HEX — exact)
Primary = **Indigo** (harmonizes with ex-Filament admin; single brand family):
- indigo-50 #EEF2FF · indigo-100 #E0E7FF · indigo-400 #818CF8 · indigo-500 #6366F1 · **indigo-600 #4F46E5 (primary action / active)** · indigo-700 #4338CA (hover/pressed)
Neutrals (slate):
- App canvas #F8FAFC (slate-50) · surface #FFFFFF · border #E2E8F0 (slate-200) · divider #F1F5F9 (slate-100)
- Text: primary #0F172A · secondary #475569 · tertiary #94A3B8
Semantic pairs (badge bg / text / dot):
- Pending "Menunggu Verifikasi": #FEF3C7 / #92400E / #F59E0B (amber) — dot pulses
- Approved "Disetujui": #D1FAE5 / #065F46 / #10B981 (emerald); success actions #059669
- Rejected "Ditolak" + destructive: #FEE2E2 / #991B1B / #EF4444 (red); destructive buttons #DC2626
- Alerts (overdue journal): card bg #FEF2F2, left border #DC2626
Leave-type (keep current color language, lucide icons replace emoji):
- Sakit #FFE4E6/#9F1239 (rose) · Izin #DBEAFE/#1E40AF (blue) · Pulang Cepat #FEF3C7/#92400E (amber) · Kegiatan Luar #F3E8FF/#6B21A8 (purple)
Focus ring: #6366F1 (2px, offset 2). Links: indigo-600. NO other hues anywhere.

## 3. Typography
- Family: **Figtree only** (400/500/600/700/800), same CDN pattern. No serif/display/decorative faces. `font-mono` for times/dates/codes only (tabular).
- Scale (floor **14px body**): page title 20/28 bold · section header 16/24 semibold · card title 15/20 semibold · body 14/20 · caption/meta 12/16 medium (never below 12; old 10-11px bumped to 12). Buttons 14 semibold, normal case (drop uppercase tracking).
- Weight hierarchy: bold titles, medium labels, regular body.

## 4. Radius · Shadow · Border · Spacing
- Radius: cards **12px** (rounded-xl) · inputs/buttons 8px (rounded-lg) · chips 6px · badges/avatar pills full.
- Shadows: cards `0 1px 2px rgba(15,23,42,.06)` (sm) · popovers md · modal overlay panel xl. Only these three; never invented shadow names (old shadow-xs/2xs bug is banned).
- Border 1px #E2E8F0 on cards/inputs.
- Spacing 4px system: card padding 20-24 · grid gap 16 (cards) / 24 (sections) · section rhythm 24 · page column max-w-7xl px-4/6/8. Mobile safe-area: content bottom padding for fixed bottom nav.

## 5. Iconography
- **lucide** only, stroke 2, sizes 16 (inline) / 20 (default) / 24 (feature). Replaces every emoji. Nav: home / calendar-days / notebook-pen / clipboard-check / file-plus-2 / user / settings-2 / bar-chart-3. Types: heart-pulse (Sakit), file-text (Izin), timer (Pulang Cepat), trophy (Kegiatan Luar). Status: clock (pending), circle-check (approved), circle-x (rejected), triangle-alert (overdue). Empty states: inbox / calendar-off / file-x w/ tertiary color, 48px in tinted circle.
- Logo: no official school logo asset exists yet (current = Laravel default SVG — not brand). Drafts use a wordmark lockup: indigo rounded-lg square 40px containing graduation-cap glyph + "School Class Progress" 16px bold + "Teaching Factory" 12px tertiary. To be replaced if user supplies a real logo (then it MUST render in every logo slot).

## 6. Core components
- **AppShell**: white top nav 64px (logo left, role links w/ 2px indigo active underline, user avatar-initials + dropdown right). Mobile: NO hamburger — fixed bottom tab bar (white blur, top border, 3-4 role tabs, active = indigo label+icon, inactive slate-400). Page header = title + subtitle + optional role pill on plain canvas (drop the white header band).
- **StatusBadge / LeaveTypeBadge** (single component each — see §2 pairs; 12px semibold pill, dot or 14px lucide icon inside).
- **SessionCard**: state ring/border (pending slate / filled emerald-tint / overdue red-tint), status chip top-left, time mono top-right, subject 15 semibold, "class · room" 14 secondary, full-width CTA footer (filled → emerald outline "Lihat Rekap Presensi", else indigo/red "Isi Jurnal & Presensi").
- **Alert card** (overdue): #FEF2F2, left 4px #DC2626, triangle-alert icon, count chip, inline CTA.
- **Forms**: label 14 medium #0F172A + required asterisk red; input h-10 14px rounded-lg border slate-200, focus indigo ring; error text 13 red-600 w/ circle-alert icon; select same; conditional block (Jam Pulang Cepat) = amber-50 info panel w/ info icon; **FileDropzone** dashed slate-200 r-12, hover/drag indigo-400 + indigo-50/40 bg, filename appears with check icon after selection.
- **Flash → Toast**: top-center slide-down pill (success emerald / error red) w/ icon + message, auto-dismiss 5s, AnimatePresence.
- **Filter tabs** (kesiswaan): segmented control, active pill slides via layoutId, count badges.
- **DataTable**: header 12 uppercase secondary on slate-50, rows 14px, hover slate-50, mono dates, right-aligned actions; < md collapses rows to stacked cards.
- **EmptyState**, **SectionCard** (icon in tinted 36px rounded-lg square + title + subtitle + trailing chip), **Modal** (overlay rgba(15,23,42,.5), panel r-12 shadow-xl, scale/fade), **Admin CRUD**: sidebar list + data table + slide-over create/edit form (replaces Filament).
- Buttons: Primary indigo-600 (hover 700, tap scale .97); Secondary white + slate-200 border; Success emerald-600; Destructive red-600; all h-10 px-4 rounded-lg 14 semibold, lucide 16 inside when meaningful.

## 7. Motion — Motion for React, exactly 5 patterns, none decorative
1. Page entrance: fade + translateY 8px, 200ms ease-out; card grids stagger children 40ms.
2. AnimatePresence: toast slide-down/exit; modal scale-fade.
3. Filter tabs: layoutId sliding active pill.
4. Micro: buttons whileTap scale 0.97; cards hover:-translate-y-0.5 shadow sm→md (pointer devices only).
5. StatusBadge change: crossfade + 1.05 pop after approve/reject.
All wrapped by `MotionConfig reducedMotion="user"`. No scroll-jacking, no parallax, no page transitions. Print view: zero motion, pure A4 CSS.

## 8. Constraints / bans
- ONLY the tokens above; never introduce fonts/colors/shadows outside this doc.
- No emoji icons. No uppercase button text. No body text < 14px. No mixed card radii. No Tailwind v4 migration in this pass.
- Preserve all real content/fields/flow states (overdue alert, parent-unlinked warning, conditional leave_time block, locked-schedule states) — redesign never drops a state.
- Language of UI copy stays Indonesian, matching existing labels.
