import React, { useState } from 'react';
import { motion } from 'motion/react';
import {
  BarChart3,
  Printer,
  Filter,
  Users,
  CheckCircle2,
  Calendar,
  AlertCircle,
  FileSpreadsheet,
  Download,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { StatsCard } from '../../components/layout/StatsCard';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, SchoolClass, User } from '../../types';

interface AttendanceSummary {
  total_students: number;
  average_percentage: number;
  total_h: number;
  total_s: number;
  total_i: number;
  total_a: number;
  total_records: number;
}

interface ReportRow {
  student: User;
  h: number;
  s: number;
  i: number;
  a: number;
  total: number;
  percentage: number;
}

interface AttendanceRecapProps extends BasePageProps {
  classes: SchoolClass[];
  selectedClassId?: number | string;
  selectedClass?: SchoolClass | null;
  startDate?: string;
  endDate?: string;
  summary?: AttendanceSummary;
  reportData?: ReportRow[];
}

export const AttendanceRecap: React.FC<AttendanceRecapProps> = ({
  classes = [],
  selectedClassId = '',
  selectedClass,
  startDate = '',
  endDate = '',
  summary = {
    total_students: 0,
    average_percentage: 0,
    total_h: 0,
    total_s: 0,
    total_i: 0,
    total_a: 0,
    total_records: 0,
  },
  reportData = [],
  flash,
  authUser,
}) => {
  const [classId, setClassId] = useState(String(selectedClassId || ''));
  const [start, setStart] = useState(startDate);
  const [end, setEnd] = useState(endDate);

  const isWaliKelas = authUser?.role?.role_name === 'wali_kelas';
  const isGuruBk = authUser?.role?.role_name === 'guru_bk';

  const printUrl = `/reports/attendance/print?class_id=${classId || selectedClassId}&start_date=${start || startDate}&end_date=${end || endDate}`;

  return (
    <div className="space-y-6">
      {/* Flash Notice */}
      <FlashAlert flash={flash} />

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/70 font-mono">
              Laporan Presensi
            </span>
            {isWaliKelas && (
              <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                🔒 Mode Wali Kelas (Khusus Kelas Sendiri)
              </span>
            )}
            {isGuruBk && (
              <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200/70">
                🌐 Mode Guru BK (Seluruh Rombel Sekolah)
              </span>
            )}
            <span className="text-xs text-slate-400">Teaching Factory System</span>
          </div>
          <h1 className="text-xl font-bold text-slate-900 tracking-tight mt-1.5 flex items-center gap-2">
            <BarChart3 className="w-5 h-5 text-indigo-600" />
            Rekapitulasi Presensi Siswa Rombel
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            {isWaliKelas
              ? `Laporan persentase kehadiran dan absensi resmi khusus untuk kelas perwalian Anda (${selectedClass?.class_name || '-'}).`
              : 'Laporan persentase kehadiran kurikulum, agregasi ketidakhadiran (H/S/I/A), dan cetak dokumen resmi A4.'}
          </p>
        </div>
      </div>

      {/* Filter Toolbar */}
      <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="/reports/attendance" className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
              <span>Pilih Rombongan Belajar</span>
              {isWaliKelas && <span className="text-[10px] text-amber-700 font-semibold">Terkunci (Kelas Binaan)</span>}
            </label>
            {isWaliKelas ? (
              <div>
                <input type="hidden" name="class_id" value={selectedClassId} />
                <div className="w-full h-9 text-xs font-semibold px-3 bg-slate-100 border border-slate-200 rounded-lg text-slate-700 flex items-center gap-2">
                  <span>🏫</span>
                  <span>{selectedClass?.class_name || classes[0]?.class_name || 'Kelas Perwalian'}</span>
                </div>
              </div>
            ) : (
              <select
                name="class_id"
                value={classId}
                onChange={(e) => setClassId(e.target.value)}
                className="w-full h-9 text-xs rounded-lg border-slate-200 bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 text-slate-900"
              >
                {classes.map((cls) => (
                  <option key={cls.id} value={cls.id}>
                    {cls.class_name}
                  </option>
                ))}
              </select>
            )}
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Tanggal Awal
            </label>
            <input
              type="date"
              name="start_date"
              value={start}
              onChange={(e) => setStart(e.target.value)}
              className="w-full h-9 text-xs rounded-lg border-slate-200 bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 text-slate-900"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Tanggal Akhir
            </label>
            <input
              type="date"
              name="end_date"
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              className="w-full h-9 text-xs rounded-lg border-slate-200 bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 text-slate-900"
            />
          </div>

          <div>
            <Button type="submit" variant="primary" size="md" icon={Filter} className="w-full h-9 justify-center">
              Terapkan Filter
            </Button>
          </div>
        </form>
      </div>

      {/* Summary Metrics */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatsCard
          title="Total Siswa"
          value={summary.total_students}
          subtitle={`Kelas: ${selectedClass?.class_name || '-'}`}
          icon={Users}
        />
        <StatsCard
          title="Rata-rata Kehadiran"
          value={`${summary.average_percentage}%`}
          subtitle="Target Min: 85%"
          icon={BarChart3}
        />
        <StatsCard
          title="Presensi Hadir (H)"
          value={summary.total_h}
          subtitle={`Dari ${summary.total_records} data kehadiran`}
          icon={CheckCircle2}
        />
        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Ketidakhadiran (S/I/A)
          </span>
          <div className="flex items-center gap-1.5 mt-2">
            <span className="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200/70 font-mono">
              S: {summary.total_s}
            </span>
            <span className="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/70 font-mono">
              I: {summary.total_i}
            </span>
            <span className="text-xs font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded-md border border-red-200/70 font-mono">
              A: {summary.total_a}
            </span>
          </div>
          <span className="text-[11px] text-slate-400 mt-2">Sakit, Izin, Alpha</span>
        </div>
      </div>

      {/* Recap Table */}
      <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
          <div>
            <h3 className="font-semibold text-sm text-slate-900">
              Daftar Siswa Kelas {selectedClass?.class_name || '-'}
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Periode: {startDate || '-'} s.d. {endDate || '-'}
            </p>
          </div>
          {selectedClassId ? (
            <a
              href={printUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors self-start sm:self-auto cursor-pointer"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>Cetak Laporan A4</span>
            </a>
          ) : (
            <button
              disabled
              className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-400 text-xs font-semibold rounded-lg border border-slate-200/80 cursor-not-allowed self-start sm:self-auto"
              title="Pilih rombongan belajar terlebih dahulu"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>Cetak Laporan A4</span>
            </button>
          )}
        </div>

        {reportData.length === 0 ? (
          <div className="text-center py-12 px-4">
            <Users className="w-10 h-10 text-slate-300 mx-auto mb-2" />
            <h4 className="text-xs font-bold text-slate-700">Tidak ada data rekapitulasi</h4>
            <p className="text-[11px] text-slate-400 mt-0.5">
              Belum ada presensi tercatat untuk kelas dan rentang tanggal yang dipilih.
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-xs">
              <thead>
                <tr className="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                  <th className="py-3 px-4 text-center w-12">No</th>
                  <th className="py-3 px-4">NIS / Identitas</th>
                  <th className="py-3 px-4">Nama Lengkap Siswa</th>
                  <th className="py-3 px-3 text-center bg-emerald-50/50 text-emerald-800">Hadir (H)</th>
                  <th className="py-3 px-3 text-center bg-blue-50/50 text-blue-800">Sakit (S)</th>
                  <th className="py-3 px-3 text-center bg-amber-50/50 text-amber-800">Izin (I)</th>
                  <th className="py-3 px-3 text-center bg-red-50/50 text-red-800">Alpha (A)</th>
                  <th className="py-3 px-4 text-center">Total</th>
                  <th className="py-3 px-4 text-center w-36">Persentase</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 font-medium text-slate-700">
                {reportData.map((row, index) => (
                  <tr key={row.student?.id || index} className="hover:bg-indigo-50/30 transition">
                    <td className="py-3 px-4 text-center font-bold text-slate-400">{index + 1}</td>
                    <td className="py-3 px-4 font-mono font-semibold text-slate-800">
                      {row.student?.username || '-'}
                    </td>
                    <td className="py-3 px-4 font-bold text-slate-900">{row.student?.fullname || '-'}</td>
                    <td className="py-3 px-3 text-center font-bold text-emerald-700 bg-emerald-50/30">{row.h}</td>
                    <td className="py-3 px-3 text-center font-bold text-blue-700 bg-blue-50/30">{row.s}</td>
                    <td className="py-3 px-3 text-center font-bold text-amber-700 bg-amber-50/30">{row.i}</td>
                    <td className="py-3 px-3 text-center font-bold text-red-700 bg-red-50/30">{row.a}</td>
                    <td className="py-3 px-4 text-center font-mono font-bold text-slate-700">{row.total}</td>
                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <div className="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                          <div
                            className={`h-full rounded-full ${
                              row.percentage >= 85 ? 'bg-emerald-500' : row.percentage >= 75 ? 'bg-amber-500' : 'bg-red-500'
                            }`}
                            style={{ width: `${Math.min(row.percentage, 100)}%` }}
                          />
                        </div>
                        <span className={`font-mono font-bold text-xs ${
                          row.percentage >= 85 ? 'text-emerald-700' : row.percentage >= 75 ? 'text-amber-700' : 'text-red-700'
                        }`}>
                          {row.percentage}%
                        </span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};
