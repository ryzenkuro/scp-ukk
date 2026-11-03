import React, { useState, useEffect, useMemo } from 'react';
import { motion } from 'motion/react';
import {
  CalendarDays,
  Clock,
  Plus,
  Trash2,
  Lock,
  Unlock,
  AlertTriangle,
  Building,
  GraduationCap,
  BookOpen,
  ArrowLeft,
  Search,
  Filter,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input, Select } from '../../components/ui/FormFields';
import { FlashAlert } from '../../components/ui/Toast';
import { SlideOver } from '../../components/ui/SlideOver';
import { LessonSlotPicker } from '../../components/schedule/LessonSlotPicker';
import { BasePageProps, Schedule, SystemConfig, SchoolClass, Room } from '../../types';

interface ScheduleListProps extends BasePageProps {
  config: SystemConfig;
  selectedCycle: 'Putaran 1' | 'Putaran 2';
  classes: SchoolClass[];
  rooms: Room[];
  days: string[];
  mySchedules: Schedule[];
  allSchedules: Schedule[];
}

export const ScheduleList: React.FC<ScheduleListProps> = ({
  config,
  selectedCycle = 'Putaran 1',
  classes = [],
  rooms = [],
  days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
  mySchedules = [],
  allSchedules = [],
  flash,
  errors,
  csrfToken,
}) => {
  const [isAddScheduleOpen, setIsAddScheduleOpen] = useState(false);
  const [subject, setSubject] = useState('');
  const [classId, setClassId] = useState('');
  const [roomId, setRoomId] = useState('');
  const [dayOfWeek, setDayOfWeek] = useState(days[0] || 'Senin');
  const [startTime, setStartTime] = useState('08:00');
  const [endTime, setEndTime] = useState('10:00');

  // Search & Day Filter for My Schedules
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedDayFilter, setSelectedDayFilter] = useState('All');

  // Search & Day Filter for School-wide Schedules
  const [allSearchQuery, setAllSearchQuery] = useState('');
  const [allDayFilter, setAllDayFilter] = useState('All');

  const conflictError = errors?.conflict?.[0];
  const lockError = errors?.locked?.[0];

  // Auto-open SlideOver if there are field validation errors
  useEffect(() => {
    if (errors && Object.keys(errors).length > 0) {
      const hasFieldErrors = Boolean(
        errors.subject ||
        errors.class_id ||
        errors.room_id ||
        errors.day_of_week ||
        errors.start_time ||
        errors.end_time
      );
      if (hasFieldErrors) {
        setIsAddScheduleOpen(true);
      }
    }
  }, [errors]);

  // Filter My Schedules
  const filteredMySchedules = useMemo(() => {
    return mySchedules.filter((s) => {
      if (selectedDayFilter !== 'All' && s.day_of_week !== selectedDayFilter) {
        return false;
      }
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const subjectMatch = s.subject?.toLowerCase().includes(q);
        const className = (s.schoolClass?.class_name || s.school_class?.class_name || '').toLowerCase();
        const roomName = (s.room?.room_name || '').toLowerCase();
        return subjectMatch || className.includes(q) || roomName.includes(q);
      }
      return true;
    });
  }, [mySchedules, selectedDayFilter, searchQuery]);

  // Filter School-wide Schedules
  const filteredAllSchedules = useMemo(() => {
    return allSchedules.filter((s) => {
      if (allDayFilter !== 'All' && s.day_of_week !== allDayFilter) {
        return false;
      }
      if (allSearchQuery.trim()) {
        const q = allSearchQuery.toLowerCase();
        const teacherMatch = (s.teacher?.fullname || '').toLowerCase().includes(q);
        const subjectMatch = s.subject?.toLowerCase().includes(q);
        const className = (s.schoolClass?.class_name || s.school_class?.class_name || '').toLowerCase();
        const roomName = (s.room?.room_name || '').toLowerCase();
        return teacherMatch || subjectMatch || className.includes(q) || roomName.includes(q);
      }
      return true;
    });
  }, [allSchedules, allDayFilter, allSearchQuery]);

  return (
    <div className="space-y-6">
      <FlashAlert flash={flash} />

      {/* Conflict / Locked Alert Banners */}
      {conflictError && (
        <motion.div
          initial={{ opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
          className="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3"
        >
          <AlertTriangle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div>
            <h4 className="text-xs font-bold text-red-900">Validasi Bentrok Jadwal (Conflict Prevention)</h4>
            <p className="text-xs text-red-700 mt-0.5">{conflictError}</p>
          </div>
        </motion.div>
      )}

      {lockError && (
        <motion.div
          initial={{ opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
          className="p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3"
        >
          <Lock className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <h4 className="text-xs font-bold text-amber-900">Jadwal Sedang Dikunci</h4>
            <p className="text-xs text-amber-700 mt-0.5">{lockError}</p>
          </div>
        </motion.div>
      )}

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
              Jadwal Mengajar Mandiri
            </span>
            <span className="text-xs text-slate-400">Teaching Factory System</span>
          </div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
            <CalendarDays className="w-6 h-6 text-indigo-600" />
            Pengaturan Jadwal Pembelajaran Blok
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            Susun jadwal tatap muka Anda pada Putaran 1 &amp; Putaran 2 dengan validasi anti-bentrok 3D.
          </p>
        </div>

        <a
          href="/guru/dashboard"
          className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg border border-slate-200 transition"
        >
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Kembali ke Dashboard</span>
        </a>
      </div>

      {/* Master Toolbar (Waka Standard: Cycle Switcher, Filters, Status & Action CTA) */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-2.5">
          {/* Cycle Selector Pills */}
          <div className="inline-flex rounded-lg border border-slate-200 p-1 bg-slate-50 gap-1 text-xs">
            {(['Putaran 1', 'Putaran 2'] as const).map((cycle) => (
              <a
                key={cycle}
                href={`/guru/schedules?cycle=${encodeURIComponent(cycle)}`}
                className={`px-3 py-1.5 font-bold rounded-md transition ${
                  selectedCycle === cycle
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                {cycle} {config?.active_cycle === cycle && '(Aktif)'}
              </a>
            ))}
          </div>

          {/* Filter Hari */}
          <select
            value={selectedDayFilter}
            onChange={(e) => setSelectedDayFilter(e.target.value)}
            className="h-9 text-xs rounded-lg border-slate-200 bg-white text-slate-700 font-medium focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="All">Semua Hari</option>
            {days.map((d) => (
              <option key={d} value={d}>
                {d}
              </option>
            ))}
          </select>

          {/* Status Lock Pill */}
          {config?.is_schedule_locked ? (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
              <Lock className="w-3.5 h-3.5" /> Terkunci Waka (Read Only)
            </span>
          ) : (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <Unlock className="w-3.5 h-3.5" /> Pengisian Mandiri Aktif
            </span>
          )}
        </div>

        <div className="flex items-center gap-2.5">
          {/* Search bar */}
          <div className="relative flex-1 sm:w-56">
            <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
            <input
              type="text"
              placeholder="Cari mapel, kelas, lab..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 text-xs pl-8 pr-3 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 placeholder:text-slate-400"
            />
          </div>

          {/* Action CTA: Tambah Jadwal Button */}
          {!config?.is_schedule_locked ? (
            <Button
              type="button"
              variant="primary"
              icon={Plus}
              onClick={() => setIsAddScheduleOpen(true)}
              className="shrink-0"
            >
              Tambah Jadwal
            </Button>
          ) : (
            <Button
              type="button"
              variant="secondary"
              icon={Lock}
              disabled
              className="shrink-0 opacity-60 cursor-not-allowed"
            >
              Jadwal Dikunci
            </Button>
          )}
        </div>
      </div>

      {/* SlideOver Drawer: Form Tambah Jadwal Mengajar Mandiri (Waka Standard) */}
      <SlideOver
        isOpen={isAddScheduleOpen}
        onClose={() => setIsAddScheduleOpen(false)}
        title="Tambah Jadwal Mengajar Mandiri"
        subtitle={`Target Siklus: ${selectedCycle} (Siklus Pembelajaran Aktif)`}
        width="max-w-lg"
      >
        <form
          method="POST"
          action="/guru/schedules"
          className="space-y-4"
        >
          <input type="hidden" name="_token" value={csrfToken} />
          <input type="hidden" name="cycle_type" value={selectedCycle} />

          <Input
            label="Mata Pelajaran"
            name="subject"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            placeholder="Contoh: Pemrograman Web & Perangkat Bergerak"
            required
            error={errors?.subject?.[0]}
          />

          <Select
            label="Kelas Rombel"
            name="class_id"
            value={classId}
            onChange={(e) => setClassId(e.target.value)}
            required
            options={[
              { value: '', label: '-- Pilih Kelas --' },
              ...classes.map((c) => ({ value: c.id, label: c.class_name })),
            ]}
            error={errors?.class_id?.[0]}
          />

          <Select
            label="Ruangan / Lab"
            name="room_id"
            value={roomId}
            onChange={(e) => setRoomId(e.target.value)}
            required
            options={[
              { value: '', label: '-- Pilih Ruangan --' },
              ...rooms.map((r) => ({ value: r.id, label: r.room_name })),
            ]}
            error={errors?.room_id?.[0]}
          />

          <Select
            label="Hari Mengajar"
            name="day_of_week"
            value={dayOfWeek}
            onChange={(e) => setDayOfWeek(e.target.value)}
            required
            options={days.map((d) => ({ value: d, label: d }))}
            error={errors?.day_of_week?.[0]}
          />

          <LessonSlotPicker
            day={dayOfWeek}
            initialStart={startTime}
            initialEnd={endTime}
            onChangeTime={(s, e) => {
              setStartTime(s);
              setEndTime(e);
            }}
          />

          <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <Button
              type="button"
              variant="secondary"
              onClick={() => setIsAddScheduleOpen(false)}
            >
              Batal
            </Button>
            <Button
              type="submit"
              variant="primary"
              icon={Plus}
            >
              Simpan Jadwal
            </Button>
          </div>
        </form>
      </SlideOver>

      {/* My Schedules Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h4 className="font-bold text-slate-900 text-sm">
              Jadwal Mengajar Saya ({selectedCycle})
            </h4>
            <p className="text-xs text-slate-500">
              Daftar kelas yang Anda ampu pada siklus ini
              {selectedDayFilter !== 'All' && ` · Hari ${selectedDayFilter}`}
              {searchQuery && ` · Hasil pencarian "${searchQuery}"`}
            </p>
          </div>
          <span className="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full font-mono">
            {filteredMySchedules.length} Sesi
          </span>
        </div>

        {filteredMySchedules.length === 0 ? (
          <div className="p-12 text-center">
            <div className="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
              <CalendarDays className="w-6 h-6" />
            </div>
            <h5 className="text-sm font-bold text-slate-800">
              Belum ada jadwal mengajar pada {selectedCycle}
            </h5>
            <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
              {selectedDayFilter !== 'All' || searchQuery
                ? 'Tidak ada jadwal yang cocok dengan filter atau kata kunci pencarian.'
                : 'Susun jadwal tatap muka Anda sekarang untuk memulai presensi dan jurnal harian.'}
            </p>
            {!config?.is_schedule_locked && selectedDayFilter === 'All' && !searchQuery && (
              <div className="mt-4">
                <Button
                  type="button"
                  variant="primary"
                  icon={Plus}
                  onClick={() => setIsAddScheduleOpen(true)}
                >
                  Tambah Jadwal Sekarang
                </Button>
              </div>
            )}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-100 text-xs">
              <thead className="bg-slate-50 text-slate-600 uppercase font-semibold text-[11px] border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3 text-left">Hari</th>
                  <th className="px-6 py-3 text-left">Waktu Sesi</th>
                  <th className="px-6 py-3 text-left">Mata Pelajaran</th>
                  <th className="px-6 py-3 text-left">Kelas Rombel</th>
                  <th className="px-6 py-3 text-left">Ruangan</th>
                  <th className="px-6 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 bg-white">
                {filteredMySchedules.map((s) => (
                  <tr key={s.id} className="hover:bg-slate-50/70 transition">
                    <td className="px-6 py-3.5 font-bold text-indigo-700">{s.day_of_week}</td>
                    <td className="px-6 py-3.5 font-mono text-slate-700 font-medium">
                      {s.start_time?.substring(0, 5)} - {s.end_time?.substring(0, 5)}
                    </td>
                    <td className="px-6 py-3.5 font-bold text-slate-900">{s.subject}</td>
                    <td className="px-6 py-3.5">
                      <span className="px-2.5 py-0.5 rounded font-bold text-[11px] bg-blue-50 text-blue-700 border border-blue-200">
                        {s.schoolClass?.class_name || s.school_class?.class_name || '-'}
                      </span>
                    </td>
                    <td className="px-6 py-3.5 text-slate-600">{s.room?.room_name || '-'}</td>
                    <td className="px-6 py-3.5 text-right">
                      {!config?.is_schedule_locked ? (
                        <form
                          method="POST"
                          action={`/guru/schedules/${s.id}`}
                          onSubmit={(e) => {
                            if (!window.confirm(`Hapus jadwal ${s.subject} (${s.day_of_week})?`)) {
                              e.preventDefault();
                            }
                          }}
                          className="inline"
                        >
                          <input type="hidden" name="_token" value={csrfToken} />
                          <input type="hidden" name="_method" value="DELETE" />
                          <Button
                            type="submit"
                            variant="ghost"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 p-1.5 rounded-lg"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </form>
                      ) : (
                        <span className="text-slate-400 italic text-xs">Terkunci</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* School-wide Schedules Monitoring */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div>
            <h4 className="font-bold text-slate-900 text-sm">
              Jadwal Keseluruhan Sekolah ({selectedCycle})
            </h4>
            <p className="text-xs text-slate-500">
              Lihat jadwal guru lain untuk referensi pemilihan ruangan dan jam kosong
            </p>
          </div>
          <div className="flex items-center gap-2">
            <select
              value={allDayFilter}
              onChange={(e) => setAllDayFilter(e.target.value)}
              className="h-8 text-xs rounded-lg border-slate-200 bg-white text-slate-700 font-medium focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="All">Semua Hari</option>
              {days.map((d) => (
                <option key={d} value={d}>
                  {d}
                </option>
              ))}
            </select>
            <div className="relative w-44">
              <Search className="w-3 h-3 text-slate-400 absolute left-2.5 top-2.5" />
              <input
                type="text"
                placeholder="Cari guru, mapel..."
                value={allSearchQuery}
                onChange={(e) => setAllSearchQuery(e.target.value)}
                className="w-full h-8 text-xs pl-7 pr-2.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 placeholder:text-slate-400"
              />
            </div>
            <span className="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full font-mono shrink-0">
              {filteredAllSchedules.length} Sesi
            </span>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-100 text-xs">
            <thead className="bg-slate-50 text-slate-600 uppercase font-semibold text-[11px] border-b border-slate-200">
              <tr>
                <th className="px-4 py-2.5 text-left">Hari</th>
                <th className="px-4 py-2.5 text-left">Jam Sesi</th>
                <th className="px-4 py-2.5 text-left">Guru Pengampu</th>
                <th className="px-4 py-2.5 text-left">Mata Pelajaran</th>
                <th className="px-4 py-2.5 text-left">Kelas</th>
                <th className="px-4 py-2.5 text-left">Ruangan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {filteredAllSchedules.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-slate-400 text-xs">
                    Tidak ada jadwal yang cocok dengan filter atau pencarian.
                  </td>
                </tr>
              ) : (
                filteredAllSchedules.map((as) => (
                  <tr key={as.id} className="hover:bg-slate-50/70 transition">
                    <td className="px-4 py-2.5 font-semibold text-slate-800">{as.day_of_week}</td>
                    <td className="px-4 py-2.5 font-mono text-slate-600">
                      {as.start_time?.substring(0, 5)} - {as.end_time?.substring(0, 5)}
                    </td>
                    <td className="px-4 py-2.5 font-medium text-slate-900">{as.teacher?.fullname || '-'}</td>
                    <td className="px-4 py-2.5 text-slate-700">{as.subject}</td>
                    <td className="px-4 py-2.5">
                      <span className="px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {as.schoolClass?.class_name || as.school_class?.class_name || '-'}
                      </span>
                    </td>
                    <td className="px-4 py-2.5 text-slate-600">{as.room?.room_name || '-'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
