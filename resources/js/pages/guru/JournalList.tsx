import React from 'react';
import { motion } from 'motion/react';
import {
  BookOpen,
  Calendar,
  Clock,
  CheckCircle2,
  AlertCircle,
  Camera,
  ArrowRight,
  Eye,
  Plus,
  ArrowLeft,
  CalendarDays,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, Schedule, SystemConfig, User } from '../../types';

interface JournalListItem {
  id: number;
  schedule_id: number;
  date: string;
  topic_description?: string;
  photo_path?: string;
  schedule?: Schedule;
  attendances?: Array<{
    id: number;
    status: 'Hadir' | 'Izin' | 'Sakit' | 'Alpha' | 'Alpa';
  }>;
}

interface JournalListProps extends BasePageProps {
  teacher?: User;
  config: SystemConfig;
  today: string;
  currentDayName: string;
  todaySchedules: Schedule[];
  allMySchedules: Schedule[];
  myJournals: {
    data: JournalListItem[];
    current_page: number;
    last_page: number;
    total: number;
  } | JournalListItem[];
}

export const JournalList: React.FC<JournalListProps> = ({
  teacher,
  config,
  today,
  currentDayName = 'Hari Ini',
  todaySchedules = [],
  allMySchedules = [],
  myJournals,
  flash,
}) => {
  const journalsList: JournalListItem[] = Array.isArray(myJournals)
    ? myJournals
    : myJournals?.data || [];

  const otherDaysSchedules = allMySchedules.filter(
    (s) => s.day_of_week !== currentDayName
  );

  return (
    <div className="space-y-6">
      <FlashAlert flash={flash} />

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
              Jurnal Mengajar & Presensi
            </span>
            <span className="text-xs text-slate-400">Teaching Factory System</span>
          </div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
            <BookOpen className="w-6 h-6 text-indigo-600" />
            Jurnal Mengajar &amp; Presensi Realtime
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            Buka kelas realtime, unggah foto bukti kegiatan pembelajaran, dan sinkronisasi presensi otomatis siswa.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <a
            href="/guru/dashboard"
            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Dashboard</span>
          </a>
          <a
            href="/guru/schedules"
            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition"
          >
            <CalendarDays className="w-3.5 h-3.5" />
            <span>Jadwal Mengajar</span>
          </a>
        </div>
      </div>

      {/* Hero Banner: Today's Cycle & Schedule Status */}
      <div className="bg-slate-900 text-white p-5 rounded-xl border border-slate-800 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
              Siklus Aktif: {config?.active_cycle}
            </span>
            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
              Pembelajaran Berjalan
            </span>
          </div>
          <h3 className="text-xl font-black mt-2">
            Hari {currentDayName}, {today}
          </h3>
          <p className="text-xs text-slate-400 mt-0.5">
            Guru Pengampu: <strong className="text-white">{teacher?.fullname || 'Guru'}</strong> ({teacher?.username})
          </p>
        </div>

        <div className="flex items-center gap-4 bg-white/5 border border-white/10 px-4 py-3 rounded-xl">
          <div className="text-center">
            <span className="text-xs text-slate-400 block">Jadwal Hari Ini</span>
            <span className="text-xl font-black text-white">{todaySchedules.length} Sesi</span>
          </div>
          <div className="h-8 w-px bg-white/10"></div>
          <div className="text-center">
            <span className="text-xs text-slate-400 block">Total Jadwal Siklus</span>
            <span className="text-xl font-black text-white">{allMySchedules.length} Sesi</span>
          </div>
        </div>
      </div>

      {/* SECTION 1: Jadwal Mengajar Hari Ini */}
      <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <Clock className="w-4 h-4 text-indigo-600" />
              Jadwal Mengajar Hari Ini ({currentDayName})
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Pilih jadwal di bawah untuk membuka sesi kelas dan mengisi jurnal mengajar.
            </p>
          </div>
          <span className="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-mono">
            {todaySchedules.length} Sesi Terjadwal
          </span>
        </div>

        {todaySchedules.length === 0 ? (
          <div className="text-center py-10 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
            <Calendar className="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <h4 className="text-sm font-bold text-slate-800">Tidak Ada Jadwal Mengajar Hari Ini</h4>
            <p className="text-xs text-slate-500 mt-1 max-w-md mx-auto">
              Anda tidak memiliki jadwal mengajar pada hari {currentDayName} di {config?.active_cycle}.
            </p>
            <a
              href="/guru/schedules"
              className="inline-flex items-center gap-1.5 mt-3 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
            >
              <Plus className="w-3.5 h-3.5" /> Buka Manajemen Jadwal
            </a>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {todaySchedules.map((sch) => {
              const journalToday = sch.journals && sch.journals.length > 0 ? sch.journals[0] : null;
              const isFilled = Boolean(journalToday);

              return (
                <div
                  key={sch.id}
                  className={`rounded-xl border ${
                    isFilled
                      ? 'border-emerald-200 bg-emerald-50/20'
                      : 'border-slate-200 bg-white hover:border-indigo-300 hover:shadow-xs'
                  } p-5 transition flex flex-col justify-between`}
                >
                  <div>
                    <div className="flex items-start justify-between gap-2 mb-2">
                      <span
                        className={`text-xs font-bold px-2.5 py-0.5 rounded-full ${
                          isFilled
                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                            : 'bg-amber-100 text-amber-800 border border-amber-200'
                        }`}
                      >
                        {isFilled ? '✅ Jurnal Terisi' : '⏳ Belum Diisi'}
                      </span>
                      <span className="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                        {sch.start_time?.substring(0, 5)} - {sch.end_time?.substring(0, 5)}
                      </span>
                    </div>

                    <h4 className="font-bold text-sm text-slate-900 mt-2">{sch.subject}</h4>

                    <div className="mt-3 space-y-1 text-xs text-slate-600">
                      <div>
                        Kelas:{' '}
                        <strong className="text-slate-800 font-semibold">
                          {sch.schoolClass?.class_name || sch.school_class?.class_name || '-'}
                        </strong>
                      </div>
                      <div>
                        Ruangan:{' '}
                        <strong className="text-slate-800 font-semibold">
                          {sch.room?.room_name || '-'}
                        </strong>
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 pt-3 border-t border-slate-100">
                    {isFilled && journalToday ? (
                      <a
                        href={`/guru/journals/${journalToday.id}`}
                        className="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
                      >
                        <Eye className="w-3.5 h-3.5" />
                        <span>Lihat Rekap Jurnal</span>
                      </a>
                    ) : (
                      <a
                        href={`/guru/schedules/${sch.id}/journals/create`}
                        className="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
                      >
                        <BookOpen className="w-3.5 h-3.5" />
                        <span>Buka Kelas &amp; Isi Jurnal</span>
                      </a>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* SECTION 2: Riwayat Jurnal Mengajar */}
      <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <BookOpen className="w-4 h-4 text-indigo-600" />
              Riwayat Jurnal Mengajar &amp; Presensi
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Daftar seluruh rekaman pembelajaran yang telah Anda selesaikan sebelumnya.
            </p>
          </div>
        </div>

        {journalsList.length === 0 ? (
          <div className="text-center py-10 px-4 text-slate-400 text-xs">
            Belum ada riwayat jurnal mengajar yang tersimpan.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-600">
              <thead className="bg-slate-50 text-slate-700 uppercase font-semibold text-[11px] border-y border-slate-200">
                <tr>
                  <th className="py-3 px-4">Tanggal &amp; Waktu</th>
                  <th className="py-3 px-4">Kelas &amp; Ruangan</th>
                  <th className="py-3 px-4">Mata Pelajaran</th>
                  <th className="py-3 px-4">Ringkasan Topik Materi</th>
                  <th className="py-3 px-4 text-center">Rekap Kehadiran</th>
                  <th className="py-3 px-4 text-center">Foto Bukti</th>
                  <th className="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 font-medium">
                {journalsList.map((jnl) => {
                  const attHadir = jnl.attendances?.filter((a) => a.status === 'Hadir').length || 0;
                  const attIzin = jnl.attendances?.filter((a) => a.status === 'Izin').length || 0;
                  const attSakit = jnl.attendances?.filter((a) => a.status === 'Sakit').length || 0;
                  const attAlpha = jnl.attendances?.filter((a) => a.status === 'Alpha' || a.status === 'Alpa').length || 0;

                  return (
                    <tr key={jnl.id} className="hover:bg-slate-50/70 transition">
                      <td className="py-3 px-4 whitespace-nowrap">
                        <span className="font-bold text-slate-900 block">{jnl.date}</span>
                        <span className="text-[11px] text-slate-500 font-mono">
                          {jnl.schedule?.start_time?.substring(0, 5)} - {jnl.schedule?.end_time?.substring(0, 5)}
                        </span>
                      </td>
                      <td className="py-3 px-4 whitespace-nowrap">
                        <span className="font-semibold text-slate-800 block">
                          {jnl.schedule?.schoolClass?.class_name || jnl.schedule?.school_class?.class_name || '-'}
                        </span>
                        <span className="text-[11px] text-slate-500">
                          {jnl.schedule?.room?.room_name || '-'}
                        </span>
                      </td>
                      <td className="py-3 px-4 whitespace-nowrap font-bold text-slate-900">
                        {jnl.schedule?.subject || '-'}
                      </td>
                      <td className="py-3 px-4 max-w-xs truncate text-slate-700">
                        {jnl.topic_description || '-'}
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
                          <a
                            href={`/storage/${jnl.photo_path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-block"
                          >
                            <img
                              src={`/storage/${jnl.photo_path}`}
                              alt="Bukti Foto"
                              className="w-9 h-9 object-cover rounded-lg border border-slate-200 hover:scale-105 transition"
                            />
                          </a>
                        ) : (
                          <span className="text-slate-400 text-xs">-</span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center whitespace-nowrap">
                        <a
                          href={`/guru/journals/${jnl.id}`}
                          className="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg transition text-xs"
                        >
                          Detail <ArrowRight className="w-3 h-3" />
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
    </div>
  );
};
