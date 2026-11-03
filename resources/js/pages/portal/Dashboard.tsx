import React, { useState } from 'react';
import {
  FilePlus2,
  Calendar,
  AlertCircle,
  FileText,
  Upload,
  Clock,
  CheckCircle2,
  Send,
  UserCheck,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input, Select, Textarea } from '../../components/ui/FormFields';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { LeaveTypeBadge } from '../../components/ui/LeaveTypeBadge';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, LeaveRequest, User } from '../../types';

interface PortalDashboardProps extends BasePageProps {
  user?: User;
  isParent?: boolean;
  student?: User | null;
  leaveRequests?: {
    data: LeaveRequest[];
  } | LeaveRequest[];
}

export const PortalDashboard: React.FC<PortalDashboardProps> = ({
  user,
  isParent = false,
  student,
  leaveRequests,
  flash,
  errors,
  csrfToken,
}) => {
  const requestsList: LeaveRequest[] = Array.isArray(leaveRequests)
    ? leaveRequests
    : leaveRequests?.data || [];

  const [leaveType, setLeaveType] = useState('Sakit');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [leaveTime, setLeaveTime] = useState('');
  const [reason, setReason] = useState('');
  const [selectedFileName, setSelectedFileName] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const isParentUnlinked = isParent && !student;

  return (
    <div className="space-y-6">
      <FlashAlert flash={flash} errors={errors} />

      {/* HEADER SECTION */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div>
          <h1 className="text-xl font-bold text-slate-900 tracking-tight">
            Portal Perizinan Siswa
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            Pengajuan surat keterangan sakit, izin acara keluarga, atau dispensasi kegiatan.
          </p>
        </div>

        <span
          className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border ${
            isParent ? 'bg-amber-50 text-amber-800 border-amber-200/70' : 'bg-emerald-50 text-emerald-800 border-emerald-200/70'
          }`}
        >
          <UserCheck className="w-3.5 h-3.5 shrink-0" />
          <span>{isParent ? 'Orang Tua Siswa' : 'Siswa'}</span>
        </span>
      </div>

      {/* UNLINKED PARENT ACCOUNT WARNING */}
      {isParentUnlinked && (
        <div className="p-4 rounded-xl bg-amber-50 border border-amber-200/80 shadow-xs flex items-start gap-3">
          <AlertCircle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <h4 className="text-xs font-semibold text-amber-950">Akun Belum Terhubung dengan Data Siswa</h4>
            <p className="text-xs text-amber-800 mt-0.5">
              Akun orang tua Anda belum tertaut dengan NISN putra/putri. Harap hubungi staf Waka Kurikulum
              atau Wali Kelas untuk menghubungkan akun agar dapat mengajukan izin resmi.
            </p>
          </div>
        </div>
      )}

      {/* TWO COLUMN GRID: LEFT FORM, RIGHT TIMELINE */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* LEFT COLUMN: SUBMIT LEAVE FORM (5 Cols) */}
        <div className="lg:col-span-5 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
          <div className="border-b border-slate-100 pb-3">
            <h2 className="text-sm font-semibold text-slate-900 tracking-tight">Form Pengajuan Izin Baru</h2>
            <p className="text-xs text-slate-500 mt-0.5">Isi rincian permohonan izin dengan benar.</p>
          </div>

          <form
            method="POST"
            action="/portal/leave-requests"
            encType="multipart/form-data"
            onSubmit={() => setIsSubmitting(true)}
            className="space-y-4"
          >
            {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}

            {/* Leave Type Select */}
            <Select
              label="Jenis Izin"
              name="leave_type"
              required
              value={leaveType}
              onChange={(e) => setLeaveType(e.target.value)}
              disabled={isParentUnlinked}
            >
              <option value="Sakit">Sakit (Melampirkan Surat Dokter / Keterangan)</option>
              <option value="Izin">Izin (Urusan Keluarga / Kepentingan Pribadi)</option>
              <option value="Pulang Cepat">Pulang Cepat (Izin Keluar Sebelum Jam Bubar)</option>
              <option value="Kegiatan Luar">Kegiatan Luar (Lomba / Dinas Luar Sekolah)</option>
            </Select>

            {/* Date Range */}
            <div className="grid grid-cols-2 gap-3">
              <Input
                label="Mulai Tanggal"
                name="start_date"
                type="date"
                required
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                disabled={isParentUnlinked}
              />
              <Input
                label="Sampai Tanggal"
                name="end_date"
                type="date"
                required
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                disabled={isParentUnlinked}
              />
            </div>

            {/* Conditional Leave Time for 'Pulang Cepat' */}
            {leaveType === 'Pulang Cepat' && (
              <div className="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/60 space-y-2">
                <span className="text-xs font-bold text-amber-900 block">Jam Keluar Sekolah:</span>
                <Input
                  name="leave_time"
                  type="time"
                  required
                  value={leaveTime}
                  onChange={(e) => setLeaveTime(e.target.value)}
                  placeholder="00:00"
                  disabled={isParentUnlinked}
                />
              </div>
            )}

            {/* Reason */}
            <Textarea
              label="Alasan Pengajuan"
              name="reason"
              required
              rows={3}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Tuliskan keterangan detail alasan tidak dapat mengikuti KBM..."
              disabled={isParentUnlinked}
            />

            {/* File Dropzone */}
            <div className="space-y-1.5">
              <label className="block text-sm font-medium text-slate-900">
                Unggah Bukti Surat / Foto (PDF/JPG/PNG)
              </label>
              <div className="relative border-2 border-dashed border-slate-200 hover:border-indigo-400 rounded-xl p-4 text-center cursor-pointer transition bg-slate-50/50">
                <input
                  type="file"
                  name="document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  onChange={(e) => {
                    if (e.target.files && e.target.files[0]) {
                      setSelectedFileName(e.target.files[0].name);
                    }
                  }}
                  className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                  disabled={isParentUnlinked}
                />
                <div className="flex flex-col items-center justify-center">
                  <Upload className="w-6 h-6 text-slate-400 mb-1.5" />
                  <span className="text-xs font-semibold text-slate-700">
                    {selectedFileName ? selectedFileName : 'Pilih file atau seret ke sini'}
                  </span>
                  <span className="text-[11px] text-slate-400 mt-0.5">Maksimal ukuran file 5 MB</span>
                </div>
              </div>
            </div>

            {/* Submit CTA */}
            <Button
              type="submit"
              variant="primary"
              className="w-full mt-2"
              isLoading={isSubmitting}
              disabled={isParentUnlinked}
            >
              <Send className="w-4 h-4" />
              <span>Kirim Permohonan Izin</span>
            </Button>
          </form>
        </div>

        {/* RIGHT COLUMN: LEAVE HISTORY & TIMELINE (7 Cols) */}
        <div className="lg:col-span-7 space-y-4">
          <div className="flex items-center justify-between pb-2">
            <div>
              <h3 className="text-base font-bold text-slate-900">Riwayat Permohonan Izin</h3>
              <p className="text-xs text-slate-500">Pantau status persetujuan dari Wali Kelas dan Guru BK</p>
            </div>
            <span className="text-xs font-semibold text-slate-600 font-mono bg-slate-100 px-2.5 py-1 rounded-full">
              {requestsList.length} Pengajuan
            </span>
          </div>

          {requestsList.length > 0 ? (
            <div className="space-y-3">
              {requestsList.map((req) => (
                <div
                  key={req.id}
                  className="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-2.5 transition hover:border-slate-300"
                >
                  <div className="flex items-center justify-between gap-2">
                    <LeaveTypeBadge type={req.leave_type} />
                    <StatusBadge status={req.status_approval} />
                  </div>

                  <div>
                    <h4 className="text-xs font-semibold text-slate-900">{req.reason}</h4>
                    <div className="flex items-center gap-2 text-xs text-slate-500 mt-1 font-mono">
                      <Calendar className="w-3.5 h-3.5 text-slate-400" />
                      <span>{req.start_date} s.d. {req.end_date}</span>
                      {req.leave_time && (
                        <span className="text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded font-medium">
                          Keluar: {req.leave_time}
                        </span>
                      )}
                    </div>
                  </div>

                  {req.rejection_note && (
                    <div className="p-2.5 rounded-lg bg-red-50 text-xs text-red-800 border border-red-200/80">
                      <strong>Catatan Penolakan:</strong> {req.rejection_note}
                    </div>
                  )}

                  {req.document_path && (
                    <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                      <span className="text-slate-500 flex items-center gap-1.5">
                        <FileText className="w-3.5 h-3.5 text-slate-400" />
                        Bukti Terlampir
                      </span>
                      <a
                        href={`/storage/${req.document_path}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-indigo-600 font-semibold hover:underline"
                      >
                        Buka Dokumen &rarr;
                      </a>
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="p-10 text-center bg-white rounded-xl border border-slate-200/80 text-slate-400 shadow-xs">
              <FileText className="w-12 h-12 mx-auto mb-2 text-slate-300" />
              <h4 className="text-sm font-bold text-slate-800">Belum Ada Riwayat Izin</h4>
              <p className="text-xs text-slate-500 mt-1">Anda belum pernah mengajukan permohonan izin belajar.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
