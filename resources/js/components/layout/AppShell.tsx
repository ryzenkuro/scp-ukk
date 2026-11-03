import React, { useState, useEffect } from 'react';
import { MotionConfig } from 'motion/react';
import {
  GraduationCap,
  LayoutDashboard,
  CalendarDays,
  NotebookPen,
  ClipboardCheck,
  FilePlus2,
  BarChart3,
  Users,
  Building2,
  LogOut,
  ChevronLeft,
  ChevronRight,
  Menu,
  Lock,
  User as UserIcon,
  BookOpen,
  DoorOpen,
  X,
} from 'lucide-react';
import { User, SystemConfig } from '../../types';

export interface NavItem {
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  href: string;
  id: string;
  tab?: string;
  badge?: string | number;
}

export interface NavGroup {
  groupTitle?: string;
  items: NavItem[];
}

interface AppShellProps {
  user?: User | null;
  config?: SystemConfig | null;
  currentPage: string;
  csrfToken?: string;
  pageProps?: any;
  children: React.ReactNode;
}

export const AppShell: React.FC<AppShellProps> = ({
  user,
  config,
  currentPage,
  csrfToken,
  pageProps,
  children,
}) => {
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const roleName = user?.role?.role_name || 'guru';

  const getRoleDisplayName = (role: string, userObj?: any) => {
    const className = userObj?.schoolClass?.class_name || userObj?.school_class?.class_name;
    switch (role) {
      case 'waka_kurikulum':
        return 'Waka Kurikulum';
      case 'guru':
        return 'Guru (Pengampu Mapel)';
      case 'wali_kelas':
        return className ? `Guru (Wali ${className})` : 'Guru (Wali Kelas)';
      case 'guru_bk':
        return 'Guru BK (Bimbingan Konseling)';
      case 'siswa':
        return className ? `Siswa (${className})` : 'Siswa';
      case 'orang_tua':
        return 'Orang Tua / Wali';
      default:
        return role.replace(/_/g, ' ');
    }
  };

  // Active tab state for Waka/Admin internal switching
  const [activeWakaTab, setActiveWakaTab] = useState<string>(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      return params.get('tab') || 'overview';
    }
    return 'overview';
  });

  useEffect(() => {
    const handleTabChange = (e: any) => {
      if (e.detail) {
        setActiveWakaTab(e.detail);
      }
    };
    const handlePopState = () => {
      const params = new URLSearchParams(window.location.search);
      setActiveWakaTab(params.get('tab') || 'overview');
    };
    window.addEventListener('scp:change-tab', handleTabChange);
    window.addEventListener('popstate', handlePopState);
    return () => {
      window.removeEventListener('scp:change-tab', handleTabChange);
      window.removeEventListener('popstate', handlePopState);
    };
  }, []);

  // Compute live contextual badges across roles
  const wakaSchedulesCount = pageProps?.schedules?.length;
  const wakaJournalsCount = pageProps?.journals?.length;
  const wakaUsersCount = pageProps?.users?.length;
  const wakaClassesCount = pageProps?.classes?.length;

  const guruSchedulesCount = pageProps?.mySchedules?.length ?? pageProps?.mySchedulesCount;
  const guruJournalsCount = Array.isArray(pageProps?.myJournals)
    ? pageProps.myJournals.length
    : pageProps?.myJournals?.total;

  const kesiswaanPendingCount = pageProps?.counts?.pending;
  const portalRequestsCount = Array.isArray(pageProps?.leaveRequests)
    ? pageProps.leaveRequests.length
    : pageProps?.leaveRequests?.data?.length;

  // Grouped Navigation Items across all 7 Roles
  const getNavGroups = (): NavGroup[] => {
    switch (roleName) {
      case 'waka_kurikulum':
        return [
          {
            groupTitle: 'Dashboard & Sistem',
            items: [
              {
                label: 'Ringkasan & Konfigurasi',
                icon: LayoutDashboard,
                href: '/waka/dashboard?tab=overview',
                id: 'waka.dashboard',
                tab: 'overview',
              },
              {
                label: 'Rekapitulasi Presensi',
                icon: BarChart3,
                href: '/reports/attendance',
                id: 'reports.attendance.index',
              },
            ],
          },
          {
            groupTitle: 'Akademik & Jadwal',
            items: [
              {
                label: 'Master Jadwal Sekolah',
                icon: CalendarDays,
                href: '/waka/dashboard?tab=schedules',
                id: 'waka.dashboard',
                tab: 'schedules',
                badge: wakaSchedulesCount,
              },
              {
                label: 'Monitoring Jurnal Guru',
                icon: BookOpen,
                href: '/waka/dashboard?tab=journals',
                id: 'waka.dashboard',
                tab: 'journals',
                badge: wakaJournalsCount,
              },
            ],
          },
          {
            groupTitle: 'Data Master Sekolah',
            items: [
              {
                label: 'Pengguna & Penautan Ortu',
                icon: Users,
                href: '/waka/dashboard?tab=users',
                id: 'waka.dashboard',
                tab: 'users',
                badge: wakaUsersCount,
              },
              {
                label: 'Kelas & Ruangan',
                icon: Building2,
                href: '/waka/dashboard?tab=classes_rooms',
                id: 'waka.dashboard',
                tab: 'classes_rooms',
                badge: wakaClassesCount,
              },
            ],
          },
          {
            groupTitle: 'Pengaturan Akun',
            items: [
              {
                label: 'Profil Pengguna',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      case 'guru':
        return [
          {
            groupTitle: 'Pembelajaran & KBM',
            items: [
              {
                label: 'Dashboard Guru',
                icon: LayoutDashboard,
                href: '/guru/dashboard',
                id: 'guru.dashboard',
              },
              {
                label: 'Jadwal Mengajar Mandiri',
                icon: CalendarDays,
                href: '/guru/schedules',
                id: 'guru.schedules.index',
                badge: guruSchedulesCount,
              },
              {
                label: 'Jurnal & Presensi Kelas',
                icon: NotebookPen,
                href: '/guru/journals',
                id: 'guru.journals.index',
                badge: guruJournalsCount,
              },
            ],
          },
          {
            groupTitle: 'Evaluasi & Laporan',
            items: [
              {
                label: 'Rekapitulasi Presensi Rombel',
                icon: BarChart3,
                href: '/reports/attendance',
                id: 'reports.attendance.index',
              },
            ],
          },
          {
            groupTitle: 'Pengaturan Akun',
            items: [
              {
                label: 'Profil Guru',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      case 'wali_kelas':
        return [
          {
            groupTitle: 'Kesiswaan & Perwalian',
            items: [
              {
                label: 'Verifikasi Surat Izin',
                icon: ClipboardCheck,
                href: '/kesiswaan/dashboard',
                id: 'kesiswaan.dashboard',
                badge: kesiswaanPendingCount && kesiswaanPendingCount > 0 ? kesiswaanPendingCount : undefined,
              },
              {
                label: 'Laporan Presensi Kelas',
                icon: BarChart3,
                href: '/reports/attendance',
                id: 'reports.attendance.index',
              },
            ],
          },
          {
            groupTitle: 'Pembelajaran & KBM',
            items: [
              {
                label: 'Dashboard KBM Guru',
                icon: LayoutDashboard,
                href: '/guru/dashboard',
                id: 'guru.dashboard',
              },
              {
                label: 'Jadwal Mengajar',
                icon: CalendarDays,
                href: '/guru/schedules',
                id: 'guru.schedules.index',
                badge: guruSchedulesCount,
              },
              {
                label: 'Input Jurnal Mengajar',
                icon: NotebookPen,
                href: '/guru/journals',
                id: 'guru.journals.index',
                badge: guruJournalsCount,
              },
            ],
          },
          {
            groupTitle: 'Pengaturan Akun',
            items: [
              {
                label: 'Profil Akun',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      case 'guru_bk':
        return [
          {
            groupTitle: 'Bimbingan Konseling',
            items: [
              {
                label: 'Monitoring Izin Siswa',
                icon: ClipboardCheck,
                href: '/kesiswaan/dashboard',
                id: 'kesiswaan.dashboard',
                badge: kesiswaanPendingCount && kesiswaanPendingCount > 0 ? kesiswaanPendingCount : undefined,
              },
              {
                label: 'Laporan Presensi Semua Kelas',
                icon: BarChart3,
                href: '/reports/attendance',
                id: 'reports.attendance.index',
              },
            ],
          },
          {
            groupTitle: 'Layanan BK & KBM',
            items: [
              {
                label: 'Dashboard KBM Guru',
                icon: LayoutDashboard,
                href: '/guru/dashboard',
                id: 'guru.dashboard',
              },
              {
                label: 'Jadwal Layanan / KBM',
                icon: CalendarDays,
                href: '/guru/schedules',
                id: 'guru.schedules.index',
                badge: guruSchedulesCount,
              },
              {
                label: 'Input Jurnal BK / KBM',
                icon: NotebookPen,
                href: '/guru/journals',
                id: 'guru.journals.index',
                badge: guruJournalsCount,
              },
            ],
          },
          {
            groupTitle: 'Pengaturan Akun',
            items: [
              {
                label: 'Profil Akun',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      case 'siswa':
        return [
          {
            groupTitle: 'Portal Perizinan',
            items: [
              {
                label: 'Pengajuan & Riwayat Izin',
                icon: FilePlus2,
                href: '/portal/dashboard',
                id: 'portal.dashboard',
                badge: portalRequestsCount,
              },
            ],
          },
          {
            groupTitle: 'Akun Siswa',
            items: [
              {
                label: 'Profil Siswa',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      case 'orang_tua':
        return [
          {
            groupTitle: 'Portal Orang Tua',
            items: [
              {
                label: 'Perizinan Siswa / Anak',
                icon: FilePlus2,
                href: '/portal/dashboard',
                id: 'portal.dashboard',
                badge: portalRequestsCount,
              },
            ],
          },
          {
            groupTitle: 'Akun Orang Tua',
            items: [
              {
                label: 'Profil Orang Tua',
                icon: UserIcon,
                href: '/profile',
                id: 'profile.edit',
              },
            ],
          },
        ];

      default:
        return [
          {
            items: [
              { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard', id: 'dashboard' },
            ],
          },
        ];
    }
  };

  const navGroups = getNavGroups();

  const handleNavClick = (e: React.MouseEvent, item: NavItem) => {
    // If clicking a waka tab while already on /waka/dashboard, switch tab instantly without page reload
    if (item.tab && window.location.pathname.includes('/waka/dashboard')) {
      e.preventDefault();
      setActiveWakaTab(item.tab);
      window.history.pushState({}, '', item.href);
      window.dispatchEvent(new CustomEvent('scp:change-tab', { detail: item.tab }));
    }
  };

  const handleLogout = (e: React.FormEvent) => {
    e.preventDefault();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    if (csrfToken) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_token';
      input.value = csrfToken;
      form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
  };

  return (
    <MotionConfig reducedMotion="user">
      <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row antialiased text-slate-900">
        {/* DESKTOP COLLAPSIBLE SIDEBAR */}
        <aside
          className={`hidden md:flex flex-col bg-white border-r border-slate-200/80 sticky top-0 h-screen z-30 transition-all duration-200 ease-in-out ${
            isCollapsed ? 'w-[68px]' : 'w-[256px]'
          }`}
        >
          {/* Logo & Brand Header */}
          <div className="h-14 flex items-center px-4 border-b border-slate-100 justify-between">
            <div className="flex items-center gap-2.5 overflow-hidden">
              <div className="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <GraduationCap className="w-4 h-4" />
              </div>
              {!isCollapsed && (
                <div className="truncate">
                  <h1 className="font-semibold text-xs tracking-tight text-slate-900 truncate">
                    School Class Progress
                  </h1>
                  <span className="text-[10px] font-medium text-slate-500 block -mt-0.5">
                    Teaching Factory SMK
                  </span>
                </div>
              )}
            </div>

            <button
              type="button"
              onClick={() => setIsCollapsed(!isCollapsed)}
              className="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
              title={isCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar'}
            >
              {isCollapsed ? <ChevronRight className="w-3.5 h-3.5" /> : <ChevronLeft className="w-3.5 h-3.5" />}
            </button>
          </div>

          {/* Navigation Items Grouped by Section (Filament/SaaS-Style) */}
          <nav className="flex-1 px-3 py-3 overflow-y-auto space-y-3">
            {navGroups.map((group, gIdx) => (
              <div key={gIdx} className="space-y-1">
                {!isCollapsed && group.groupTitle && (
                  <div className="px-2.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1 mt-2">
                    {group.groupTitle}
                  </div>
                )}
                {group.items.map((item) => {
                  const Icon = item.icon;

                  // Active condition: for waka tabs, check tab; for other pages, check route id
                  let isActive = false;
                  if (item.tab && (currentPage === 'waka.dashboard' || currentPage.startsWith('waka.'))) {
                    isActive = activeWakaTab === item.tab;
                  } else {
                    isActive = currentPage === item.id || currentPage.startsWith(item.id);
                  }

                  return (
                    <a
                      key={item.label}
                      href={item.href}
                      onClick={(e) => handleNavClick(e, item)}
                      className={`flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors group relative ${
                        isActive
                          ? 'bg-indigo-50/90 text-indigo-700 font-semibold border border-indigo-100/80 shadow-2xs'
                          : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900'
                      }`}
                      title={isCollapsed ? item.label : undefined}
                    >
                      <Icon
                        className={`w-4 h-4 shrink-0 ${
                          isActive ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600'
                        }`}
                      />
                      {!isCollapsed && <span className="truncate flex-1">{item.label}</span>}
                      {!isCollapsed && item.badge !== undefined && (
                        <span
                          className={`text-[10px] font-semibold px-1.5 py-0.2 rounded-full shrink-0 ${
                            isActive
                              ? 'bg-indigo-200/90 text-indigo-900'
                              : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'
                          }`}
                        >
                          {item.badge}
                        </span>
                      )}

                      {/* Tooltip on collapsed */}
                      {isCollapsed && (
                        <div className="absolute left-full ml-2 px-2.5 py-1 bg-slate-900 text-white text-xs rounded-md shadow-md opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 flex items-center gap-2">
                          <span>{item.label}</span>
                          {item.badge !== undefined && (
                            <span className="bg-slate-700 text-slate-200 text-[10px] px-1.5 py-0.2 rounded-full font-mono">
                              {item.badge}
                            </span>
                          )}
                        </div>
                      )}
                    </a>
                  );
                })}
              </div>
            ))}
          </nav>

          {/* Bottom Sidebar Info & User */}
          <div className="p-3 border-t border-slate-100 space-y-2">
            {!isCollapsed && config && (
              <div className="px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200/60 text-xs">
                <span className="text-[10px] font-semibold text-slate-500 block uppercase tracking-wider">
                  Siklus Pembelajaran
                </span>
                <div className="flex items-center justify-between mt-0.5">
                  <span className="font-semibold text-slate-800 text-[11px]">{config.active_cycle}</span>
                  {config.is_schedule_locked && (
                    <span className="flex items-center gap-1 text-[10px] font-medium text-amber-700">
                      <Lock className="w-3 h-3" /> Terkunci
                    </span>
                  )}
                </div>
              </div>
            )}

            <div className={`flex items-center ${isCollapsed ? 'justify-center' : 'justify-between'} px-1 py-1`}>
              <a
                href="/profile"
                className="flex items-center gap-2 text-xs text-slate-700 hover:text-indigo-600 transition truncate"
              >
                <div className="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-semibold text-xs flex items-center justify-center shrink-0">
                  {user ? user.fullname.substring(0, 2).toUpperCase() : 'U'}
                </div>
                {!isCollapsed && user && (
                  <div className="truncate text-left">
                    <span className="font-semibold block truncate leading-tight text-xs text-slate-900">{user.fullname}</span>
                    <span className="text-[10px] text-slate-500 font-medium truncate block">{getRoleDisplayName(roleName, user)}</span>
                  </div>
                )}
              </a>

              {!isCollapsed && (
                <button
                  type="button"
                  onClick={handleLogout}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                  title="Keluar"
                >
                  <LogOut className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>
        </aside>

        {/* MAIN APPLICATION CONTAINER */}
        <div className="flex-1 flex flex-col min-w-0 pb-16 md:pb-0">
          {/* TOPBAR HEADER (h-14) */}
          <header className="h-14 bg-white/90 backdrop-blur-xs border-b border-slate-200/80 sticky top-0 z-20 flex items-center justify-between px-4 sm:px-6">
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="md:hidden p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer"
              >
                <Menu className="w-5 h-5" />
              </button>

              <div className="flex items-center gap-2 text-xs">
                <span className="font-semibold text-slate-900">{getRoleDisplayName(roleName, user)}</span>
                <span className="text-slate-300">/</span>
                <span className="text-slate-500">Workstation</span>
              </div>
            </div>

            <div className="flex items-center gap-2.5">
              {config && (
                <div className="hidden sm:inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60 font-mono">
                  <span>{config.active_cycle}</span>
                </div>
              )}

              <a
                href="/profile"
                className="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 hover:border-slate-300 text-xs font-medium text-slate-700 transition bg-white shadow-2xs"
              >
                <UserIcon className="w-3.5 h-3.5 text-slate-500" />
                <span className="hidden sm:inline">{user?.username || 'Akun'}</span>
              </a>

              <button
                type="button"
                onClick={handleLogout}
                className="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition cursor-pointer"
              >
                <LogOut className="w-3.5 h-3.5" />
                <span>Keluar</span>
              </button>
            </div>
          </header>

          {/* MAIN PAGE BODY */}
          <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            {children}
          </main>
        </div>

        {/* MOBILE BOTTOM NAVIGATION BAR (< 768px) */}
        <div className="md:hidden fixed bottom-0 inset-x-0 h-16 bg-white/95 backdrop-blur-md border-t border-slate-200/80 z-40 flex items-center justify-around px-2 pb-safe">
          {navGroups
            .flatMap((g) => g.items)
            .slice(0, 4)
            .map((item) => {
              const Icon = item.icon;
              let isActive = false;
              if (item.tab && (currentPage === 'waka.dashboard' || currentPage.startsWith('waka.'))) {
                isActive = activeWakaTab === item.tab;
              } else {
                isActive = currentPage === item.id || currentPage.startsWith(item.id);
              }

              return (
                <a
                  key={item.label}
                  href={item.href}
                  onClick={(e) => handleNavClick(e, item)}
                  className={`flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-colors ${
                    isActive ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-slate-800'
                  }`}
                >
                  <Icon className={`w-4 h-4 mb-0.5 ${isActive ? 'text-indigo-600' : 'text-slate-400'}`} />
                  <span className="truncate max-w-[64px]">{item.label}</span>
                </a>
              );
            })}
          <a
            href="/profile"
            className="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold text-slate-500"
          >
            <UserIcon className="w-4 h-4 mb-0.5 text-slate-400" />
            <span>Profil</span>
          </a>
        </div>

        {/* MOBILE SLIDE-OUT DRAWER WITH FULL CATEGORIZED NAVIGATION */}
        {isMobileMenuOpen && (
          <div className="fixed inset-0 z-50 md:hidden flex">
            {/* Backdrop */}
            <div
              className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
              onClick={() => setIsMobileMenuOpen(false)}
            />

            {/* Drawer Panel */}
            <div className="relative w-4/5 max-w-xs bg-white h-full shadow-2xl flex flex-col z-10 animate-in slide-in-from-left duration-200">
              <div className="h-16 flex items-center justify-between px-4 border-b border-slate-100">
                <div className="flex items-center gap-2.5">
                  <div className="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                    <GraduationCap className="w-5 h-5" />
                  </div>
                  <div>
                    <h2 className="font-bold text-xs text-slate-900">School Class Progress</h2>
                    <span className="text-[10px] text-slate-500 block">Teaching Factory SMK</span>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Grouped Nav Items */}
              <nav className="flex-1 px-3 py-3 overflow-y-auto space-y-4">
                {navGroups.map((group, gIdx) => (
                  <div key={gIdx} className="space-y-1">
                    {group.groupTitle && (
                      <div className="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 mt-2">
                        {group.groupTitle}
                      </div>
                    )}
                    {group.items.map((item) => {
                      const Icon = item.icon;
                      let isActive = false;
                      if (item.tab && (currentPage === 'waka.dashboard' || currentPage.startsWith('waka.'))) {
                        isActive = activeWakaTab === item.tab;
                      } else {
                        isActive = currentPage === item.id || currentPage.startsWith(item.id);
                      }

                      return (
                        <a
                          key={item.label}
                          href={item.href}
                          onClick={(e) => {
                            handleNavClick(e, item);
                            setIsMobileMenuOpen(false);
                          }}
                          className={`flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-colors ${
                            isActive
                              ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-100'
                              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                          }`}
                        >
                          <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-indigo-600' : 'text-slate-400'}`} />
                          <span className="truncate flex-1">{item.label}</span>
                          {item.badge !== undefined && (
                            <span className="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                              {item.badge}
                            </span>
                          )}
                        </a>
                      );
                    })}
                  </div>
                ))}
              </nav>

              {/* Mobile Drawer Bottom User */}
              <div className="p-3 border-t border-slate-100 flex items-center justify-between">
                <a
                  href="/profile"
                  className="flex items-center gap-2.5 truncate"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0">
                    {user ? user.fullname.substring(0, 2).toUpperCase() : 'U'}
                  </div>
                  <div className="truncate text-left text-xs">
                    <span className="font-semibold block truncate leading-tight text-slate-900">{user?.fullname}</span>
                    <span className="text-[11px] text-slate-400 font-medium truncate block">{getRoleDisplayName(roleName, user)}</span>
                  </div>
                </a>
                <button
                  type="button"
                  onClick={handleLogout}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                  title="Keluar"
                >
                  <LogOut className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </MotionConfig>
  );
};
