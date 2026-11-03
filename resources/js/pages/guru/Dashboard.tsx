import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  CalendarDays,
  Clock,
  AlertTriangle,
  NotebookPen,
  CheckCircle2,
  Users,
  MapPin,
  BookOpen,
  Send,
  Plus,
  ShieldAlert,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { StatsCard } from '../../components/layout/StatsCard';
import { SlideOver } from '../../components/ui/SlideOver';
import { Input, Textarea } from '../../components/ui/FormFields';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, Schedule, SystemConfig, User } from '../../types';

interface GuruDashboardProps extends BasePageProps {
  teacher?: User;
  config?: SystemConfig;
  currentDayName?: string;
  today?: string;
  cutoffTime?: string;
  overdueSchedules?: Schedule[];
  mySchedulesCount?: number;
  todaySchedules?: Schedule[];
}

export const GuruDashboard: React.FC<GuruDashboardProps> = ({
  teacher,
  config,
  currentDayName = 'Hari Ini',
  today = '',
  cutoffTime = '',
  overdueSchedules = [],
  mySchedulesCount = 0,
  todaySchedules = [],
  flash,
  errors,
  csrfToken,
}) => {
  const [filterTab, setFilterTab] = useState<'all' | 'pending' | 'completed'>('all');
  const [selectedSchedule, setSelectedSchedule] = useState<Schedule | null>(null);
  const [isSlideOverOpen, setIsSlideOverOpen] = useState(false);

  // Form states inside slide-over
  const [topic, setTopic] = useState('');
  const [teachingActivity, setTeachingActivity] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const completedCount = todaySchedules.filter((s) => s.journals && s.journals.length > 0).length;
  const pendingCount = todaySchedules.length - completedCount;

  const filteredSchedules = todaySchedules.filter((s) => {
    const isCompleted = s.journals && s.journals.length > 0;
    if (filterTab === 'completed') return isCompleted;
    if (filterTab === 'pending') return !isCompleted;
    return true;
  });

  const handleOpenJournalDrawer = (schedule: Schedule) => {
    setSelectedSchedule(schedule);
    const existing = schedule.journals?.[0];
    setTopic(existing?.topic || '');
    setTeachingActivity(existing?.teaching_activity || '');
    setIsSlideOverOpen(true);
  };

  return (
    <div className="space-y-6">
      {/* Flash Alert Messages */}
      <FlashAlert flash={flash} errors={errors} />

      {/* HOMEROOM / BK SUB-ROLE CALLOUT BANNER */}
      {['wali_kelas', 'guru_bk'].includes(teacher?.role?.role_name || '') && (
        <div className="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
              <ShieldAlert className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-xs font-bold text-amber-900">
                {teacher?.role?.role_name === 'wali_kelas'
                  ? 'Peran Tambahan: Wali Kelas'
                  : 'Peran Tambahan: Guru Bimbingan Konseling (BK)'}
              </h4>
              <p className="text-[11px] text-amber-700">
                {teacher?.role?.role_name === 'wali_kelas'
                  ? 'Anda memiliki wewenang memverifikasi surat izin siswa pada kelas perwalian Anda.'
                  : 'Anda memiliki wewenang memantau perizinan dan presensi siswa seluruh sekolah.'}
              </p>
            </div>
          </div>
          <a
            href="/kesiswaan/dashboard"
            className="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-amber-900 bg-amber-200/70 hover:bg-amber-300/80 border border-amber-300 transition shrink-0"
          >
            <span>Buka Panel Perizinan Kesiswaan</span>
          </a>
        </div>
      )}

      {/* OVERDUE SESSIONS BANNER (> 30 Menit Keterlambatan) */}
      {overdueSchedules && overdueSchedules.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
          className="p-4 rounded-xl bg-red-50 border-l-4 border-red-600 border-t border-r border-b border-red-200/80 shadow-xs"
        >
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-start gap-3.5">
              <div className="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                <AlertTriangle className="w-5 h-5 text-red-600" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="text-sm font-bold text-red-950">
                    Peringatan Keterlambatan Pengisian Jurnal
                  </h3>
                  <span className="px-2 py-0.5 text-[11px] font-bold rounded-full bg-red-200 text-red-900 font-mono">
                    {overdueSchedules.length} Sesi Terlambat (&gt;30 Menit)
                  </span>
                </div>
                <div className="mt-1.5 space-y-1 text-xs text-red-800">
                  {overdueSchedules.map((overdue) => (
                    <p key={overdue.id}>
                      Mata pelajaran <strong className="font-bold underline">{overdue.subject}</strong> di kelas{' '}
                      <strong>{overdue.schoolClass?.class_name || overdue.school_class?.class_name}</strong> (berakhir jam{' '}
                      <span className="font-mono font-semibold">{overdue.end_time?.substring(0, 5)}</span>). Harap segera diisi!
                    </p>
                  ))}
                </div>
              </div>
            </div>

            <div className="shrink-0">
              <Button
                variant="destructive"
                size="sm"
                onClick={() => handleOpenJournalDrawer(overdueSchedules[0])}
              >
                <NotebookPen className="w-4 h-4" />
                <span>Buka Form Jurnal Terlambat</span>
              </Button>
            </div>
          </div>
        </motion.div>
      )}

      {/* TOP KPI STAT OVERVIEW */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <StatsCard
          title={`Sesi Mengajar (${currentDayName})`}
          value={todaySchedules.length}
          description={`${completedCount} selesai · ${pendingCount} belum terisi`}
          icon={CalendarDays}
          variant="indigo"
        />
        <StatsCard
          title="Jurnal Belum Terisi"
          value={pendingCount}
          description={overdueSchedules.length > 0 ? `${overdueSchedules.length} melewati batas waktu` : 'Semua sesi terpantau'}
          icon={Clock}
          variant={overdueSchedules.length > 0 ? 'red' : 'amber'}
        />
        <StatsCard
          title="Total Jadwal Guru"
          value={mySchedulesCount}
          description={`Siklus aktif: ${config?.active_cycle || 'Putaran 1'}`}
          icon={BookOpen}
          variant="default"
        />
      </div>

      {/* SECTION HEADER & FILTER SEGMENTED TABS */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
        <div>
          <h2 className="text-base font-bold text-slate-900">Jadwal Mengajar Hari Ini</h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Daftar sesi pembelajaran kelas blok pada hari {currentDayName}, {today}
          </p>
        </div>

        {/* Segmented Filter Tabs with motion.dev sliding pill */}
        <div className="flex items-center gap-1 bg-slate-200/60 p-1 rounded-lg self-start">
          {[
            { id: 'all', label: 'Semua Sesi', count: todaySchedules.length },
            { id: 'pending', label: 'Belum Terisi', count: pendingCount },
            { id: 'completed', label: 'Sudah Terisi', count: completedCount },
          ].map((tab) => {
            const isActive = filterTab === tab.id;
            return (
              <button
                key={tab.id}
                type="button"
                onClick={() => setFilterTab(tab.id as any)}
                className={`relative px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${
                  isActive ? 'text-slate-900 font-bold' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                {isActive && (
                  <motion.div
                    layoutId="guru-filter-tab"
                    className="absolute inset-0 bg-white rounded-md shadow-xs border border-slate-200"
                    transition={{ type: 'spring', stiffness: 450, damping: 35 }}
                  />
                )}
                <span className="relative z-10 flex items-center gap-1.5">
                  <span>{tab.label}</span>
                  <span
                    className={`text-[11px] px-1.5 py-0.2 rounded-full font-mono ${
                      isActive ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200/70 text-slate-600'
                    }`}
                  >
                    {tab.count}
                  </span>
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* SESSION CARDS GRID */}
      {filteredSchedules.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {filteredSchedules.map((schedule) => {
            const isCompleted = schedule.journals && schedule.journals.length > 0;
            const isOverdue = overdueSchedules.some((ov) => ov.id === schedule.id);

            return (
              <motion.div
                key={schedule.id}
                whileHover={{ y: -2 }}
                className={`p-5 rounded-xl bg-white border transition shadow-xs flex flex-col justify-between ${
                  isOverdue
                    ? 'border-red-300 ring-1 ring-red-400/30'
                    : isCompleted
                    ? 'border-emerald-200 bg-emerald-50/10'
                    : 'border-slate-200'
                }`}
              >
                <div>
                  {/* Card Header */}
                  <div className="flex items-center justify-between gap-2 mb-3">
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 font-mono">
                      <Clock className="w-3 h-3 text-slate-500" />
                      <span>
                        {schedule.start_time?.substring(0, 5)} - {schedule.end_time?.substring(0, 5)}
                      </span>
                    </span>

                    {isCompleted ? (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                        <span>Jurnal Terisi</span>
                      </span>
                    ) : isOverdue ? (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 animate-pulse">
                        <AlertTriangle className="w-3.5 h-3.5 text-red-600" />
                        <span>Terlambat</span>
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900">
                        <span>Belum Diisi</span>
                      </span>
                    )}
                  </div>

                  {/* Subject Name */}
                  <h3 className="text-base font-bold text-slate-900 leading-snug">{schedule.subject}</h3>

                  {/* Class & Room Meta */}
                  <div className="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div className="flex items-center gap-1.5 bg-slate-50 p-2 rounded-lg border border-slate-100">
                      <Users className="w-4 h-4 text-slate-400 shrink-0" />
                      <span className="font-semibold text-slate-800 truncate">
                        {schedule.schoolClass?.class_name || schedule.school_class?.class_name || 'Kelas N/A'}
                      </span>
                    </div>
                    <div className="flex items-center gap-1.5 bg-slate-50 p-2 rounded-lg border border-slate-100">
                      <MapPin className="w-4 h-4 text-slate-400 shrink-0" />
                      <span className="font-semibold text-slate-800 truncate">
                        {schedule.room?.room_name || 'Ruangan N/A'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Card CTA Footer */}
                <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                  {isCompleted ? (
                    <a
                      href={`/guru/journals/${schedule.journals![0].id}`}
                      className="w-full inline-flex items-center justify-center gap-2 h-9 px-4 text-xs font-semibold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition"
                    >
                      <BookOpen className="w-3.5 h-3.5 text-emerald-600" />
                      <span>Lihat Rekap Presensi</span>
                    </a>
                  ) : (
                    <Button
                      variant={isOverdue ? 'destructive' : 'primary'}
                      size="sm"
                      className="w-full"
                      onClick={() => handleOpenJournalDrawer(schedule)}
                    >
                      <NotebookPen className="w-4 h-4" />
                      <span>{isOverdue ? 'Isi Jurnal Sekarang (Terlambat)' : 'Isi Jurnal & Presensi Sesi Ini'}</span>
                    </Button>
                  )}
                </div>
              </motion.div>
            );
          })}
        </div>
      ) : (
        <div className="p-12 text-center bg-white rounded-xl border border-slate-200 shadow-xs">
          <CalendarDays className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h4 className="text-sm font-bold text-slate-800">Tidak Ada Sesi Mengajar</h4>
          <p className="text-xs text-slate-500 mt-1">
            Tidak ada jadwal mengajar yang sesuai dengan filter {filterTab} pada siklus {config?.active_cycle}.
          </p>
        </div>
      )}

      {/* QUICK SLIDE-OVER DRAWER FOR FILLING JOURNAL & ATTENDANCE */}
      <SlideOver
        isOpen={isSlideOverOpen}
        onClose={() => setIsSlideOverOpen(false)}
        title="Form Pengisian Jurnal Pembelajaran"
        subtitle={
          selectedSchedule
            ? `${selectedSchedule.subject} · ${selectedSchedule.schoolClass?.class_name || 'Kelas'} (${selectedSchedule.start_time?.substring(0, 5)} - ${selectedSchedule.end_time?.substring(0, 5)})`
            : undefined
        }
        footer={
          <>
            <Button variant="secondary" onClick={() => setIsSlideOverOpen(false)}>
              Batal
            </Button>
            <Button
              variant="primary"
              onClick={() => {
                const form = document.getElementById('quick-journal-form') as HTMLFormElement;
                if (form) form.submit();
              }}
              isLoading={isSubmitting}
            >
              <Send className="w-4 h-4" />
              <span>Simpan Jurnal & Buka Presensi Siswa</span>
            </Button>
          </>
        }
      >
        {selectedSchedule && (
          <form
            id="quick-journal-form"
            method="POST"
            action={`/guru/schedules/${selectedSchedule.id}/journals`}
            onSubmit={() => setIsSubmitting(true)}
            className="space-y-5"
          >
            {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}

            <div className="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200/60 text-xs text-indigo-900 space-y-1">
              <span className="font-bold block">Petunjuk Pengisian Cepat:</span>
              <p>
                Isi topik pembelajaran dan ringkasan aktivitas kelas. Anda dapat langsung membuka form presensi siswa per orang pada langkah berikutnya.
              </p>
            </div>

            <div>
              <Input
                label="Materi Pokok / Kompetensi Dasar (Topik)"
                name="topic"
                required
                value={topic}
                onChange={(e) => setTopic(e.target.value)}
                placeholder="Contoh: Pembuatan REST API dengan Controller & Eloquent"
              />
            </div>

            <div>
              <Textarea
                label="Aktivitas & Uraian Kegiatan Pembelajaran"
                name="teaching_activity"
                required
                rows={4}
                value={teachingActivity}
                onChange={(e) => setTeachingActivity(e.target.value)}
                placeholder="Jelaskan secara ringkas kegiatan belajar mengajar di bengkel / lab / kelas..."
              />
            </div>

            <div>
              <a
                href={`/guru/schedules/${selectedSchedule.id}/journals/create`}
                className="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
              >
                <span>Buka Form Lengkap dengan Checklist Presensi Siswa Detil &rarr;</span>
              </a>
            </div>
          </form>
        )}
      </SlideOver>
    </div>
  );
};
