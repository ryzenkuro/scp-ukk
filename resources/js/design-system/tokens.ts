/**
 * School Class Progress (SCP) — Design Tokens System
 * 3-Layer Token Architecture: Primitive -> Semantic -> Component
 */

// ============================================================================
// LAYER 1: PRIMITIVE TOKENS
// ============================================================================
export const primitives = {
  colors: {
    indigo: {
      50: '#EEF2FF',
      100: '#E0E7FF',
      200: '#C7D2FE',
      300: '#A5B4FC',
      400: '#818CF8',
      500: '#6366F1',
      600: '#4F46E5', // Primary Brand / Active Action
      700: '#4338CA',
      800: '#3730A3',
      900: '#312E81',
      950: '#1E1B4B',
    },
    slate: {
      50: '#F8FAFC',  // Canvas Background
      100: '#F1F5F9', // Subtle surface / dividers
      200: '#E2E8F0', // Card & component borders
      300: '#CBD5E1',
      400: '#94A3B8', // Placeholder & tertiary text
      500: '#64748B',
      600: '#475569', // Secondary body text
      700: '#334155',
      800: '#1E293B',
      900: '#0F172A', // Primary headings & strong text
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
      sans: 'Figtree, ui-sans-serif, system-ui, -apple-system, sans-serif',
      mono: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
    },
    sizes: {
      xs: { fontSize: '12px', lineHeight: '16px' },   // Floor for meta/badges
      sm: { fontSize: '14px', lineHeight: '20px' },   // Floor for body/inputs
      base: { fontSize: '15px', lineHeight: '22px' }, // Card headings
      md: { fontSize: '16px', lineHeight: '24px' },   // Section headers
      lg: { fontSize: '18px', lineHeight: '26px' },   // Sub-page titles
      xl: { fontSize: '20px', lineHeight: '28px' },   // Page titles
      '2xl': { fontSize: '24px', lineHeight: '32px' }, // Metric numbers
      '3xl': { fontSize: '30px', lineHeight: '36px' }, // Large hero stat
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
    md: '6px',
    lg: '8px',     // Buttons & Inputs
    xl: '12px',    // Cards & Panels
    '2xl': '16px', // Modals & Drawers
    full: '9999px', // Pills & Status Badges
  },
  shadows: {
    sm: '0 1px 2px 0 rgba(15, 23, 42, 0.05)',
    md: '0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.04)',
    xl: '0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04)',
  },
} as const;

// ============================================================================
// LAYER 2: SEMANTIC TOKENS
// ============================================================================
export const semantics = {
  surface: {
    canvas: primitives.colors.slate[50],
    default: primitives.colors.white,
    subtle: primitives.colors.slate[100],
    elevated: primitives.colors.white,
    overlay: 'rgba(15, 23, 42, 0.5)',
    glass: 'rgba(255, 255, 255, 0.85)',
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
} as const;

// ============================================================================
// LAYER 3: COMPONENT SPECIFIC TOKENS
// ============================================================================
export const components = {
  sidebar: {
    widthExpanded: '260px',
    widthCollapsed: '72px',
    bg: primitives.colors.white,
    border: primitives.colors.slate[200],
    itemHeight: '40px',
    itemRadius: primitives.radii.lg,
  },
  topbar: {
    height: '64px',
    bg: primitives.colors.white,
    border: primitives.colors.slate[200],
  },
  bottomNav: {
    height: '64px',
    bg: 'rgba(255, 255, 255, 0.90)',
    border: primitives.colors.slate[200],
  },
  card: {
    radius: primitives.radii.xl, // 12px
    border: primitives.colors.slate[200],
    shadowRest: primitives.shadows.sm,
    shadowHover: primitives.shadows.md,
  },
  button: {
    height: '40px',
    radius: primitives.radii.lg, // 8px
    font: 'text-sm font-semibold',
  },
  input: {
    height: '40px',
    radius: primitives.radii.lg, // 8px
    font: 'text-sm',
    border: primitives.colors.slate[200],
    focusRing: primitives.colors.indigo[500],
  },
  table: {
    headerBg: primitives.colors.slate[50],
    headerText: primitives.colors.slate[600],
    rowHoverBg: primitives.colors.slate[50],
    border: primitives.colors.slate[200],
    rowHeight: '56px',
  },
} as const;

export type PrimitiveTokens = typeof primitives;
export type SemanticTokens = typeof semantics;
export type ComponentTokens = typeof components;
