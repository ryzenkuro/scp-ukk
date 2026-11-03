import React, { useState } from 'react';
import { motion } from 'motion/react';
import {
  BookOpen,
  Camera,
  Users,
  CheckCircle2,
  Lock,
  ArrowLeft,
  AlertCircle,
  Upload,
  Send,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Textarea } from '../../components/ui/FormFields';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, Schedule, User, LeaveRequest } from '../../types';

interface JournalCreateProps extends BasePageProps {
  schedule: Schedule;
  today: string;
  students: User[];
  approvedLeaves: Record<string | number, LeaveRequest> | LeaveRequest[];
}

export const JournalCreate: React.FC<JournalCreateProps> = ({
  schedule,
  today,
  students = [],
  approvedLeaves = {},
  flash,
  errors,
  csrfToken,
}) => {
  const [topic, setTopic] = useState('');
  const [selectedFileName, setSelectedFileName] = useState('');
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  // Helper to get student leave if any
  const getLeaveForStudent = (studentId: number): LeaveRequest | null => {
    if (Array.isArray(approvedLeaves)) {
      return approvedLeaves.find((l) => l.user_id === studentId) || null;
    }
    return approvedLeaves[studentId] || null;
  };

  // Default attendance dictionary
  const initialAttendances: Record<number, 'Hadir' | 'Izin' | 'Sakit' | 'Alpha'> = {};
  students.forEach((std) => {
    const leave = getLeaveForStudent(std.id);
    if (leave) {
      initialAttendances[std.id] =
        leave.leave_type === 'Sakit' || leave.reason?.toLowerCase().includes('sakit')
          ? 'Sakit'
          : 'Izin';
    } else {
      initialAttendances[std.id] = 'Hadir';
    }
  });

  const [attendances, setAttendances] = useState(initialAttendances);

  const handlePhotoSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setSelectedFileName(file.name);
      const url = URL.createObjectURL(file);
      setPreviewUrl(url);
    }
  };

  const handleStatusChange = (
    studentId: number,
    status: 'Hadir' | 'Izin' | 'Sakit' | 'Alpha'
  ) => {
    setAttendances((prev) => ({
      ...prev,
      [studentId]: status,
    }));
  };

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
            <span className="text-slate-800 font-bold">Buka Kelas &amp; Presensi</span>
          </div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <BookOpen className="w-6 h-6 text-indigo-600" />
            Form Jurnal Pembelajaran &amp; Presensi Kelas
          </h1>
        </div>

        <a
          href="/guru/journals"
          className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition"
        >
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Kembali ke Daftar Sesi</span>
        </a>
      </div>

      {/* Class Session Hero Card */}
      <div className="bg-slate-900 text-white p-5 rounded-xl border border-slate-800 shadow-xs">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4 mb-4">
          <div>
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
              {schedule.cycle_type}
            </span>
            <h2 className="text-xl font-black mt-1 text-white">{schedule.subject}</h2>
          </div>
          <div className="text-right">
            <span className="text-xs text-slate-400 block">Tanggal Pelaksanaan:</span>
            <span className="text-sm font-bold text-white">{today}</span>
          </div>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div className="bg-white/5 p-3 rounded-xl border border-white/5">
            <span className="text-slate-400 block mb-0.5">Kelas Rombel</span>
            <strong className="text-sm font-bold text-white">
              {schedule.schoolClass?.class_name || schedule.school_class?.class_name || '-'}
            </strong>
          </div>
          <div className="bg-white/5 p-3 rounded-xl border border-white/5">
            <span className="text-slate-400 block mb-0.5">Ruangan / Lab</span>
            <strong className="text-sm font-bold text-white">{schedule.room?.room_name || '-'}</strong>
          </div>
          <div className="bg-white/5 p-3 rounded-xl border border-white/5">
            <span className="text-slate-400 block mb-0.5">Jam Mengajar</span>
            <strong className="text-sm font-bold font-mono text-white">
              {schedule.start_time?.substring(0, 5)} - {schedule.end_time?.substring(0, 5)}
            </strong>
          </div>
          <div className="bg-white/5 p-3 rounded-xl border border-white/5">
            <span className="text-slate-400 block mb-0.5">Total Siswa Terdaftar</span>
            <strong className="text-sm font-bold text-white">{students.length} Siswa</strong>
          </div>
        </div>
      </div>

      {/* Main Form */}
      <form
        method="POST"
        action={`/guru/schedules/${schedule.id}/journals`}
        encType="multipart/form-data"
        className="space-y-6"
      >
        <input type="hidden" name="_token" value={csrfToken} />

        {/* SECTION 1: Topics & Realtime Photo */}
        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
          <div className="border-b border-slate-100 pb-3">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
              <Camera className="w-4 h-4 text-indigo-600" />
              1. Materi Pembelajaran &amp; Bukti Foto Kegiatan Realtime
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Isi deskripsi materi ajar yang disampaikan dan unggah foto dokumentasi kegiatan siswa di kelas/lab.
            </p>
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Ringkasan Materi / Topik Pembelajaran <span className="text-red-500">*</span>
            </label>
            <textarea
              name="topic_description"
              rows={3}
              required
              value={topic}
              onChange={(e) => setTopic(e.target.value)}
              placeholder="Tuliskan topik bahasan, materi ajar, atau kompetensi keahlian yang disampaikan pada pertemuan ini (Minimal 5 karakter)..."
              className="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-600 focus:ring-indigo-600"
            />
            {errors?.topic_description?.[0] && (
              <p className="text-[11px] text-red-600 mt-1">{errors.topic_description[0]}</p>
            )}
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Foto Bukti Pembelajaran Realtime <span className="text-red-500">*</span>
            </label>
            <p className="text-xs text-slate-500 mb-2">
              Dapat langsung memotret menggunakan kamera HP atau memilih berkas foto (JPG/PNG/WebP, Maks. 2MB).
            </p>

            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
              <label className="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 transition">
                <Camera className="w-4 h-4" />
                <span>Ambil Foto / Pilih Berkas</span>
                <input
                  type="file"
                  name="photo"
                  accept="image/*"
                  capture="environment"
                  required
                  className="hidden"
                  onChange={handlePhotoSelect}
                />
              </label>
              <span className="text-xs text-slate-500 italic">
                {selectedFileName || 'Belum ada foto yang dipilih.'}
              </span>
            </div>

            {previewUrl && (
              <div className="mt-4 inline-block relative rounded-2xl overflow-hidden border-2 border-indigo-500 shadow-md">
                <img
                  src={previewUrl}
                  alt="Pratinjau Foto"
                  className="max-h-56 object-cover rounded-xl"
                />
                <div className="absolute bottom-0 inset-x-0 bg-slate-900/80 backdrop-blur-xs text-white text-[11px] py-1 px-3 text-center font-bold">
                  📸 Foto Siap Diunggah
                </div>
              </div>
            )}

            {errors?.photo?.[0] && (
              <p className="text-[11px] text-red-600 mt-1">{errors.photo[0]}</p>
            )}
          </div>
        </div>

        {/* SECTION 2: Attendance Table */}
        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
              <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                <Users className="w-4 h-4 text-indigo-600" />
                2. Presensi Siswa &amp; Sinkronisasi Izin Otomatis
              </h3>
              <p className="text-xs text-slate-500 mt-0.5">
                Siswa dengan izin/sakit yang telah disetujui tim Kesiswaan otomatis disinkronkan dan dikunci.
              </p>
            </div>
            <div className="flex items-center gap-2 text-xs">
              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 text-[10px]">
                ● Hadir Default
              </span>
              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-bold border border-amber-200 text-[10px]">
                🔒 Izin Auto-Sync
              </span>
            </div>
          </div>

          {students.length === 0 ? (
            <div className="text-center py-8 text-slate-400 text-xs">
              Tidak ada data siswa yang terdaftar pada kelas rombel ini.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-y border-slate-200">
                  <tr>
                    <th className="py-3 px-3 w-12 text-center">No</th>
                    <th className="py-3 px-4">Nama Siswa</th>
                    <th className="py-3 px-4">Status Izin Kesiswaan</th>
                    <th className="py-3 px-4 text-center">Pilihan Kehadiran</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium">
                  {students.map((student, idx) => {
                    const leave = getLeaveForStudent(student.id);
                    const hasLeave = Boolean(leave);
                    const currentStatus = attendances[student.id] || 'Hadir';

                    return (
                      <tr
                        key={student.id}
                        className={`hover:bg-slate-50/70 transition ${
                          hasLeave ? 'bg-amber-50/30' : ''
                        }`}
                      >
                        <td className="py-3 px-3 text-center font-bold text-slate-400">
                          {idx + 1}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-slate-900">{student.fullname}</div>
                          <div className="text-[11px] text-slate-400 font-mono">
                            NIS: {student.username}
                          </div>
                        </td>
                        <td className="py-3 px-4">
                          {hasLeave && leave ? (
                            <div className="inline-flex flex-col gap-0.5">
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                <Lock className="w-3 h-3" /> Izin/Sakit Terverifikasi
                              </span>
                              <span className="text-[11px] text-amber-800 italic">
                                &quot;{leave.reason}&quot;
                              </span>
                            </div>
                          ) : (
                            <span className="text-slate-400 text-[11px]">
                              Tidak ada pengajuan izin
                            </span>
                          )}
                        </td>
                        <td className="py-3 px-4 text-center">
                          {hasLeave ? (
                            <>
                              <input
                                type="hidden"
                                name={`attendances[${student.id}]`}
                                value={currentStatus}
                              />
                              <div className="inline-flex items-center gap-1 bg-amber-100 text-amber-900 font-bold px-2.5 py-1 rounded-lg border border-amber-300 text-xs shadow-2xs">
                                <Lock className="w-3 h-3" /> {currentStatus} (Terkunci Sesuai Izin)
                              </div>
                            </>
                          ) : (
                            <div className="inline-flex items-center gap-3">
                              {(['Hadir', 'Izin', 'Sakit', 'Alpha'] as const).map((status) => (
                                <label
                                  key={status}
                                  className="inline-flex items-center gap-1 cursor-pointer text-xs"
                                >
                                  <input
                                    type="radio"
                                    name={`attendances[${student.id}]`}
                                    value={status}
                                    checked={currentStatus === status}
                                    onChange={() => handleStatusChange(student.id, status)}
                                    className="text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                  />
                                  <span
                                    className={`font-bold ${
                                      status === 'Hadir'
                                        ? 'text-emerald-700'
                                        : status === 'Izin'
                                        ? 'text-blue-700'
                                        : status === 'Sakit'
                                        ? 'text-amber-700'
                                        : 'text-red-700'
                                    }`}
                                  >
                                    {status}
                                  </span>
                                </label>
                              ))}
                            </div>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Submit Action */}
        <div className="flex items-center justify-end gap-3 pt-2">
          <a
            href="/guru/journals"
            className="px-4 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
          >
            Batal
          </a>
          <Button type="submit" variant="primary" icon={Send}>
            Simpan Jurnal &amp; Selesaikan Sesi
          </Button>
        </div>
      </form>
    </div>
  );
};
