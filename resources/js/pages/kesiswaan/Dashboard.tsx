import React, { useState } from 'react';
import {
  ClipboardCheck,
  Clock,
  CheckCircle2,
  XCircle,
  FileText,
  ExternalLink,
  ShieldAlert,
  Send,
  Eye,
  User as UserIcon,
  Calendar,
  BookOpen,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { StatsCard } from '../../components/layout/StatsCard';
import { DataTable, Column, FilterTab } from '../../components/layout/DataTable';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { LeaveTypeBadge } from '../../components/ui/LeaveTypeBadge';
import { SlideOver } from '../../components/ui/SlideOver';
import { Textarea } from '../../components/ui/FormFields';
import { FlashAlert } from '../../components/ui/Toast';
import { BasePageProps, LeaveRequest, User } from '../../types';

interface KesiswaanDashboardProps extends BasePageProps {
  user?: User;
  isHomeroom?: boolean;
  selectedStatus?: string;
  counts?: {
    all: number;
    pending: number;
    approved: number;
    rejected: number;
  };
  leaveRequests?: {
    data: LeaveRequest[];
    current_page: number;
    last_page: number;
    total: number;
  } | LeaveRequest[];
}

export const KesiswaanDashboard: React.FC<KesiswaanDashboardProps> = ({
  user,
  isHomeroom = false,
  selectedStatus = 'All',
  counts = { all: 0, pending: 0, approved: 0, rejected: 0 },
  leaveRequests,
  flash,
  errors,
  csrfToken,
}) => {
  const requestsList: LeaveRequest[] = Array.isArray(leaveRequests)
    ? leaveRequests
    : leaveRequests?.data || [];

  const [activeTab, setActiveTab] = useState(selectedStatus.toLowerCase());
  const [selectedRequest, setSelectedRequest] = useState<LeaveRequest | null>(null);
  const [isSlideOverOpen, setIsSlideOverOpen] = useState(false);
  const [rejectionNote, setRejectionNote] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const tabs: FilterTab[] = [
    { id: 'all', label: 'Semua Pengajuan', count: counts.all },
    { id: 'pending', label: 'Menunggu Verifikasi', count: counts.pending },
    { id: 'approved', label: 'Disetujui', count: counts.approved },
    { id: 'rejected', label: 'Ditolak', count: counts.rejected },
  ];

  const handleOpenDetail = (request: LeaveRequest) => {
    setSelectedRequest(request);
    setRejectionNote(request.rejection_note || '');
    setIsSlideOverOpen(true);
  };

  const handleTabChange = (tabId: string) => {
    setActiveTab(tabId);
    const statusMap: Record<string, string> = {
      all: 'All',
      pending: 'Pending',
      approved: 'Approved',
      rejected: 'Rejected',
    };
    window.location.href = `/kesiswaan/dashboard?status=${statusMap[tabId] || 'All'}`;
  };

  const columns: Column<LeaveRequest>[] = [
    {
      key: 'applicant',
      header: 'Nama Siswa / Pemohon',
      render: (req) => {
        const studentName = req.applicant?.fullname || req.user?.fullname || 'Siswa';
        const className = req.applicant?.schoolClass?.class_name || req.applicant?.school_class?.class_name || '-';
        return (
          <div>
            <span className="font-bold text-slate-900 block">{studentName}</span>
            <span className="text-xs text-slate-500 font-mono">Kelas: {className}</span>
          </div>
        );
      },
    },
    {
      key: 'leave_type',
      header: 'Jenis Izin',
      render: (req) => <LeaveTypeBadge type={req.leave_type} />,
    },
    {
      key: 'dates',
      header: 'Rentang Waktu',
      render: (req) => (
        <div className="font-mono text-xs">
          <span className="block font-semibold text-slate-800">{req.start_date} s.d. {req.end_date}</span>
          {req.leave_time && (
            <span className="text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded font-medium">
              Keluar jam: {req.leave_time}
            </span>
          )}
        </div>
      ),
    },
    {
      key: 'reason',
      header: 'Alasan Izin',
      render: (req) => (
        <p className="text-xs text-slate-600 max-w-xs truncate" title={req.reason}>
          {req.reason}
        </p>
      ),
    },
    {
      key: 'document',
      header: 'Dokumen',
      render: (req) =>
        req.document_path ? (
          <a
            href={`/storage/${req.document_path}`}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
          >
            <FileText className="w-3.5 h-3.5" />
            <span>Lihat Bukti</span>
            <ExternalLink className="w-3 h-3 ml-0.5" />
          </a>
        ) : (
          <span className="text-xs text-slate-400 italic">Tanpa lampiran</span>
        ),
    },
    {
      key: 'status_approval',
      header: 'Status',
      align: 'center',
      render: (req) => <StatusBadge status={req.status_approval} />,
    },
    {
      key: 'action',
      header: 'Aksi',
      align: 'right',
      render: (req) => (
        <Button variant="secondary" size="sm" onClick={() => handleOpenDetail(req)}>
          <Eye className="w-3.5 h-3.5" />
          <span>Tinjau</span>
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <FlashAlert flash={flash} errors={errors} />

      {/* HEADER SECTION */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div>
          <h1 className="text-xl font-bold text-slate-900 tracking-tight">
            Panel Verifikasi Perizinan Siswa
          </h1>
          <p className="text-xs text-slate-500 mt-0.5">
            Tinjau dan verifikasi surat izin sakit, izin keperluan keluarga, atau dispensasi kegiatan.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/guru/dashboard"
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition"
          >
            <BookOpen className="w-3.5 h-3.5" />
            <span>Dashboard KBM Mengajar</span>
          </a>
          <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border ${
              isHomeroom ? 'bg-amber-50 text-amber-800 border-amber-200/70' : 'bg-purple-50 text-purple-800 border-purple-200/70'
            }`}
          >
            <ShieldAlert className="w-3.5 h-3.5 shrink-0" />
            <span>
              {isHomeroom
                ? `Wali Kelas: ${user?.schoolClass?.class_name || user?.school_class?.class_name || 'Aktif'}`
                : 'Guru BK (Seluruh Kelas)'}
            </span>
          </span>
        </div>
      </div>

      {/* STAT CARDS OVERVIEW */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatsCard title="Total Pengajuan" value={counts.all} icon={ClipboardCheck} variant="default" />
        <StatsCard
          title="Menunggu"
          value={counts.pending}
          icon={Clock}
          variant={counts.pending > 0 ? 'amber' : 'default'}
        />
        <StatsCard title="Disetujui" value={counts.approved} icon={CheckCircle2} variant="emerald" />
        <StatsCard title="Ditolak" value={counts.rejected} icon={XCircle} variant="red" />
      </div>

      {/* RESOURCE DATA TABLE WITH SEGMENTED TABS */}
      <DataTable
        columns={columns}
        data={requestsList}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={handleTabChange}
        searchPlaceholder="Cari nama siswa atau alasan..."
        searchFilter={(item, q) => {
          const name = item.applicant?.fullname || item.user?.fullname || '';
          return name.toLowerCase().includes(q) || item.reason.toLowerCase().includes(q);
        }}
        emptyTitle="Tidak Ada Pengajuan Izin"
        emptyDescription={`Tidak ditemukan pengajuan izin siswa dengan status filter ${activeTab}.`}
      />

      {/* SLIDE-OVER DRAWER VERIFIKASI IZIN */}
      <SlideOver
        isOpen={isSlideOverOpen}
        onClose={() => setIsSlideOverOpen(false)}
        title="Tinjau & Verifikasi Permohonan Izin"
        subtitle={
          selectedRequest
            ? `${selectedRequest.applicant?.fullname || selectedRequest.user?.fullname || 'Siswa'} (${selectedRequest.leave_type})`
            : undefined
        }
      >
        {selectedRequest && (
          <div className="space-y-6">
            {/* Student & Leave Info Cards */}
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500 uppercase">Pemohon</span>
                <StatusBadge status={selectedRequest.status_approval} />
              </div>
              <div className="space-y-1">
                <h4 className="text-base font-bold text-slate-900">
                  {selectedRequest.applicant?.fullname || selectedRequest.user?.fullname}
                </h4>
                <p className="text-xs text-slate-600">
                  Kelas:{' '}
                  <span className="font-semibold text-slate-800 font-mono">
                    {selectedRequest.applicant?.schoolClass?.class_name ||
                      selectedRequest.applicant?.school_class?.class_name ||
                      '-'}
                  </span>
                </p>
              </div>

              <div className="pt-2 border-t border-slate-200/60 grid grid-cols-2 gap-2 text-xs">
                <div>
                  <span className="text-slate-400 block mb-0.5">Jenis Izin</span>
                  <LeaveTypeBadge type={selectedRequest.leave_type} />
                </div>
                <div>
                  <span className="text-slate-400 block mb-0.5">Tanggal</span>
                  <span className="font-mono font-semibold text-slate-800">
                    {selectedRequest.start_date} s.d. {selectedRequest.end_date}
                  </span>
                </div>
              </div>

              {selectedRequest.leave_time && (
                <div className="p-2.5 rounded-lg bg-amber-50 text-amber-900 text-xs font-medium">
                  Izin Pulang Cepat pada jam: <strong>{selectedRequest.leave_time}</strong>
                </div>
              )}
            </div>

            {/* Reason */}
            <div>
              <span className="text-xs font-semibold text-slate-500 block uppercase mb-1.5">
                Alasan Pengajuan
              </span>
              <div className="p-4 rounded-xl bg-white border border-slate-200 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">
                {selectedRequest.reason}
              </div>
            </div>

            {/* Document Proof Attachment Preview */}
            <div>
              <span className="text-xs font-semibold text-slate-500 block uppercase mb-1.5">
                Bukti Surat / Foto Lampiran
              </span>
              {selectedRequest.document_path ? (
                <div className="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-semibold text-slate-800 truncate flex items-center gap-1.5">
                      <FileText className="w-4 h-4 text-indigo-600" />
                      {selectedRequest.document_path.split('/').pop()}
                    </span>
                    <a
                      href={`/storage/${selectedRequest.document_path}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-indigo-600 font-bold hover:underline shrink-0"
                    >
                      Buka Asli &rarr;
                    </a>
                  </div>
                  {/* Photo preview if image */}
                  {selectedRequest.document_path.match(/\.(jpg|jpeg|png|webp)$/i) && (
                    <img
                      src={`/storage/${selectedRequest.document_path}`}
                      alt="Bukti Izin"
                      className="w-full max-h-60 object-contain rounded-lg border border-slate-200 bg-white"
                    />
                  )}
                </div>
              ) : (
                <div className="p-4 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">
                  Tidak ada dokumen lampiran yang diunggah.
                </div>
              )}
            </div>

            {/* ACTION FORMS FOR APPROVE / REJECT */}
            {selectedRequest.status_approval === 'Pending' && (
              <div className="pt-4 border-t border-slate-200 space-y-4">
                <Textarea
                  label="Catatan Verifikasi (Wajib diisi jika menolak)"
                  value={rejectionNote}
                  onChange={(e) => setRejectionNote(e.target.value)}
                  placeholder="Berikan alasan atau arahan jika pengajuan ditolak..."
                  rows={3}
                />

                <div className="grid grid-cols-2 gap-3 pt-2">
                  {/* REJECT BUTTON */}
                  <form
                    method="POST"
                    action={`/kesiswaan/leave-requests/${selectedRequest.id}/verify`}
                    onSubmit={() => setIsSubmitting(true)}
                  >
                    {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}
                    <input type="hidden" name="_method" value="PATCH" />
                    <input type="hidden" name="status" value="Rejected" />
                    <input type="hidden" name="status_approval" value="Rejected" />
                    <input type="hidden" name="rejection_note" value={rejectionNote} />
                    <Button
                      type="submit"
                      variant="destructive"
                      className="w-full"
                      isLoading={isSubmitting}
                      disabled={!rejectionNote.trim()}
                    >
                      <XCircle className="w-4 h-4" />
                      <span>Tolak Izin</span>
                    </Button>
                  </form>

                  {/* APPROVE BUTTON */}
                  <form
                    method="POST"
                    action={`/kesiswaan/leave-requests/${selectedRequest.id}/verify`}
                    onSubmit={() => setIsSubmitting(true)}
                  >
                    {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}
                    <input type="hidden" name="_method" value="PATCH" />
                    <input type="hidden" name="status" value="Approved" />
                    <input type="hidden" name="status_approval" value="Approved" />
                    <Button type="submit" variant="primary" className="w-full bg-emerald-600 hover:bg-emerald-700" isLoading={isSubmitting}>
                      <CheckCircle2 className="w-4 h-4" />
                      <span>Setujui Izin</span>
                    </Button>
                  </form>
                </div>
              </div>
            )}
          </div>
        )}
      </SlideOver>
    </div>
  );
};
