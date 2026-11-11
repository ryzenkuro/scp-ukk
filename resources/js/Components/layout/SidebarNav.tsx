import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
  LayoutDashboard, 
  Users, 
  GraduationCap, 
  UserCheck, 
  Calendar, 
  FileText, 
  ShieldCheck, 
  LogOut, 
  ChevronDown, 
  ChevronRight,
  BookOpen,
  ClipboardList,
  Clock,
  X
} from 'lucide-react';
import { cn } from '@/lib/utils';

export type NavItemData = {
  id: string;
  title: string;
  href: string;
  icon: React.ElementType;
  badge?: number | string;
  active?: boolean;
  children?: { title: string; href: string; active?: boolean }[];
};

export type NavGroupData = {
  heading?: string;
  items: NavItemData[];
};

export interface SidebarNavProps {
  isOpen?: boolean;
  onClose?: () => void;
}

export function SidebarNav({ isOpen = false, onClose }: SidebarNavProps = {}) {
  const { auth, url } = usePage<any>().props;
  const user = auth?.user;
  const roleName = user?.role?.name || (user?.role_id === 1 ? 'waka' : user?.role_id === 2 ? 'guru' : user?.role_id === 3 ? 'siswa' : 'user');

  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({
    master: true,
  });

  const toggleGroup = (groupId: string) => {
    setOpenGroups(prev => ({ ...prev, [groupId]: !prev[groupId] }));
  };

  // Navigasi Khusus Waka Kurikulum (Admin Utama)
  const wakaNavGroups: NavGroupData[] = [
    {
      heading: 'Menu Utama',
      items: [
        {
          id: 'dashboard',
          title: 'Dashboard Waka',
          href: '/waka/dashboard',
          icon: LayoutDashboard,
          active: url === '/waka/dashboard' || url === '/dashboard',
        },
      ]
    },
    {
      heading: 'Data Master Sekolah',
      items: [
        {
          id: 'siswa',
          title: 'Data Siswa & Kelas',
          href: '/waka/siswa',
          icon: Users,
          active: url.startsWith('/waka/siswa'),
        },
        {
          id: 'alumni',
          title: 'Data Alumni (Arsip)',
          href: '/waka/alumni',
          icon: GraduationCap,
          active: url.startsWith('/waka/alumni'),
        },
        {
          id: 'guru',
          title: 'Data Tenaga Guru',
          href: '/waka/guru',
          icon: UserCheck,
          active: url.startsWith('/waka/guru'),
        },
        {
          id: 'orang_tua',
          title: 'Data Orang Tua',
          href: '/waka/orang-tua',
          icon: ShieldCheck,
          active: url.startsWith('/waka/orang-tua'),
        },
      ]
    },
    {
      heading: 'Akun & Penjadwalan',
      items: [
        {
          id: 'akun',
          title: 'Kelola Akun & Role',
          href: '/waka/akun',
          icon: ShieldCheck,
          active: url.startsWith('/waka/akun'),
        },
        {
          id: 'jadwal',
          title: 'Jadwal Sekolah',
          href: '/waka/jadwal',
          icon: Calendar,
          active: url.startsWith('/waka/jadwal'),
        },
        {
          id: 'jam-pelajaran',
          title: 'Jam Pembelajaran',
          href: '/waka/jam-pelajaran',
          icon: Clock,
          active: url.startsWith('/waka/jam-pelajaran'),
        },
      ]
    },
    {
      heading: 'Laporan & Rekapitulasi',
      items: [
        {
          id: 'laporan-presensi',
          title: 'Rekap Presensi Siswa',
          href: '/waka/laporan/presensi',
          icon: FileText,
          active: url.startsWith('/waka/laporan/presensi'),
        },
        {
          id: 'laporan-izin',
          title: 'Rekap Izin Siswa',
          href: '/waka/laporan/izin',
          icon: ClipboardList,
          active: url.startsWith('/waka/laporan/izin'),
        },
        {
          id: 'laporan-guru',
          title: 'Audit Kinerja Guru',
          href: '/waka/laporan/guru',
          icon: BookOpen,
          active: url.startsWith('/waka/laporan/guru'),
        },
      ]
    }
  ];

  // Navigasi Guru BK (Bimbingan & Konseling)
  const guruBkNavGroups: NavGroupData[] = [
    {
      heading: 'Portal Guru BK',
      items: [
        {
          id: 'dashboard-bk',
          title: 'Dashboard Guru BK',
          href: '/bk/dashboard',
          icon: LayoutDashboard,
          active: url === '/bk/dashboard' || url === '/kesiswaan/dashboard',
        },
        {
          id: 'presensi-bk',
          title: 'Rekap Presensi Rombel',
          href: '/bk/presensi',
          icon: FileText,
          active: url.startsWith('/bk/presensi') || url.startsWith('/kesiswaan/presensi'),
        },
        {
          id: 'izin-bk',
          title: 'Rekap Izin Rombel',
          href: '/bk/izin',
          icon: ClipboardList,
          active: url.startsWith('/bk/izin') || url.startsWith('/kesiswaan/izin'),
        },
      ]
    },
    {
      heading: 'Operasional Mengajar',
      items: [
        {
          id: 'dashboard-guru-bk',
          title: 'Dashboard Guru',
          href: '/guru/dashboard',
          icon: BookOpen,
          active: url === '/guru/dashboard',
        },
        {
          id: 'jadwal-mengajar-bk',
          title: 'Jadwal Mengajar',
          href: '/guru/jadwal',
          icon: Calendar,
          active: url === '/guru/jadwal',
        },
        {
          id: 'jurnal-kbm-bk',
          title: 'Jurnal & Presensi KBM',
          href: '/guru/journal',
          icon: BookOpen,
          active: url.startsWith('/guru/journal'),
        },
      ]
    }
  ];

  // Navigasi Wali Kelas
  const waliKelasNavGroups: NavGroupData[] = [
    {
      heading: 'Portal Wali Kelas',
      items: [
        {
          id: 'dashboard-wali-kelas',
          title: 'Dashboard Wali Kelas',
          href: '/wali-kelas/dashboard',
          icon: LayoutDashboard,
          active: url === '/wali-kelas/dashboard',
        },
        {
          id: 'siswa-wali-kelas',
          title: 'Data Siswa Binaan',
          href: '/wali-kelas/siswa',
          icon: Users,
          active: url.startsWith('/wali-kelas/siswa'),
        },
        {
          id: 'jadwal-wali-kelas',
          title: 'Jadwal Kelas Binaan',
          href: '/wali-kelas/jadwal',
          icon: Calendar,
          active: url.startsWith('/wali-kelas/jadwal'),
        },
        {
          id: 'izin-wali-kelas',
          title: 'Verifikasi Izin Rombel',
          href: '/wali-kelas/izin',
          icon: ClipboardList,
          active: url.startsWith('/wali-kelas/izin'),
        },
        {
          id: 'presensi-wali-kelas',
          title: 'Rekap Presensi Rombel',
          href: '/wali-kelas/presensi',
          icon: FileText,
          active: url.startsWith('/wali-kelas/presensi'),
        },
      ]
    },
    {
      heading: 'Operasional Mengajar',
      items: [
        {
          id: 'dashboard-guru-wali',
          title: 'Dashboard Guru',
          href: '/guru/dashboard',
          icon: BookOpen,
          active: url === '/guru/dashboard',
        },
        {
          id: 'jurnal-kbm-wali',
          title: 'Jurnal & Presensi KBM',
          href: '/guru/journal',
          icon: BookOpen,
          active: url.startsWith('/guru/journal'),
        },
      ]
    }
  ];

  // Navigasi Guru
  const guruNavGroups: NavGroupData[] = [
    {
      heading: 'Portal Guru',
      items: [
        {
          id: 'dashboard-guru',
          title: 'Dashboard Guru',
          href: '/guru/dashboard',
          icon: LayoutDashboard,
          active: url === '/guru/dashboard',
        },
        {
          id: 'jadwal-mengajar-guru',
          title: 'Jadwal Mengajar',
          href: '/guru/jadwal',
          icon: Calendar,
          active: url === '/guru/jadwal',
        },
        {
          id: 'jurnal-kbm',
          title: 'Jurnal & Presensi KBM',
          href: '/guru/journal',
          icon: BookOpen,
          active: url.startsWith('/guru/journal'),
        },
      ]
    }
  ];

  // Navigasi Siswa
  const siswaNavGroups: NavGroupData[] = [
    {
      heading: 'Portal Siswa',
      items: [
        {
          id: 'dashboard-siswa',
          title: 'Dashboard Siswa',
          href: '/siswa/dashboard',
          icon: LayoutDashboard,
          active: url === '/siswa/dashboard',
        },
        {
          id: 'jadwal-siswa',
          title: 'Jadwal Pelajaran',
          href: '/siswa/jadwal',
          icon: Calendar,
          active: url.startsWith('/siswa/jadwal'),
        },
        {
          id: 'izin-siswa',
          title: 'Pengajuan Izin',
          href: '/siswa/izin',
          icon: ClipboardList,
          active: url.startsWith('/siswa/izin'),
        },
      ]
    }
  ];

  // Navigasi Orang Tua Murid
  const orangTuaNavGroups: NavGroupData[] = [
    {
      heading: 'Portal Orang Tua Murid',
      items: [
        {
          id: 'dashboard-ortu',
          title: 'Dashboard Orang Tua',
          href: '/siswa/dashboard',
          icon: LayoutDashboard,
          active: url === '/siswa/dashboard',
        },
        {
          id: 'jadwal-anak',
          title: 'Jadwal Pelajaran Anak',
          href: '/siswa/jadwal',
          icon: Calendar,
          active: url.startsWith('/siswa/jadwal'),
        },
        {
          id: 'izin-anak',
          title: 'Pengajuan Izin Anak',
          href: '/siswa/izin',
          icon: ClipboardList,
          active: url.startsWith('/siswa/izin'),
        },
      ]
    }
  ];

  const isWali = roleName === 'wali_kelas' || user?.role_id === 5 || (user?.homeroom_assignments && user.homeroom_assignments.length > 0) || (user?.wali_classes && user.wali_classes.length > 0);
  let currentGroups = wakaNavGroups;
  if (roleName === 'guru_bk' || roleName === 'bk' || roleName === 'kesiswaan') {
    currentGroups = guruBkNavGroups;
  } else if (isWali) {
    currentGroups = waliKelasNavGroups;
  } else if (roleName === 'guru') {
    currentGroups = guruNavGroups;
  } else if (roleName === 'orang_tua' || user?.role_id === 6) {
    currentGroups = orangTuaNavGroups;
  } else if (roleName === 'siswa' || user?.role_id === 3) {
    currentGroups = siswaNavGroups;
  }

  return (
    <aside
      className={cn(
        "fixed inset-y-0 left-0 z-50 flex h-full w-64 flex-col border-r border-border bg-card text-card-foreground transition-transform duration-200 ease-in-out md:static md:translate-x-0 md:z-auto",
        isOpen ? "translate-x-0 shadow-2xl" : "-translate-x-full"
      )}
    >
      {/* Brand Header */}
      <div className="flex h-16 items-center justify-between border-b border-border px-6">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary font-bold text-primary-foreground shadow-sm">
            SP
          </div>
          <div>
            <div className="text-sm font-bold tracking-tight">SMK N 1 BANTUL</div>
            <div className="text-[11px] font-medium text-muted-foreground">Presensi & Agenda UKK</div>
          </div>
        </div>
        {onClose && (
          <button
            type="button"
            onClick={onClose}
            className="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground md:hidden"
          >
            <X className="h-5 w-5" />
          </button>
        )}
      </div>

      {/* Nav List */}
      <div className="flex-1 space-y-6 overflow-y-auto px-4 py-4">
        {currentGroups.map((group, gIdx) => (
          <div key={gIdx} className="space-y-1">
            {group.heading && (
              <p className="px-3 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                {group.heading}
              </p>
            )}
            <div className="mt-1 space-y-1">
              {group.items.map((item) => {
                const Icon = item.icon;
                const hasChildren = item.children && item.children.length > 0;
                const isOpen = openGroups[item.id] ?? false;

                if (hasChildren) {
                  return (
                    <div key={item.id} className="space-y-1">
                      <button
                        onClick={() => toggleGroup(item.id)}
                        className={cn(
                          "flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground text-muted-foreground",
                          item.active && "bg-accent text-accent-foreground font-semibold"
                        )}
                      >
                        <span className="flex items-center gap-3">
                          <Icon className="h-4 w-4 shrink-0" />
                          <span>{item.title}</span>
                        </span>
                        {isOpen ? (
                          <ChevronDown className="h-4 w-4" />
                        ) : (
                          <ChevronRight className="h-4 w-4" />
                        )}
                      </button>
                      {isOpen && (
                        <div className="ml-7 space-y-1 border-l border-border pl-2">
                          {item.children?.map((child, cIdx) => (
                            <Link
                              key={cIdx}
                              href={child.href}
                              onClick={() => onClose?.()}
                              className={cn(
                                "block rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-accent hover:text-accent-foreground text-muted-foreground",
                                child.active && "bg-accent text-accent-foreground font-semibold"
                              )}
                            >
                              {child.title}
                            </Link>
                          ))}
                        </div>
                      )}
                    </div>
                  );
                }

                return (
                  <Link
                    key={item.id}
                    href={item.href}
                    onClick={() => onClose?.()}
                    className={cn(
                      "flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground text-muted-foreground",
                      item.active && "bg-accent text-accent-foreground font-semibold"
                    )}
                  >
                    <span className="flex items-center gap-3">
                      <Icon className="h-4 w-4 shrink-0" />
                      <span>{item.title}</span>
                    </span>
                    {item.badge && (
                      <span className="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-bold text-secondary-foreground">
                        {item.badge}
                      </span>
                    )}
                  </Link>
                );
              })}
            </div>
          </div>
        ))}
      </div>

      {/* User Card Bottom */}
      <div className="border-t border-border p-4">
        <div className="flex items-center justify-between">
          <Link href="/profile" onClick={() => onClose?.()} className="flex items-center gap-3 overflow-hidden">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted font-semibold text-muted-foreground">
              {user?.fullname?.[0] || user?.username?.[0] || 'U'}
            </div>
            <div className="truncate">
              <p className="truncate text-xs font-semibold">{user?.fullname || user?.username}</p>
              <p className="truncate text-[10px] text-muted-foreground">{user?.role?.label || roleName}</p>
            </div>
          </Link>
          <Link
            href="/logout"
            method="post"
            as="button"
            className="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-destructive transition"
            title="Keluar"
          >
            <LogOut className="h-4 w-4" />
          </Link>
        </div>
      </div>
    </aside>
  );
}
