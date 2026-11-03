# Theme — current state (pre-redesign)

## Part 1 — Compact token summary (CURRENT UI, ground truth for reproduction)

- **Fonts:** Figtree 400/500/600 via bunny.net CDN, set as `fontFamily.sans` extend. No other families (browser mono used via `font-mono`).
- **CSS variables:** NONE. `resources/css/app.css` = only the 3 `@tailwind` directives.
- **Tailwind config:** pure defaults + Figtree only. No custom colors/radii/shadows. `@tailwindcss/forms` plugin active. Tailwind **v3** (^3.1.0).
- **De-facto palette (ad-hoc classes, no tokens):**
  - Canvas: `bg-gray-100` everywhere. Surfaces: white cards.
  - Accent: indigo — buttons `bg-indigo-600 hover:bg-indigo-700`; nav active border `indigo-400`; chips `indigo-50/100 + text indigo-700/800`; focus rings indigo-500; check-accent indigo-600.
  - Text: `text-gray-800/900` headings, `text-gray-600` body, `text-gray-500` secondary, `text-gray-400` tertiary.
  - Status (badge pairs): Pending `bg-amber-100 text-amber-800` (+ dot `amber-500`, sometimes `animate-pulse`); Approved/verified `bg-emerald-100 text-emerald-800` (dot emerald-500; action btn emerald-600); Rejected `bg-red-100 text-red-800` (dot red-500); overdue/alerts red-50 card + `border-l-4 border-red-600`.
  - Leave types: Sakit `rose-100/800`, Pulang Cepat `amber-100/800`, Kegiatan Luar `purple-100/800`, Izin `blue-100/800` — each prefixed with an EMOJI.
- **Type scale:** body dominated by `text-xs` (12px) — too small; `text-[11px]`, `text-[10px]` chips; headings `text-xl font-bold` page, `text-base`/`text-lg` card titles. Buttons `text-xs font-semibold|bold` (feature pages) / `text-xs uppercase tracking-widest` (Breeze buttons).
- **Radius (inconsistent):** Breeze controls `rounded-md`; feature cards `rounded-2xl`; inner cards/buttons `rounded-xl`; small chips `rounded`/`rounded-md`; pills `rounded-full`.
- **Shadow:** `shadow-sm` cards, `shadow-md` header, `shadow-xl` mobile bottom bar / modal, `shadow-lg` dropdown. **`shadow-xs` and `shadow-2xs` are used in feature views but do not exist in Tailwind v3 → those elements render with NO shadow.**
- **Spacing:** content `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`, vertical rhythm `space-y-6`; card padding `p-6`; grids `gap-4/gap-6`.
- **Icons: EMOJI** (🏠 📅 📝 👤 ⚠️ ✅ ⏳ 🕒 📊 📋 📭 🏖️ 📤 📎 🔒 🔓 🩹 ⏱️ 🏆 etc.) across nav, badges, buttons, empty states. Only real SVGs: Laravel logo, hamburger, chevron, upload-cloud (portal dropzone), modal is Alpine.
- **Dark mode:** none. **Print:** `/reports/attendance/print` standalone A4 page.

## Part 2 — Raw sources

### tailwind.config.js (full)
```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
```

### resources/css/app.css (full)
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### package.json (deps)
devDependencies: `@tailwindcss/forms ^0.5.2`, `@tailwindcss/vite ^4.0.0` (present but config is v3-style; vite plugin usage in vite.config.js — verify before touching), `alpinejs ^3.4.2`, `autoprefixer`, `concurrently`, `laravel-vite-plugin`, `postcss`, `tailwindcss ^3.1.0`, `vite ^8`. optionalDependencies: `@laravel/multiplex`. NO react yet.
