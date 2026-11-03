import React from 'react';
import { motion } from 'motion/react';
import {
  BookOpen,
  Calendar,
  Clock,
  Printer,
  ArrowLeft,
  Camera,
  CheckCircle2,
  Users,
  MapPin,
  Maximize2,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, Schedule, User } from '../../types';

interface AttendanceRecord {
  id: number;
  student_id: number;
  status: 'Hadir' | 'Izin' | 'Sakit' | 'Alpha' | 'Alpa';
  notes?: string | null;
  student?: User;
}

interface JournalDetailData {
  id: number;
  schedule_id: number;
  date: string;
  topic_description: string;
  photo_path?: string | null;
  schedule: Schedule;
  attendances?: AttendanceRecord[];
}

interface JournalDetailProps extends BasePageProps {
  journal: JournalDetailData;
}

export const JournalDetail: React.FC<JournalDetailProps> = ({
  journal,
  flash,
}) => {
  const attendances = journal?.attendances || [];
  const hadirCount = attendances.filter((a) => a.status === 'Hadir').length;
  const izinCount = attendances.filter((a) => a.status === 'Izin').length;
  const sakitCount = attendances.filter((a) => a.status === 'Sakit').length;
  const alphaCount = attendances.filter((a) => a.status === 'Alpha' || a.status === 'Alpa').length;

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      <FlashAlert flash={flash} />

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
          <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="/guru/journals" className="hover:underline">
              Jurnal Mengajar
            </a>
            <span>/</span>
            <span className="text-slate-800 font-bold">Detail Jurnal #{journal.id}</span>
          </div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <BookOpen className="w-6 h-6 text-indigo-600" />
            Detail Jurnal Pembelajaran &amp; Rekap Presensi
          </h1>
        </div>

        <div className="flex items-center gap-2">
          <a
            href="/guru/journals"
            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Kembali ke Sesi</span>
          </a>
          <button
            onClick={() => window.print()}
            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>Cetak Berita Acara</span>
          </button>
        </div>
      </div>

      {/* Session Header Card */}
      <div className="bg-slate-900 text-white p-5 rounded-xl border border-slate-800 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
              {journal.schedule?.cycle_type}
            </span>
            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1">
              <CheckCircle2 className="w-3 h-3 text-emerald-400" /> Selesai Diisi
            </span>
          </div>
          <h2 className="text-xl font-black mt-2 text-white">{journal.schedule?.subject}</h2>
          <p className="text-xs text-slate-400 mt-0.5">
            Guru Pengampu:{' '}
            <strong className="text-white">
              {journal.schedule?.teacher?.fullname || 'Guru'}
            </strong>
          </p>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs bg-white/5 border border-white/10 p-3.5 rounded-xl">
          <div>
            <span className="text-slate-400 block mb-0.5">Tanggal:</span>
            <strong className="text-white">{journal.date}</strong>
          </div>
          <div>
            <span className="text-slate-400 block mb-0.5">Kelas:</span>
            <strong className="text-white">
              {journal.schedule?.schoolClass?.class_name || journal.schedule?.school_class?.class_name || '-'}
            </strong>
          </div>
          <div>
            <span className="text-slate-400 block mb-0.5">Ruangan:</span>
            <strong className="text-white">{journal.schedule?.room?.room_name || '-'}</strong>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Photo Proof & Material */}
        <div className="space-y-6">
          {/* Photo */}
          <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-3">
            <h4 className="font-semibold text-xs uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <Camera className="w-4 h-4 text-indigo-600" />
              Foto Bukti Kegiatan Kelas
            </h4>
            {journal.photo_path ? (
              <div className="rounded-xl overflow-hidden border border-slate-200 relative group">
                <img
                  src={`/storage/${journal.photo_path}`}
                  alt="Bukti Dokumentasi Pembelajaran"
                  className="w-full h-52 object-cover group-hover:scale-105 transition duration-300"
                />
                <a
                  href={`/storage/${journal.photo_path}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="absolute bottom-2 right-2 px-2.5 py-1 bg-slate-900/80 hover:bg-slate-900 text-white text-[11px] font-bold rounded-lg backdrop-blur-xs transition flex items-center gap-1"
                >
                  <Maximize2 className="w-3 h-3" /> Perbesar
                </a>
              </div>
            ) : (
              <div className="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-400 text-xs">
                Tidak ada foto kegiatan yang terlampir.
              </div>
            )}
          </div>

          {/* Topic */}
          <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-2">
            <h4 className="font-semibold text-xs uppercase tracking-wider text-slate-700">
              Materi &amp; Topik Pembelajaran
            </h4>
            <p className="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap bg-slate-50 p-3.5 rounded-xl border border-slate-200/60 font-medium">
              {journal.topic_description}
            </p>
          </div>
        </div>

        {/* Right Column: Attendance Recap & Roster */}
        <div className="lg:col-span-2 space-y-6">
          {/* Mini Stat Cards */}
          <div className="grid grid-cols-4 gap-3">
            <div className="bg-emerald-50 border border-emerald-200/80 p-3 rounded-lg text-center">
              <span className="text-[11px] font-semibold text-emerald-800 uppercase block">Hadir</span>
              <strong className="text-lg font-bold text-emerald-900 font-mono">{hadirCount}</strong>
            </div>
            <div className="bg-blue-50 border border-blue-200/80 p-3 rounded-lg text-center">
              <span className="text-[11px] font-semibold text-blue-800 uppercase block">Izin</span>
              <strong className="text-lg font-bold text-blue-900 font-mono">{izinCount}</strong>
            </div>
            <div className="bg-amber-50 border border-amber-200/80 p-3 rounded-lg text-center">
              <span className="text-[11px] font-semibold text-amber-800 uppercase block">Sakit</span>
              <strong className="text-lg font-bold text-amber-900 font-mono">{sakitCount}</strong>
            </div>
            <div className="bg-red-50 border border-red-200/80 p-3 rounded-lg text-center">
              <span className="text-[11px] font-semibold text-red-800 uppercase block">Alpha</span>
              <strong className="text-lg font-bold text-red-900 font-mono">{alphaCount}</strong>
            </div>
          </div>

          {/* Attendance Table */}
          <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div className="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
              <h4 className="font-bold text-slate-900 text-xs uppercase tracking-wider">
                Daftar Presensi Peserta Didik
              </h4>
              <span className="text-xs text-slate-500 font-mono">
                Total: {attendances.length} Siswa
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-600 uppercase font-bold text-[11px] border-b border-slate-200">
                  <tr>
                    <th className="py-2.5 px-3 w-12 text-center">No</th>
                    <th className="py-2.5 px-4">Nama Siswa</th>
                    <th className="py-2.5 px-4">NIS</th>
                    <th className="py-2.5 px-4 text-center">Status Presensi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {attendances.map((att, idx) => (
                    <tr key={att.id || idx} className="hover:bg-slate-50/70 transition">
                      <td className="py-2.5 px-3 text-center text-slate-400 font-bold">{idx + 1}</td>
                      <td className="py-2.5 px-4 font-bold text-slate-900">
                        {att.student?.fullname || '-'}
                      </td>
                      <td className="py-2.5 px-4 font-mono text-slate-600">
                        {att.student?.username || '-'}
                      </td>
                      <td className="py-2.5 px-4 text-center">
                        <span
                          className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                            att.status === 'Hadir'
                              ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                              : att.status === 'Izin'
                              ? 'bg-blue-100 text-blue-800 border border-blue-200'
                              : att.status === 'Sakit'
                              ? 'bg-amber-100 text-amber-800 border border-amber-200'
                              : 'bg-red-100 text-red-800 border border-red-200'
                          }`}
                        >
                          {att.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
