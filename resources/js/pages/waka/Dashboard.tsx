import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  LayoutDashboard,
  CalendarDays,
  BookOpen,
  Users,
  GraduationCap,
  DoorOpen,
  RefreshCw,
  Lock,
  Unlock,
  AlertOctagon,
  Upload,
  Download,
  Plus,
  Trash2,
  Search,
  Filter,
  CheckCircle2,
  AlertCircle,
  FileSpreadsheet,
  Link as LinkIcon,
  Eye,
  Clock,
  UserCheck,
  Building,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { StatsCard } from '../../components/layout/StatsCard';
import { Input, Select } from '../../components/ui/FormFields';
import { SlideOver } from '../../components/ui/SlideOver';
import { Modal } from '../../components/ui/Modal';
import { FlashAlert } from '../../components/ui/Toast';
import { LessonSlotPicker } from '../../components/schedule/LessonSlotPicker';
import {
  BasePageProps,
  SystemConfig,
  Schedule,
  Journal,
  SchoolClass,
  Room,
  Role,
  User,
  Department,
} from '../../types';

interface WakaDashboardProps extends BasePageProps {
  config: SystemConfig;
  departments?: Department[];
  classes: SchoolClass[];
  rooms: Room[];
  roles: Role[];
  users: User[];
  schedules?: Schedule[];
  journals?: Journal[];
  teachers?: User[];
  students?: User[];
  parents?: User[];
  overdueSchedules?: Schedule[];
}

export const WakaDashboard: React.FC<WakaDashboardProps> = ({
  config,
  departments = [],
  classes = [],
  rooms = [],
  roles = [],
  users = [],
  schedules = [],
  journals = [],
  teachers = [],
  students = [],
  parents = [],
  overdueSchedules = [],
  flash,
  errors,
  csrfToken,
}) => {
  // Determine active tab from URL query params or default to 'overview'
  const getInitialTab = (): 'overview' | 'schedules' | 'journals' | 'users' | 'classes_rooms' => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const t = params.get('tab');
      if (t === 'schedules' || t === 'journals' || t === 'users' || t === 'classes_rooms') {
        return t;
      }
    }
    return 'overview';
  };

  const [activeTab, setActiveTab] = useState<'overview' | 'schedules' | 'journals' | 'users' | 'classes_rooms'>(getInitialTab);

  // Listen for sidebar tab switch events from AppShell
  useEffect(() => {
    const handleTabChange = (e: any) => {
      const t = e.detail;
      if (t === 'overview' || t === 'schedules' || t === 'journals' || t === 'users' || t === 'classes_rooms') {
        setActiveTab(t);
      }
    };
    window.addEventListener('scp:change-tab', handleTabChange);
    return () => window.removeEventListener('scp:change-tab', handleTabChange);
  }, []);

  // Search & Filter States
  const [scheduleCycleFilter, setScheduleCycleFilter] = useState<'All' | 'Putaran 1' | 'Putaran 2'>('All');
  const [scheduleDayFilter, setScheduleDayFilter] = useState<string>('All');
  const [scheduleDepartmentFilter, setScheduleDepartmentFilter] = useState<string>('All');
  const [scheduleGradeFilter, setScheduleGradeFilter] = useState<string>('All');
  const [scheduleSearch, setScheduleSearch] = useState('');

  // State pembuatan kelas/rombel bertingkat & berjurusan
  const [newClassGrade, setNewClassGrade] = useState<'X' | 'XI' | 'XII'>('X');
  const [newClassDeptId, setNewClassDeptId] = useState<string>('');
  const [newClassNumber, setNewClassNumber] = useState<string>('1');

  const [journalSearch, setJournalSearch] = useState('');
  const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);

  const [userRoleFilter, setUserRoleFilter] = useState<string>('All');
  const [userSearch, setUserSearch] = useState('');
  const [newUserRoleId, setNewUserRoleId] = useState<string>('');

  // Modals / SlideOvers
  const [isAddScheduleOpen, setIsAddScheduleOpen] = useState(false);
  const [addScheduleDay, setAddScheduleDay] = useState<string>('Senin');
  const [isAddUserOpen, setIsAddUserOpen] = useState(false);
  const [isLinkParentOpen, setIsLinkParentOpen] = useState(false);
  const [selectedStudentForLink, setSelectedStudentForLink] = useState<User | null>(null);
  const [selectedParentId, setSelectedParentId] = useState<string>('');

  const nextCycle = config?.active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';
  const conflictError = errors?.conflict?.[0];

  // Filtered Schedules (Mendukung filter Putaran, Hari, Jurusan, Tingkat Rombel X/XI/XII, & Search)
  const filteredSchedules = schedules.filter((s) => {
    if (scheduleCycleFilter !== 'All' && s.cycle_type !== scheduleCycleFilter) return false;
    if (scheduleDayFilter !== 'All' && s.day_of_week !== scheduleDayFilter) return false;

    const cls = s.schoolClass || s.school_class;
    const className = cls?.class_name || '';

    // Filter Jurusan
    if (scheduleDepartmentFilter !== 'All') {
      const deptCode = cls?.department?.department_code;
      const deptId = cls?.department_id;
      const matchDept =
        deptCode === scheduleDepartmentFilter ||
        String(deptId) === scheduleDepartmentFilter ||
        className.toUpperCase().includes(scheduleDepartmentFilter.toUpperCase());
      if (!matchDept) return false;
    }

    // Filter Tingkat Rombel (X, XI, XII)
    if (scheduleGradeFilter !== 'All') {
      const grade = cls?.grade;
      const matchGrade =
        grade === scheduleGradeFilter ||
        className.startsWith(scheduleGradeFilter + ' ') ||
        className.startsWith(scheduleGradeFilter + '-');
      if (!matchGrade) return false;
    }

    if (scheduleSearch) {
      const q = scheduleSearch.toLowerCase();
      const matchTeacher = s.teacher?.fullname?.toLowerCase().includes(q);
      const matchSubject = s.subject?.toLowerCase().includes(q);
      const matchClass = className.toLowerCase().includes(q);
      const matchRoom = s.room?.room_name?.toLowerCase().includes(q);
      if (!matchTeacher && !matchSubject && !matchClass && !matchRoom) return false;
    }
    return true;
  });

  // Filtered Journals
  const filteredJournals = journals.filter((j) => {
    if (journalSearch) {
      const q = journalSearch.toLowerCase();
      const matchTeacher = j.schedule?.teacher?.fullname?.toLowerCase().includes(q);
      const matchSubject = j.schedule?.subject?.toLowerCase().includes(q);
      const matchTopic = (j.topic_description || j.topic || '').toLowerCase().includes(q);
      const matchClass = (j.schedule?.schoolClass?.class_name || j.schedule?.school_class?.class_name || '').toLowerCase().includes(q);
      if (!matchTeacher && !matchSubject && !matchTopic && !matchClass) return false;
    }
    return true;
  });

  // Filtered Users
  const filteredUsers = users.filter((u) => {
    if (userRoleFilter === 'teachers') {
      if (!['guru', 'wali_kelas', 'guru_bk'].includes(u.role?.role_name || '')) return false;
    } else if (userRoleFilter !== 'All' && u.role?.role_name !== userRoleFilter) {
      return false;
    }
    if (userSearch) {
      const q = userSearch.toLowerCase();
      const matchName = u.fullname?.toLowerCase().includes(q);
      const matchUser = u.username?.toLowerCase().includes(q);
      if (!matchName && !matchUser) return false;
    }
    return true;
  });

  const selectedRoleObj = roles.find((r) => String(r.id) === String(newUserRoleId));

  const formatUserRoleBadge = (roleName?: string) => {
    switch (roleName) {
      case 'waka_kurikulum':
        return { label: 'Waka Kurikulum', badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
      case 'guru':
        return { label: 'Guru Mapel', badgeClass: 'bg-blue-50 text-blue-700 border-blue-200' };
      case 'wali_kelas':
        return { label: 'Guru (Wali Kelas)', badgeClass: 'bg-amber-50 text-amber-800 border-amber-300' };
      case 'guru_bk':
        return { label: 'Guru BK', badgeClass: 'bg-teal-50 text-teal-800 border-teal-300' };
      case 'siswa':
        return { label: 'Siswa', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
      case 'orang_tua':
        return { label: 'Orang Tua', badgeClass: 'bg-purple-50 text-purple-700 border-purple-200' };
      default:
        return { label: roleName || 'User', badgeClass: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
  };

  // Dynamic Section Header Info
  const getSectionTitle = () => {
    switch (activeTab) {
      case 'schedules':
        return {
          title: 'Master Jadwal Pembelajaran Sekolah',
          subtitle: 'Pengaturan seluruh sesi mengajar guru, ruang lab/teori, dan rombel pada Putaran 1 & Putaran 2.',
        };
      case 'journals':
        return {
          title: 'Monitoring Jurnal & Presensi Seluruh Guru',
          subtitle: 'Inspeksi realtime materi kompetensi ajar, foto bukti dokumentasi kelas, dan rekapitulasi kehadiran siswa.',
        };
      case 'users':
        return {
          title: 'Manajemen Pengguna & Penautan Orang Tua',
          subtitle: 'Kelola seluruh akun pengguna dan hubungkan akun orang tua dengan siswa binaan secara langsung.',
        };
      case 'classes_rooms':
        return {
          title: 'Master Data Kelas Rombel & Ruangan',
          subtitle: 'Kelola rombongan belajar kejuruan dan ruangan laboratorium praktikum / teori.',
        };
      default:
        return {
          title: 'Ringkasan & Operasional Kurikulum',
          subtitle: 'Kendali penuh siklus blok mingguan, integritas penguncian jadwal, dan monitoring keterlambatan pengisian jurnal.',
        };
    }
  };

  const sectionInfo = getSectionTitle();

  return (
    <div className="space-y-6">
      {/* Flash Notifications */}
      <FlashAlert flash={flash} />

      {/* Conflict Validation Alert */}
      {conflictError && (
        <motion.div
          initial={{ opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
          className="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3"
        >
          <AlertCircle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div>
            <h4 className="text-xs font-bold text-red-900">Validasi Bentrok Jadwal</h4>
            <p className="text-xs text-red-700 mt-0.5">{conflictError}</p>
          </div>
        </motion.div>
      )}

      {/* CSV Import errors notice */}
      {flash?.import_errors && flash.import_errors.length > 0 && (
        <div className="p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div className="text-xs">
            <h4 className="font-bold text-amber-900 uppercase">Catatan Hasil Import Jadwal:</h4>
            <ul className="list-disc list-inside text-amber-800 mt-1 space-y-0.5">
              {flash.import_errors.map((err, i) => (
                <li key={i}>{err}</li>
              ))}
            </ul>
          </div>
        </div>
      )}

      {/* Header Band */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/70 font-mono">
              Panel Kurikulum &amp; Admin
            </span>
            <span className="text-xs text-slate-400">Teaching Factory System</span>
          </div>
          <h1 className="text-xl font-bold text-slate-900 tracking-tight mt-1.5">
            {sectionInfo.title}
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            {sectionInfo.subtitle}
          </p>
        </div>

        {/* Global Cycle & Lock Quick Indicators */}
        <div className="flex items-center gap-2 shrink-0">
          <div className="px-2.5 py-1 rounded-lg bg-white border border-slate-200/80 shadow-2xs flex items-center gap-1.5 text-xs">
            <span className="text-slate-500">Siklus:</span>
            <strong className="text-slate-900 font-semibold">{config?.active_cycle}</strong>
          </div>
          <div className="px-2.5 py-1 rounded-lg bg-white border border-slate-200/80 shadow-2xs flex items-center gap-1.5 text-xs">
            {config?.is_schedule_locked ? (
              <>
                <Lock className="w-3.5 h-3.5 text-red-600" />
                <span className="text-red-700 font-semibold">Jadwal Terkunci</span>
              </>
            ) : (
              <>
                <Unlock className="w-3.5 h-3.5 text-emerald-600" />
                <span className="text-emerald-700 font-semibold">Input Terbuka</span>
              </>
            )}
          </div>
        </div>
      </div>

      {/* TAB CONTENT 1: OVERVIEW & SYSTEM CONFIGURATION */}
      {activeTab === 'overview' && (
        <motion.div
          key="tab-overview"
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2 }}
          className="space-y-6"
        >
          {/* Controls: Cycle Toggle & Lock Schedule */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Cycle Toggle */}
            <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-2.5">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <RefreshCw className="w-3.5 h-3.5 text-indigo-600" />
                    Siklus Pembelajaran Blok
                  </span>
                  <span className={`px-2 py-0.5 rounded-full text-[11px] font-semibold border ${
                    config?.active_cycle === 'Putaran 1'
                      ? 'bg-blue-50 text-blue-700 border-blue-200/70'
                      : 'bg-purple-50 text-purple-700 border-purple-200/70'
                  }`}>
                    {config?.active_cycle} Aktif
                  </span>
                </div>
                <h3 className="text-base font-semibold text-slate-900 mb-1">
                  Putaran Mingguan Teaching Factory
                </h3>
                <p className="text-xs text-slate-500 leading-relaxed mb-5">
                  Mengatur rotasi siswa antara blok teori dan blok praktik industri. Seluruh jadwal guru, jurnal harian, dan presensi otomatis menyesuaikan putaran aktif.
                </p>
              </div>

              <form method="POST" action="/waka/config/toggle-cycle">
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="_method" value="PATCH" />
                <Button type="submit" variant="primary" size="md" className="w-full justify-center" icon={RefreshCw}>
                  Ganti Siklus ke {nextCycle}
                </Button>
              </form>
            </div>

            {/* Lock Schedule Toggle */}
            <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-2.5">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    {config?.is_schedule_locked ? (
                      <Lock className="w-3.5 h-3.5 text-red-600" />
                    ) : (
                      <Unlock className="w-3.5 h-3.5 text-emerald-600" />
                    )}
                    Integritas Jadwal Mandiri
                  </span>
                  <span className={`px-2 py-0.5 rounded-full text-[11px] font-semibold border ${
                    config?.is_schedule_locked
                      ? 'bg-red-50 text-red-700 border-red-200/70'
                      : 'bg-emerald-50 text-emerald-700 border-emerald-200/70'
                  }`}>
                    {config?.is_schedule_locked ? 'Terkunci' : 'Terbuka'}
                  </span>
                </div>
                <h3 className="text-base font-semibold text-slate-900 mb-1">
                  {config?.is_schedule_locked ? 'Jadwal Dikunci Penuh' : 'Masa Pengisian Mandiri Aktif'}
                </h3>
                <p className="text-xs text-slate-500 leading-relaxed mb-5">
                  {config?.is_schedule_locked
                    ? 'Guru TIDAK DAPAT menambah atau menghapus jadwal mengajar. Form input dan endpoint terkunci untuk memastikan kestabilan operasional.'
                    : 'Masa input mandiri sedang dibuka: Guru bebas menyusun jadwal mengajar mereka sesuai SK pembagian tugas.'}
                </p>
              </div>

              <form method="POST" action="/waka/config/toggle-lock">
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="_method" value="PATCH" />
                <Button
                  type="submit"
                  variant={config?.is_schedule_locked ? 'secondary' : 'destructive'}
                  size="md"
                  className="w-full justify-center"
                  icon={config?.is_schedule_locked ? Unlock : Lock}
                >
                  {config?.is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Semua Jadwal'}
                </Button>
              </form>
            </div>
          </div>

          {/* KPI Metrics */}
          <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <StatsCard
              title="Total Jadwal"
              value={schedules.length}
              subtitle="Sesi aktif di sekolah"
              icon={CalendarDays}
            />
            <StatsCard
              title="Jurnal Terisi"
              value={journals.length}
              subtitle="Dokumentasi KBM"
              icon={BookOpen}
            />
            <StatsCard
              title="Total Siswa"
              value={students.length}
              subtitle="Peserta didik"
              icon={GraduationCap}
            />
            <StatsCard
              title="Total Guru"
              value={teachers.length}
              subtitle="Tenaga pengajar"
              icon={Users}
            />
            <StatsCard
              title="Kelas Rombel"
              value={classes.length}
              subtitle="Rombongan belajar"
              icon={Building}
            />
          </div>

          {/* Overdue Journal Alert Banner (>30 Menit) */}
          <div className="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
              <div>
                <h3 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                  <AlertOctagon className={`w-4 h-4 ${overdueSchedules.length > 0 ? 'text-red-600' : 'text-emerald-600'}`} />
                  Rekap Keterlambatan Jurnal Hari Ini (&gt;30 Menit)
                </h3>
                <p className="text-xs text-slate-500 mt-0.5">
                  Daftar sesi yang telah selesai mengajarnya namun belum diisi jurnal melebihi batas toleransi 30 menit.
                </p>
              </div>
              <span className={`px-2 py-0.5 rounded-full text-[11px] font-semibold border ${
                overdueSchedules.length === 0
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200/70'
                  : 'bg-red-50 text-red-700 border-red-200/70'
              }`}>
                {overdueSchedules.length === 0 ? 'Semua Jurnal Terisi' : `${overdueSchedules.length} Sesi Terlambat`}
              </span>
            </div>

            {overdueSchedules.length === 0 ? (
              <div className="text-center py-6">
                <CheckCircle2 className="w-8 h-8 text-emerald-500 mx-auto mb-1.5" />
                <p className="text-xs font-semibold text-slate-800">Semua Jurnal Hari Ini Tertib Terisi</p>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  Tidak ada guru yang terlambat mengisi jurnal melebihi batas waktu 30 menit.
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-700 uppercase font-semibold text-[11px] border-y border-slate-200/80">
                    <tr>
                      <th className="py-2.5 px-3">Guru Pengampu</th>
                      <th className="py-2.5 px-3">Mata Pelajaran</th>
                      <th className="py-2.5 px-3">Kelas</th>
                      <th className="py-2.5 px-3">Ruangan</th>
                      <th className="py-2.5 px-3 text-center">Jam Berakhir</th>
                      <th className="py-2.5 px-3 text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {overdueSchedules.map((ovd) => (
                      <tr key={ovd.id} className="hover:bg-red-50/30 transition">
                        <td className="py-2.5 px-3 font-semibold text-slate-900">{ovd.teacher?.fullname || 'Guru'}</td>
                        <td className="py-2.5 px-3 text-slate-800">{ovd.subject}</td>
                        <td className="py-2.5 px-3">
                          <span className="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded font-semibold text-[11px]">
                            {ovd.schoolClass?.class_name || ovd.school_class?.class_name || '-'}
                          </span>
                        </td>
                        <td className="py-2.5 px-3 text-slate-600">{ovd.room?.room_name || '-'}</td>
                        <td className="py-2.5 px-3 text-center font-mono font-semibold text-slate-700">
                          {ovd.end_time?.substring(0, 5)} WIB
                        </td>
                        <td className="py-2.5 px-3 text-center">
                          <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800 border border-red-200/70">
                            Terlambat &gt;30 Mnt
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          {/* CSV Import */}
          <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
              <div>
                <div className="flex items-center gap-2">
                  <FileSpreadsheet className="w-4 h-4 text-indigo-600" />
                  <h3 className="font-semibold text-slate-900 text-sm">Import Jadwal Massal (.CSV)</h3>
                </div>
                <p className="text-xs text-slate-500 mt-0.5">
                  Validasi otomatis irisan bentrok guru/ruang (BR-001) dan auto-register akun guru baru jika belum ada di database.
                </p>
              </div>
              <a
                href="/waka/schedules/template"
                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg border border-slate-200/80 transition"
              >
                <Download className="w-3.5 h-3.5" />
                <span>Template CSV</span>
              </a>
            </div>

            <form
              method="POST"
              action="/waka/schedules/import"
              encType="multipart/form-data"
              className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-1"
            >
              <input type="hidden" name="_token" value={csrfToken} />
              <div className="flex-1">
                <input
                  type="file"
                  name="file"
                  accept=".csv,text/csv,text/plain"
                  required
                  className="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-lg p-1"
                />
              </div>
              <Button type="submit" variant="primary" size="md" icon={Upload}>
                Mulai Import CSV
              </Button>
            </form>
          </div>
        </motion.div>
      )}

      {/* TAB CONTENT 2: MASTER JADWAL SEKOLAH */}
      {activeTab === 'schedules' && (
        <motion.div
          key="tab-schedules"
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2 }}
          className="space-y-4"
        >
          {/* Toolbar */}
          <div className="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div className="flex flex-wrap items-center gap-2">
              {/* Cycle Filter */}
              <div className="inline-flex rounded-lg border border-slate-200/80 p-0.5 bg-slate-100 gap-0.5 text-xs">
                {(['All', 'Putaran 1', 'Putaran 2'] as const).map((cycle) => (
                  <button
                    key={cycle}
                    onClick={() => setScheduleCycleFilter(cycle)}
                    className={`px-2.5 py-1 rounded-md font-medium transition cursor-pointer ${
                      scheduleCycleFilter === cycle
                        ? 'bg-white text-slate-900 font-semibold shadow-2xs'
                        : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    {cycle === 'All' ? 'Semua Putaran' : cycle}
                  </button>
                ))}
              </div>

              {/* Day Filter */}
              <select
                value={scheduleDayFilter}
                onChange={(e) => setScheduleDayFilter(e.target.value)}
                className="h-8 text-xs rounded-lg border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white"
              >
                <option value="All">Semua Hari</option>
                {['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'].map((d) => (
                  <option key={d} value={d}>
                    {d}
                  </option>
                ))}
              </select>

              {/* Jurusan Filter */}
              <select
                value={scheduleDepartmentFilter}
                onChange={(e) => setScheduleDepartmentFilter(e.target.value)}
                className="h-8 text-xs rounded-lg border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-700 bg-white"
              >
                <option value="All">Semua Jurusan</option>
                {departments.map((dept) => (
                  <option key={dept.id} value={dept.department_code}>
                    Jurusan {dept.department_code}
                  </option>
                ))}
              </select>

              {/* Tingkat Rombel (X, XI, XII) Filter */}
              <select
                value={scheduleGradeFilter}
                onChange={(e) => setScheduleGradeFilter(e.target.value as 'All' | 'X' | 'XI' | 'XII')}
                className="h-8 text-xs rounded-lg border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-700 bg-white"
              >
                <option value="All">Semua Rombel</option>
                <option value="X">Kelas X</option>
                <option value="XI">Kelas XI</option>
                <option value="XII">Kelas XII</option>
              </select>
            </div>

            <div className="flex items-center gap-2">
              <div className="relative flex-1 md:w-60">
                <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                <input
                  type="text"
                  placeholder="Cari guru, mapel, kelas..."
                  value={scheduleSearch}
                  onChange={(e) => setScheduleSearch(e.target.value)}
                  className="w-full h-8 text-xs pl-8 pr-3 rounded-lg border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 placeholder:text-slate-400"
                />
              </div>

              <Button
                type="button"
                variant="primary"
                size="sm"
                icon={Plus}
                onClick={() => setIsAddScheduleOpen(true)}
              >
                Tambah Jadwal
              </Button>
            </div>
          </div>

          {/* Schedules Table */}
          <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h4 className="font-semibold text-slate-900 text-sm">Jadwal Mengajar Seluruh Sekolah</h4>
                <p className="text-xs text-slate-500">Menampilkan {filteredSchedules.length} jadwal mengajar</p>
              </div>
            </div>

            {filteredSchedules.length === 0 ? (
              <div className="p-10 text-center text-slate-400 text-xs">
                Tidak ada jadwal yang cocok dengan filter atau pencarian Anda.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-600 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                      <th className="py-3 px-4">Siklus</th>
                      <th className="py-3 px-4">Hari &amp; Waktu</th>
                      <th className="py-3 px-4">Guru Pengampu</th>
                      <th className="py-3 px-4">Mata Pelajaran</th>
                      <th className="py-3 px-4">Kelas Rombel</th>
                      <th className="py-3 px-4">Ruangan</th>
                      <th className="py-3 px-4 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-medium">
                    {filteredSchedules.map((sch) => (
                      <tr key={sch.id} className="hover:bg-slate-50/70 transition">
                        <td className="py-3 px-4">
                          <span className={`px-2 py-0.5 rounded font-bold text-[10px] ${
                            sch.cycle_type === 'Putaran 1'
                              ? 'bg-blue-50 text-blue-700 border border-blue-200'
                              : 'bg-purple-50 text-purple-700 border border-purple-200'
                          }`}>
                            {sch.cycle_type}
                          </span>
                        </td>
                        <td className="py-3 px-4 whitespace-nowrap">
                          <span className="font-bold text-slate-900 block">{sch.day_of_week}</span>
                          <span className="text-[11px] font-mono text-slate-500">
                            {sch.start_time?.substring(0, 5)} - {sch.end_time?.substring(0, 5)}
                          </span>
                        </td>
                        <td className="py-3 px-4 font-bold text-slate-900">
                          {sch.teacher?.fullname || 'Guru'}
                        </td>
                        <td className="py-3 px-4 text-slate-800">{sch.subject}</td>
                        <td className="py-3 px-4">
                          <span className="px-2 py-0.5 rounded font-semibold text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {sch.schoolClass?.class_name || sch.school_class?.class_name || '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-slate-600">{sch.room?.room_name || '-'}</td>
                        <td className="py-3 px-4 text-right">
                          <form
                            method="POST"
                            action={`/waka/schedules/${sch.id}`}
                            onSubmit={(e) => {
                              if (!window.confirm(`Hapus jadwal ${sch.subject} (${sch.teacher?.fullname})?`)) {
                                e.preventDefault();
                              }
                            }}
                            className="inline"
                          >
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="DELETE" />
                            <Button type="submit" variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50">
                              <Trash2 className="w-3.5 h-3.5" />
                            </Button>
                          </form>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </motion.div>
      )}

      {/* TAB CONTENT 3: MONITORING JURNAL GURU */}
      {activeTab === 'journals' && (
        <motion.div
          key="tab-journals"
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2 }}
          className="space-y-4"
        >
          {/* Search bar */}
          <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div className="relative flex-1 max-w-md">
              <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
              <input
                type="text"
                placeholder="Cari nama guru, mapel, materi..."
                value={journalSearch}
                onChange={(e) => setJournalSearch(e.target.value)}
                className="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border-slate-300 focus:border-indigo-600"
              />
            </div>
            <span className="text-xs font-mono text-slate-500">
              Total Jurnal: {filteredJournals.length}
            </span>
          </div>

          {/* Journals Table */}
          <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h4 className="font-bold text-slate-900 text-sm">Monitoring Jurnal Mengajar Seluruh Guru</h4>
                <p className="text-xs text-slate-500">Periksa topik ajar, rekaman foto kegiatan siswa di kelas, dan presensi</p>
              </div>
            </div>

            {filteredJournals.length === 0 ? (
              <div className="p-10 text-center text-slate-400 text-xs">
                Belum ada jurnal mengajar yang sesuai pencarian.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-600 uppercase font-semibold text-[11px] border-b border-slate-200">
                    <tr>
                      <th className="py-3 px-4">Tanggal &amp; Waktu</th>
                      <th className="py-3 px-4">Guru Pengampu</th>
                      <th className="py-3 px-4">Mata Pelajaran &amp; Kelas</th>
                      <th className="py-3 px-4">Topik Materi</th>
                      <th className="py-3 px-4 text-center">Rekap Absensi</th>
                      <th className="py-3 px-4 text-center">Bukti Foto</th>
                      <th className="py-3 px-4 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-medium text-slate-700">
                    {filteredJournals.map((jnl) => {
                      const attHadir = jnl.attendances?.filter((a) => a.status === 'Hadir').length || 0;
                      const attIzin = jnl.attendances?.filter((a) => a.status === 'Izin').length || 0;
                      const attSakit = jnl.attendances?.filter((a) => a.status === 'Sakit').length || 0;
                      const attAlpha = jnl.attendances?.filter((a) => a.status === 'Alpha' || a.status === 'Alpa').length || 0;

                      return (
                        <tr key={jnl.id} className="hover:bg-slate-50/70 transition">
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span className="font-bold text-slate-900 block">{jnl.date}</span>
                            <span className="text-[11px] font-mono text-slate-500">
                              {jnl.schedule?.start_time?.substring(0, 5)} - {jnl.schedule?.end_time?.substring(0, 5)}
                            </span>
                          </td>
                          <td className="py-3 px-4 whitespace-nowrap font-bold text-slate-900">
                            {jnl.schedule?.teacher?.fullname || 'Guru'}
                          </td>
                          <td className="py-3 px-4 whitespace-nowrap">
                            <span className="font-bold text-slate-900 block">{jnl.schedule?.subject}</span>
                            <span className="text-[11px] text-slate-500">
                              {jnl.schedule?.schoolClass?.class_name || jnl.schedule?.school_class?.class_name || '-'} / {jnl.schedule?.room?.room_name || '-'}
                            </span>
                          </td>
                          <td className="py-3 px-4 max-w-xs truncate text-slate-700" title={jnl.topic_description || jnl.topic}>
                            {jnl.topic_description || jnl.topic || '-'}
                          </td>
                          <td className="py-3 px-4 text-center whitespace-nowrap">
                            <div className="inline-flex items-center gap-1 text-[10px] font-bold">
                              <span className="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded">H: {attHadir}</span>
                              <span className="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded">I: {attIzin}</span>
                              <span className="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded">S: {attSakit}</span>
                              <span className="px-1.5 py-0.5 bg-red-100 text-red-800 rounded">A: {attAlpha}</span>
                            </div>
                          </td>
                          <td className="py-3 px-4 text-center whitespace-nowrap">
                            {jnl.photo_path ? (
                              <button
                                type="button"
                                onClick={() => setSelectedPhoto(`/storage/${jnl.photo_path}`)}
                                className="inline-block relative group cursor-pointer"
                              >
                                <img
                                  src={`/storage/${jnl.photo_path}`}
                                  alt="Bukti Foto"
                                  className="w-9 h-9 object-cover rounded-lg border border-slate-200 group-hover:scale-105 transition"
                                />
                              </button>
                            ) : (
                              <span className="text-slate-400 text-xs">-</span>
                            )}
                          </td>
                          <td className="py-3 px-4 text-center whitespace-nowrap">
                            <a
                              href={`/guru/journals/${jnl.id}`}
                              className="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs transition"
                            >
                              <Eye className="w-3.5 h-3.5" />
                              <span>Lihat Detail</span>
                            </a>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </motion.div>
      )}

      {/* TAB CONTENT 4: PENGGUNA & PENAUTAN ORTU */}
      {activeTab === 'users' && (
        <motion.div
          key="tab-users"
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2 }}
          className="space-y-4"
        >
          {/* Toolbar */}
          <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div className="flex flex-wrap items-center gap-1">
              {[
                { id: 'All', label: 'Semua Akun' },
                { id: 'teachers', label: '👨‍🏫 Semua Guru (Pendidik)' },
                { id: 'guru', label: 'Guru Mapel' },
                { id: 'wali_kelas', label: 'Wali Kelas' },
                { id: 'guru_bk', label: 'Guru BK' },
                { id: 'siswa', label: 'Siswa' },
                { id: 'orang_tua', label: 'Orang Tua' },
                { id: 'waka_kurikulum', label: 'Waka' },
              ].map((r) => (
                <button
                  key={r.id}
                  onClick={() => setUserRoleFilter(r.id)}
                  className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                    userRoleFilter === r.id
                      ? 'bg-indigo-600 text-white shadow-2xs'
                      : 'text-slate-600 hover:text-slate-900 bg-slate-50'
                  }`}
                >
                  {r.label}
                </button>
              ))}
            </div>

            <div className="flex items-center gap-2">
              <div className="relative flex-1 md:w-64">
                <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
                <input
                  type="text"
                  placeholder="Cari nama atau username..."
                  value={userSearch}
                  onChange={(e) => setUserSearch(e.target.value)}
                  className="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border-slate-300 focus:border-indigo-600"
                />
              </div>

              <Button
                type="button"
                variant="primary"
                icon={Plus}
                onClick={() => setIsAddUserOpen(true)}
              >
                Tambah Pengguna
              </Button>
            </div>
          </div>

          {/* Users Table */}
          <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h4 className="font-bold text-slate-900 text-sm">Daftar Akun Pengguna Terdaftar</h4>
                <p className="text-xs text-slate-500">
                  Menampilkan {filteredUsers.length} pengguna (Guru, Siswa, Orang Tua, Staf)
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-slate-100 text-xs">
                <thead className="bg-slate-50 text-slate-600 uppercase font-semibold text-[11px]">
                  <tr>
                    <th className="px-6 py-3 text-left">Nama Lengkap</th>
                    <th className="px-6 py-3 text-left">Username / NIS</th>
                    <th className="px-6 py-3 text-left">Peran (Role)</th>
                    <th className="px-6 py-3 text-left">Kelas Rombel</th>
                    <th className="px-6 py-3 text-left">Relasi Orang Tua / Anak</th>
                    <th className="px-6 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white font-medium">
                  {filteredUsers.map((u) => {
                    const isStudent = u.role?.role_name === 'siswa';
                    const isParent = u.role?.role_name === 'orang_tua';

                    return (
                      <tr key={u.id} className="hover:bg-slate-50/70 transition">
                        <td className="px-6 py-3 font-bold text-slate-900">{u.fullname}</td>
                        <td className="px-6 py-3 font-mono text-slate-600">{u.username}</td>
                        <td className="px-6 py-3">
                          {(() => {
                            const badge = formatUserRoleBadge(u.role?.role_name);
                            return (
                              <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${badge.badgeClass}`}>
                                {badge.label}
                              </span>
                            );
                          })()}
                        </td>
                        <td className="px-6 py-3 text-slate-600">
                          {u.schoolClass?.class_name || u.school_class?.class_name || '-'}
                        </td>
                        <td className="px-6 py-3">
                          {isStudent && (
                            <div className="flex items-center gap-2">
                              {u.parent ? (
                                <span className="inline-flex items-center gap-1 text-slate-700 font-semibold">
                                  <UserCheck className="w-3.5 h-3.5 text-emerald-600" />
                                  {u.parent.fullname}
                                </span>
                              ) : (
                                <span className="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200 text-[10px]">
                                  Belum Ditautkan
                                </span>
                              )}
                              <button
                                type="button"
                                onClick={() => {
                                  setSelectedStudentForLink(u);
                                  setSelectedParentId(String(u.parent_id || ''));
                                  setIsLinkParentOpen(true);
                                }}
                                className="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold underline"
                              >
                                {u.parent ? 'Ubah' : 'Tautkan'}
                              </button>
                            </div>
                          )}

                          {isParent && (
                            <div className="text-slate-600">
                              {u.children && u.children.length > 0 ? (
                                <span>{u.children.map((c) => c.fullname).join(', ')}</span>
                              ) : (
                                <span className="text-slate-400 italic">Belum ada siswa</span>
                              )}
                            </div>
                          )}

                          {!isStudent && !isParent && <span className="text-slate-400">-</span>}
                        </td>
                        <td className="px-6 py-3 text-right">
                          <form
                            method="POST"
                            action={`/waka/users/${u.id}`}
                            onSubmit={(e) => {
                              if (!window.confirm(`Hapus akun pengguna ${u.fullname} (${u.username})?`)) {
                                e.preventDefault();
                              }
                            }}
                            className="inline"
                          >
                            <input type="hidden" name="_token" value={csrfToken} />
                            <input type="hidden" name="_method" value="DELETE" />
                            <Button type="submit" variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50">
                              <Trash2 className="w-3.5 h-3.5" />
                            </Button>
                          </form>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </motion.div>
      )}

      {/* TAB CONTENT 5: JURUSAN, KELAS & RUANGAN */}
      {activeTab === 'classes_rooms' && (
        <motion.div
          key="tab-classes-rooms"
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2 }}
          className="space-y-6"
        >
          {/* Panel 1: Master Jurusan (Departments) */}
          <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                  <Building className="w-4 h-4 text-indigo-600" />
                  Master Jurusan Kejuruan (Program Keahlian SMK)
                </h3>
                <p className="text-xs text-slate-500">Total {departments.length} konsentrasi keahlian terdaftar</p>
              </div>
            </div>

            {/* Form Tambah Jurusan */}
            <form method="POST" action="/waka/departments" className="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <input type="hidden" name="_token" value={csrfToken} />
              <input
                type="text"
                name="department_code"
                placeholder="Kode (contoh: RPL)"
                required
                className="text-xs px-3 py-2 rounded-xl border-slate-300 focus:border-indigo-600 uppercase"
              />
              <input
                type="text"
                name="department_name"
                placeholder="Nama Lengkap Jurusan"
                required
                className="text-xs px-3 py-2 rounded-xl border-slate-300 focus:border-indigo-600"
              />
              <Button type="submit" variant="primary">
                Simpan Jurusan
              </Button>
            </form>

            {/* Tabel Jurusan */}
            <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden text-xs">
              {departments.map((dept) => (
                <div key={dept.id} className="p-3 flex items-center justify-between hover:bg-slate-50 transition">
                  <div className="flex items-center gap-2">
                    <span className="px-2 py-0.5 rounded font-black text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                      {dept.department_code}
                    </span>
                    <span className="font-semibold text-slate-900">{dept.department_name}</span>
                    <span className="text-[11px] text-slate-400">({dept.classes_count ?? 0} rombel)</span>
                  </div>
                  <form
                    method="POST"
                    action={`/waka/departments/${dept.id}`}
                    onSubmit={(e) => {
                      if (!window.confirm(`Hapus jurusan ${dept.department_code} (${dept.department_name})?`)) {
                        e.preventDefault();
                      }
                    }}
                  >
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="_method" value="DELETE" />
                    <Button type="submit" variant="ghost" size="sm" className="text-red-600 hover:text-red-700">
                      <Trash2 className="w-3.5 h-3.5" />
                    </Button>
                  </form>
                </div>
              ))}
            </div>
          </div>

          {/* Grid 2 Kolom: Kelas / Rombel & Ruangan */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Panel Rombongan Belajar (Kelas) */}
            <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                  <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <GraduationCap className="w-4 h-4 text-indigo-600" />
                    Master Rombongan Belajar (Rombel)
                  </h3>
                  <p className="text-xs text-slate-500">Total {classes.length} kelas aktif (Tingkat X, XI, XII)</p>
                </div>
              </div>

              {/* Form Tambah Rombel dengan Opsi Tingkat & Jurusan */}
              <form method="POST" action="/waka/classes" className="space-y-2 p-3 bg-slate-50/80 border border-slate-200/60 rounded-xl text-xs">
                <input type="hidden" name="_token" value={csrfToken} />
                <div className="font-bold text-slate-700">Buat Rombel Terstruktur:</div>
                <div className="grid grid-cols-3 gap-2">
                  <div>
                    <label className="block text-[10px] font-bold text-slate-500 mb-1">Tingkat</label>
                    <select
                      name="grade"
                      value={newClassGrade}
                      onChange={(e) => setNewClassGrade(e.target.value as any)}
                      className="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-600 bg-white"
                    >
                      <option value="X">Kelas X</option>
                      <option value="XI">Kelas XI</option>
                      <option value="XII">Kelas XII</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-slate-500 mb-1">Jurusan</label>
                    <select
                      name="department_id"
                      value={newClassDeptId}
                      onChange={(e) => setNewClassDeptId(e.target.value)}
                      className="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-600 bg-white"
                    >
                      <option value="">-- Pilih --</option>
                      {departments.map((dept) => (
                        <option key={dept.id} value={dept.id}>
                          {dept.department_code}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-slate-500 mb-1">No. Rombel</label>
                    <input
                      type="text"
                      name="rombel_number"
                      value={newClassNumber}
                      onChange={(e) => setNewClassNumber(e.target.value)}
                      placeholder="1, 2, 3..."
                      className="w-full text-xs px-2.5 py-2 rounded-xl border-slate-300 focus:border-indigo-600 bg-white"
                    />
                  </div>
                </div>

                <div className="pt-1 flex items-center justify-between">
                  <span className="text-[11px] text-slate-500 font-medium">
                    Hasil: <span className="font-bold text-indigo-700">{newClassGrade} {departments.find(d => String(d.id) === newClassDeptId)?.department_code || '...'} {newClassNumber}</span>
                  </span>
                  <Button type="submit" variant="primary" size="sm">
                    Simpan Rombel
                  </Button>
                </div>
              </form>

              {/* List Kelas */}
              <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden text-xs max-h-96 overflow-y-auto">
                {classes.map((c) => (
                  <div key={c.id} className="p-3 flex items-center justify-between hover:bg-slate-50 transition">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-slate-900">{c.class_name}</span>
                      {c.grade && (
                        <span className="px-1.5 py-0.5 rounded font-bold text-[10px] bg-blue-50 text-blue-700 border border-blue-200">
                          {c.grade}
                        </span>
                      )}
                      {c.department && (
                        <span className="px-1.5 py-0.5 rounded font-bold text-[10px] bg-purple-50 text-purple-700 border border-purple-200">
                          {c.department.department_code}
                        </span>
                      )}
                    </div>
                    <form
                      method="POST"
                      action={`/waka/classes/${c.id}`}
                      onSubmit={(e) => {
                        if (!window.confirm(`Hapus kelas ${c.class_name}?`)) {
                          e.preventDefault();
                        }
                      }}
                    >
                      <input type="hidden" name="_token" value={csrfToken} />
                      <input type="hidden" name="_method" value="DELETE" />
                      <Button type="submit" variant="ghost" size="sm" className="text-red-600 hover:text-red-700">
                        <Trash2 className="w-3.5 h-3.5" />
                      </Button>
                    </form>
                  </div>
                ))}
              </div>
            </div>

            {/* Panel Ruangan */}
            <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                  <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <DoorOpen className="w-4 h-4 text-indigo-600" />
                    Master Ruangan (Lab &amp; Teori)
                  </h3>
                  <p className="text-xs text-slate-500">Total {rooms.length} ruangan aktif</p>
                </div>
              </div>

              {/* Form Tambah Ruangan */}
              <form method="POST" action="/waka/rooms" className="flex gap-2">
                <input type="hidden" name="_token" value={csrfToken} />
                <input
                  type="text"
                  name="room_name"
                  placeholder="Nama Ruangan (contoh: Lab RPL 3)"
                  required
                  className="flex-1 text-xs px-3 py-2 rounded-xl border-slate-300 focus:border-indigo-600"
                />
                <Button type="submit" variant="primary">
                  Simpan Ruangan
                </Button>
              </form>

              {/* List Ruangan */}
              <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden text-xs max-h-96 overflow-y-auto">
                {rooms.map((r) => (
                  <div key={r.id} className="p-3 flex items-center justify-between hover:bg-slate-50 transition">
                    <span className="font-bold text-slate-900">{r.room_name}</span>
                    <form
                      method="POST"
                      action={`/waka/rooms/${r.id}`}
                      onSubmit={(e) => {
                        if (!window.confirm(`Hapus ruangan ${r.room_name}?`)) {
                          e.preventDefault();
                        }
                      }}
                    >
                      <input type="hidden" name="_token" value={csrfToken} />
                      <input type="hidden" name="_method" value="DELETE" />
                      <Button type="submit" variant="ghost" size="sm" className="text-red-600 hover:text-red-700">
                        <Trash2 className="w-3.5 h-3.5" />
                      </Button>
                    </form>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </motion.div>
      )}

      {/* MODAL 1: TAMBAH JADWAL SEKOLAH */}
      <SlideOver
        isOpen={isAddScheduleOpen}
        onClose={() => setIsAddScheduleOpen(false)}
        title="Tambah Jadwal Sekolah Baru"
        subtitle="Sistem Anti-Bentrok 3D (Guru, Ruang, dan Rombel)"
      >
        <form method="POST" action="/waka/schedules" className="space-y-4">
          <input type="hidden" name="_token" value={csrfToken} />

          <Select
            label="Siklus Putaran"
            name="cycle_type"
            required
            options={[
              { value: 'Putaran 1', label: 'Putaran 1' },
              { value: 'Putaran 2', label: 'Putaran 2' },
            ]}
          />

          <Select
            label="Guru Pengampu"
            name="teacher_id"
            required
            options={[
              { value: '', label: '-- Pilih Guru / Pengampu --' },
              ...teachers.map((t) => ({
                value: t.id,
                label: `${t.fullname} (${t.role?.role_name?.replace('_', ' ').toUpperCase() || 'GURU'})`,
              })),
            ]}
          />

          <Input
            label="Mata Pelajaran"
            name="subject"
            placeholder="Contoh: Pemrograman Web"
            required
          />

          <Select
            label="Kelas Rombel"
            name="class_id"
            required
            options={[
              { value: '', label: '-- Pilih Kelas / Rombel --' },
              ...classes.map((c) => ({
                value: c.id,
                label: `${c.class_name}${c.department ? ' [' + c.department.department_code + ']' : ''}`,
              })),
            ]}
          />

          <Select
            label="Ruangan / Lab"
            name="room_id"
            required
            options={[
              { value: '', label: '-- Pilih Ruangan --' },
              ...rooms.map((r) => ({ value: r.id, label: r.room_name })),
            ]}
          />

          <Select
            label="Hari"
            name="day_of_week"
            required
            value={addScheduleDay}
            onChange={(e) => setAddScheduleDay(e.target.value)}
            options={['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'].map((d) => ({
              value: d,
              label: d,
            }))}
          />

          <LessonSlotPicker day={addScheduleDay} />

          <div className="pt-4 flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => setIsAddScheduleOpen(false)}>
              Batal
            </Button>
            <Button type="submit" variant="primary">
              Simpan Jadwal
            </Button>
          </div>
        </form>
      </SlideOver>

      {/* MODAL 2: TAUTKAN ORANG TUA KE SISWA */}
      <Modal
        isOpen={isLinkParentOpen}
        onClose={() => setIsLinkParentOpen(false)}
        title="Tautkan Orang Tua ke Siswa"
      >
        <form method="POST" action="/waka/link-parent" className="space-y-4">
          <input type="hidden" name="_token" value={csrfToken} />
          <input type="hidden" name="student_id" value={selectedStudentForLink?.id || ''} />

          <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <span className="text-slate-500 block">Siswa:</span>
            <strong className="text-slate-900 font-bold">{selectedStudentForLink?.fullname}</strong>
            <span className="text-slate-500 block mt-0.5">
              Kelas: {selectedStudentForLink?.schoolClass?.class_name || selectedStudentForLink?.school_class?.class_name || '-'}
            </span>
          </div>

          <Select
            label="Pilih Akun Orang Tua"
            name="parent_id"
            value={selectedParentId}
            onChange={(e) => setSelectedParentId(e.target.value)}
            options={[
              { value: '', label: '-- Tidak Terhubung / Lepas --' },
              ...parents.map((p) => ({ value: p.id, label: `${p.fullname} (${p.username})` })),
            ]}
          />

          <div className="pt-2 flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => setIsLinkParentOpen(false)}>
              Batal
            </Button>
            <Button type="submit" variant="primary">
              Simpan Penautan
            </Button>
          </div>
        </form>
      </Modal>

      {/* MODAL 3: TAMBAH PENGGUNA BARU */}
      <SlideOver
        isOpen={isAddUserOpen}
        onClose={() => {
          setIsAddUserOpen(false);
          setNewUserRoleId('');
        }}
        title="Tambah Akun Pengguna Baru"
        subtitle="Daftarkan akun Tenaga Pendidik (Guru Mapel, Wali Kelas, BK), Siswa, Ortu, atau Waka"
      >
        <form method="POST" action="/waka/users" className="space-y-4">
          <input type="hidden" name="_token" value={csrfToken} />

          <Select
            label="Pilih Peran Pengguna (Role)"
            name="role_id"
            required
            value={newUserRoleId}
            onChange={(e) => setNewUserRoleId(e.target.value)}
          >
            <option value="">-- Pilih Peran / Sub-Peran --</option>
            <optgroup label="👨‍🏫 Tenaga Pendidik / Guru">
              {roles.find((r) => r.role_name === 'guru') && (
                <option value={roles.find((r) => r.role_name === 'guru')?.id}>
                  Guru Biasa (Pengampu Mapel)
                </option>
              )}
              {roles.find((r) => r.role_name === 'wali_kelas') && (
                <option value={roles.find((r) => r.role_name === 'wali_kelas')?.id}>
                  Guru (Wali Kelas)
                </option>
              )}
              {roles.find((r) => r.role_name === 'guru_bk') && (
                <option value={roles.find((r) => r.role_name === 'guru_bk')?.id}>
                  Guru Bimbingan Konseling (BK)
                </option>
              )}
            </optgroup>
            <optgroup label="👨‍🎓 Peserta Didik & Keluarga">
              {roles.find((r) => r.role_name === 'siswa') && (
                <option value={roles.find((r) => r.role_name === 'siswa')?.id}>
                  Siswa
                </option>
              )}
              {roles.find((r) => r.role_name === 'orang_tua') && (
                <option value={roles.find((r) => r.role_name === 'orang_tua')?.id}>
                  Orang Tua / Wali Murid
                </option>
              )}
            </optgroup>
            <optgroup label="⚙️ Manajemen & Staf">
              {roles.find((r) => r.role_name === 'waka_kurikulum') && (
                <option value={roles.find((r) => r.role_name === 'waka_kurikulum')?.id}>
                  Waka Kurikulum (Administrator)
                </option>
              )}
            </optgroup>
          </Select>

          <Input
            label="Nama Lengkap"
            name="fullname"
            placeholder="Contoh: Muhammad Faiz S.Kom"
            required
          />

          <Input
            label="Username / NIS / NIP"
            name="username"
            placeholder="Contoh: faiz123"
            required
          />

          <Input
            label="Kata Sandi (Password)"
            name="password"
            type="password"
            placeholder="Minimal 4 karakter"
            required
          />

          <Select
            label={
              selectedRoleObj?.role_name === 'wali_kelas'
                ? 'Kelas Rombel Binaan (Wajib untuk Wali Kelas)'
                : selectedRoleObj?.role_name === 'siswa'
                ? 'Kelas Rombel Siswa (Wajib)'
                : 'Kelas Rombel (Khusus Siswa & Wali Kelas)'
            }
            name="class_id"
            required={selectedRoleObj?.role_name === 'wali_kelas' || selectedRoleObj?.role_name === 'siswa'}
            helperText={
              selectedRoleObj?.role_name === 'wali_kelas'
                ? 'Pilih rombel perwalian yang akan dibina oleh Wali Kelas ini.'
                : selectedRoleObj?.role_name === 'siswa'
                ? 'Pilih rombel kelas tempat siswa terdaftar.'
                : 'Hanya diisi jika pengguna adalah Wali Kelas atau Siswa.'
            }
            options={[
              { value: '', label: '-- Pilih Kelas Rombel --' },
              ...classes.map((c) => ({ value: c.id, label: c.class_name })),
            ]}
          />

          {(selectedRoleObj?.role_name === 'siswa' || !selectedRoleObj) && (
            <Select
              label="Orang Tua Terkait (Khusus Siswa)"
              name="parent_id"
              helperText="Tautkan akun orang tua jika sudah terdaftar."
              options={[
                { value: '', label: '-- Tidak Ada / Belum Ditautkan --' },
                ...parents.map((p) => ({ value: p.id, label: `${p.fullname} (${p.username})` })),
              ]}
            />
          )}

          <div className="pt-4 flex justify-end gap-2">
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                setIsAddUserOpen(false);
                setNewUserRoleId('');
              }}
            >
              Batal
            </Button>
            <Button type="submit" variant="primary">
              Simpan Pengguna
            </Button>
          </div>
        </form>
      </SlideOver>

      {/* LIGHTBOX MODAL: PREVIEW FOTO JURNAL */}
      <Modal
        isOpen={Boolean(selectedPhoto)}
        onClose={() => setSelectedPhoto(null)}
        title="Foto Bukti Pembelajaran Realtime"
      >
        {selectedPhoto && (
          <div className="space-y-3">
            <div className="rounded-xl overflow-hidden border border-slate-200">
              <img
                src={selectedPhoto}
                alt="Pratinjau Foto Bukti"
                className="w-full max-h-96 object-contain bg-slate-900"
              />
            </div>
            <div className="flex justify-end">
              <a
                href={selectedPhoto}
                target="_blank"
                rel="noopener noreferrer"
                className="px-3.5 py-1.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition"
              >
                Buka Ukuran Penuh
              </a>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
};
