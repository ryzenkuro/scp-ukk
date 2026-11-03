# School Class Progress (SCP) — Design System Specification
**Target Stack**: React 18+ (Full Frontend) + Laravel 11/13 (Backend 0% perubahan via Single Blade Host / Props Bridge) + Tailwind CSS v3 + Lucide React + motion.dev  
**Design Paradigm**: Filament-inspired Clean Operational Layout (Non-PHP, Non-Livewire, Pure React)

---

## 1. Three-Layer Token Architecture

### 1.1 Primitive Tokens (Raw Values)
Raw values that form the fundamental visual building blocks. Never reference primitives directly in components; map them through semantic tokens.

```typescript
export const primitives = {
  colors: {
    indigo: {
      50: '#EEF2FF',
      100: '#E0E7FF',
      200: '#C7D2FE',
      300: '#A5B4FC',
      400: '#818CF8',
      500: '#6366F1',
      600: '#4F46E5', // Brand Primary
      700: '#4338CA',
      800: '#3730A3',
      900: '#312E81',
      950: '#1E1B4B',
    },
    slate: {
      50: '#F8FAFC',  // Canvas Background
      100: '#F1F5F9', // Dividers & Subtle Tints
      200: '#E2E8F0', // Borders & Inactive Rings
      300: '#CBD5E1',
      400: '#94A3B8', // Tertiary / Placeholder Text
      500: '#64748B',
      600: '#475569', // Secondary Text
      700: '#334155',
      800: '#1E293B',
      900: '#0F172A', // Primary Headings & Dark Text
      950: '#020617',
    },
    emerald: {
      50: '#ECFDF5',
      100: '#D1FAE5',
      500: '#10B981',
      600: '#059669',
      800: '#065F46',
    },
    amber: {
      50: '#FFFBEB',
      100: '#FEF3C7',
      400: '#FBBF24',
      500: '#F59E0B',
      800: '#92400E',
    },
    rose: {
      50: '#FFF1F2',
      100: '#FFE4E6',
      500: '#F43F5E',
      600: '#E11D48',
      800: '#9F1239',
    },
    red: {
      50: '#FEF2F2',
      100: '#FEE2E2',
      500: '#EF4444',
      600: '#DC2626',
      800: '#991B1B',
    },
    blue: {
      50: '#EFF6FF',
      100: '#DBEAFE',
      500: '#3B82F6',
      600: '#2563EB',
      800: '#1E40AF',
    },
    purple: {
      50: '#FAF5FF',
      100: '#F3E8FF',
      500: '#A855F7',
      600: '#9333EA',
      800: '#6B21A8',
    },
    white: '#FFFFFF',
    black: '#000000',
  },
  typography: {
    fontFamilies: {
      sans: ['Figtree', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
      mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
    },
    sizes: {
      xs: ['12px', '16px'], // Badges, metadata, chips only (FLOOR for microtext)
      sm: ['14px', '20px'], // Body, table data, form inputs (STRICT FLOOR for body)
      base: ['15px', '22px'], // Card titles, section highlights
      md: ['16px', '24px'], // Section headers, prominent controls
      lg: ['18px', '26px'], // Modal headings, sub-page titles
      xl: ['20px', '28px'], // Page titles, top-level headings
      '2xl': ['24px', '32px'], // KPI Metrics, dashboard summary numbers
      '3xl': ['30px', '36px'], // Main display numbers
    },
    weights: {
      regular: 400,
      medium: 500,
      semibold: 600,
      bold: 700,
      extrabold: 800,
    },
  },
  spacing: {
    1: '4px',
    2: '8px',
    3: '12px',
    4: '16px',
    5: '20px',
    6: '24px',
    8: '32px',
    10: '40px',
    12: '48px',
    16: '64px',
  },
  radii: {
    none: '0px',
    sm: '4px',
    md: '6px',   // Segmented filter pills
    lg: '8px',   // Buttons, inputs, form fields
    xl: '12px',  // Cards, panel containers, table wrappers
    '2xl': '16px', // Slide-over drawers, modals
    full: '9999px', // Status badges, avatars, dot indicators
  },
  shadows: {
    sm: '0 1px 2px 0 rgba(15, 23, 42, 0.05)', // Rest state for cards and inputs
    md: '0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.04)', // Hover state for cards, dropdown menus
    xl: '0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04)', // Modals, slide-overs
  },
};
```

---

### 1.2 Semantic Tokens (Purpose-Driven Mappings)
Tokens that describe context, state, and functional meaning.

```typescript
export const semantics = {
  surface: {
    canvas: primitives.colors.slate[50],
    default: primitives.colors.white,
    subtle: primitives.colors.slate[100],
    elevated: primitives.colors.white,
    overlay: 'rgba(15, 23, 42, 0.5)', // Modal & Drawer backdrop
    glass: 'rgba(255, 255, 255, 0.85)', // Mobile bottom nav with backdrop-blur-md
  },
  border: {
    default: primitives.colors.slate[200],
    subtle: primitives.colors.slate[100],
    hover: primitives.colors.slate[300],
    focus: primitives.colors.indigo[500],
    invalid: primitives.colors.red[500],
  },
  text: {
    primary: primitives.colors.slate[900],
    secondary: primitives.colors.slate[600],
    tertiary: primitives.colors.slate[400],
    disabled: primitives.colors.slate[300],
    inverse: primitives.colors.white,
  },
  action: {
    primary: {
      bg: primitives.colors.indigo[600],
      bgHover: primitives.colors.indigo[700],
      bgActive: primitives.colors.indigo[800],
      text: primitives.colors.white,
      focusRing: primitives.colors.indigo[500],
    },
    secondary: {
      bg: primitives.colors.white,
      bgHover: primitives.colors.slate[50],
      bgActive: primitives.colors.slate[100],
      border: primitives.colors.slate[200],
      text: primitives.colors.slate[700],
    },
    destructive: {
      bg: primitives.colors.red[600],
      bgHover: primitives.colors.red[700],
      bgActive: primitives.colors.red[800],
      text: primitives.colors.white,
      focusRing: primitives.colors.red[500],
    },
    success: {
      bg: primitives.colors.emerald[600],
      bgHover: primitives.colors.emerald[700],
      text: primitives.colors.white,
      focusRing: primitives.colors.emerald[500],
    },
  },
  status: {
    pending: {
      bg: primitives.colors.amber[100],
      text: primitives.colors.amber[800],
      dot: primitives.colors.amber[500],
      label: 'Menunggu Verifikasi',
    },
    approved: {
      bg: primitives.colors.emerald[100],
      text: primitives.colors.emerald[800],
      dot: primitives.colors.emerald[500],
      label: 'Disetujui',
    },
    rejected: {
      bg: primitives.colors.red[100],
      text: primitives.colors.red[800],
      dot: primitives.colors.red[500],
      label: 'Ditolak',
    },
    overdue: {
      bg: primitives.colors.red[50],
      border: primitives.colors.red[600],
      text: primitives.colors.red[800],
      icon: 'triangle-alert',
    },
  },
  leaveTypes: {
    sakit: {
      bg: primitives.colors.rose[100],
      text: primitives.colors.rose[800],
      icon: 'HeartPulse',
      label: 'Sakit',
    },
    izin: {
      bg: primitives.colors.blue[100],
      text: primitives.colors.blue[800],
      icon: 'FileText',
      label: 'Izin',
    },
    pulang_cepat: {
      bg: primitives.colors.amber[100],
      text: primitives.colors.amber[800],
      icon: 'Timer',
      label: 'Pulang Cepat',
    },
    kegiatan_luar: {
      bg: primitives.colors.purple[100],
      text: primitives.colors.purple[800],
      icon: 'Trophy',
      label: 'Kegiatan Luar',
    },
  },
  cycle: {
    activePill: {
      bg: primitives.colors.indigo[50],
      text: primitives.colors.indigo[700],
      border: primitives.colors.indigo[200],
    },
  },
};
```

---

### 1.3 Component Tokens
Rules bound directly to atomic UI elements:

- **Button**:
  - Height: `h-10` (40px)
  - Padding: `px-4`
  - Font: `text-sm font-semibold`
  - Border radius: `rounded-lg` (8px)
  - Tap animation: `whileTap={{ scale: 0.97 }}`
- **Card**:
  - Border radius: `rounded-xl` (12px)
  - Padding: `p-5` or `p-6` (20-24px)
  - Border: `1px solid #E2E8F0`
  - Shadow: `shadow-sm` transitioning to `shadow-md` on hover.
- **Input / Select**:
  - Height: `h-10` (40px)
  - Text: `text-sm` (14px)
  - Border radius: `rounded-lg` (8px)
  - Focus Ring: `focus:ring-2 focus:ring-indigo-500 focus:border-transparent`

---

## 2. Interaction & Responsiveness Specifications

### 2.1 Desktop Layout (Filament-grade Architecture)
1. **Collapsible Sidebar**:
   - Expanded: `w-[260px]`
   - Collapsed (Icon-only): `w-[72px]`
   - Background: `#FFFFFF`, Right Border: `1px solid #E2E8F0`
   - Smooth toggle transition: `transition-all duration-200 ease-in-out`
   - Nav Item:
     - Height: `h-10` (40px), Radius: `rounded-lg` (8px)
     - Active: `bg-indigo-50 text-indigo-700 font-semibold`
     - Inactive: `text-slate-600 hover:bg-slate-50 hover:text-slate-900`
     - Badge/Count: Right-aligned small pill with numeric count.
2. **Top Bar Header**:
   - Height: `h-16` (64px), Sticky top with `z-20`
   - Left: Sidebar Toggle Trigger + Breadcrumbs (e.g. `Portal Guru / Jadwal`)
   - Center: Quick Search (`Ctrl+K` Command Bar launcher)
   - Right: Active Cycle Badge (`Siklus A / B`) + User Profile Dropdown

### 2.2 Mobile Layout (< 640px)
1. **Header**: Compact `h-14` header showing School Class Progress logo lockup and active user avatar.
2. **Bottom App Navigation Bar**:
   - Height: `h-16` (64px) + `env(safe-area-inset-bottom)`
   - Fixed at bottom with `z-40`, glassmorphism: `bg-white/90 backdrop-blur-md border-t border-slate-200/80`
   - 4-5 Primary Touch Targets (48x48px touch zone):
     - **Guru**: Beranda (Home), Sesi Hari Ini (Calendar), Isi Jurnal (NotebookPen - Featured Center Button), Rekap (BarChart).
     - **Siswa/Ortu**: Beranda (Home), Ajukan Izin (PlusCircle - Prominent Button), Riwayat Izin (ClipboardList), Profil (User).
   - Active Tab Indicator: Indigo text + icon with a subtle top indicator dot.

---

## 3. Form & Table Guidelines

### 3.1 Form Validation & Input States
- **Form Libraries**: React Hook Form with Zod schema resolution.
- **Labeling**:
  - Label: `text-sm font-medium text-slate-900`
  - Required Field: Trailing red asterisk `<span className="text-red-500 ml-0.5">*</span>`.
- **Validation State**:
  - Pristine / Valid: `border-slate-200 focus:ring-2 focus:ring-indigo-500`
  - Error: `border-red-500 bg-red-50/20 text-red-900 focus:ring-2 focus:ring-red-500`
  - Inline Error Message:
    ```tsx
    <motion.div 
      initial={{ opacity: 0, y: -4 }} 
      animate={{ opacity: 1, y: 0 }} 
      className="flex items-center gap-1.5 mt-1.5 text-xs font-medium text-red-600"
    >
      <AlertCircle className="w-3.5 h-3.5 shrink-0" />
      <span>{errorMessage}</span>
    </motion.div>
    ```
- **Conditional Alert Container (Contoh: Jam Pulang Cepat / Overdue Alert)**:
  - Background `#FFFBEB` (Amber-50), Left Border `4px solid #F59E0B`, padding `p-4`, radius `rounded-xl`.

### 3.2 DataTable Guidelines (Filament Standard in React)
1. **Toolbar**:
   - Realtime Search Input with 300ms debounce.
   - Filter Dropdowns (Filter Kelas, Filter Status, Filter Rentang Tanggal).
   - Segmented Tabs (Semua, Menunggu, Selesai) with `motion.dev` sliding pill indicator.
2. **Table Grid**:
   - Header: `bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200 sticky top-0`
   - Rows: Height `h-14`, hover state `hover:bg-slate-50/80 transition-colors`
   - Numeric / Timestamps: Rendered using `font-mono text-xs text-slate-600` for clear tabular alignment.
   - Actions Column: Pinned right with icon buttons (View, Edit, Verify, Print).
3. **Empty State**:
   - Icon: Tinted circle `w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3`
   - Title: `text-base font-semibold text-slate-800`
   - Description: `text-sm text-slate-500 max-w-sm mb-4`
   - Action: Primary Indigo CTA Button to create record or clear filters.
