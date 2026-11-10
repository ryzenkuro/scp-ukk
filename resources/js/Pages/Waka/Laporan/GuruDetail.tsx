import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
  Table,
  TableHeader,
  TableBody,
  TableHead,
  TableRow,
  TableCell,
} from '@/Components/ui/table';
import { 
  ArrowLeft, 
  Calendar, 
  BookOpen, 
  Clock, 
  ExternalLink,
  Users,
  Eye
} from 'lucide-react';
import { formatDateIndo } from '@/lib/utils';
import { JournalDetailModal, JournalDetailData } from '@/Components/modals/JournalDetailModal';

interface GuruInfo {
  id: number;
  username: string;
  fullname: string;
  email: string;
  phone: string;
}

interface AttendanceItem {
  id: number;
  status: 'hadir' | 'sakit' | 'izin' | 'alpa';
}

interface JournalItem {
  id: number;
  date: string;
  topic: string;
  notes?: string;
  documentation_path?: string;
  created_at: string;
  schedule?: {
    id: number;
    subject: string;
    day_of_week: string;
    start_time: string;
    end_time: string;
    class_name: string;
  };
  attendances?: AttendanceItem[];
}

interface ScheduleItem {
  id: number;
  day_of_week: string;
  start_time: string;
  end_time: string;
  subject: string;
  class_name: string;
  room_name?: string;
  cycle_number: number;
  school_class?: {
    name: string;
  };
  room?: {
    name: string;
  };
}

interface GuruDetailProps {
  guru: GuruInfo;
  journals: JournalItem[];
  schedules: ScheduleItem[];
  totalJournals: number;
  totalSchedules: number;
}

export default function GuruDetail({
  guru,
  journals = [],
  schedules = [],
  totalJournals,
  totalSchedules,
}: GuruDetailProps) {
  const [selectedJournal, setSelectedJournal] = useState<JournalDetailData | null>(null);

  const formatTime = (timeStr: string) => {
    return timeStr ? timeStr.substring(0, 5) : '';
  };

  return (
    <AppLayout title={`Detail Jurnal Guru - ${guru.fullname}`}>
      <Head title={`Detail Jurnal ${guru.fullname} - Waka`} />

      <div className="space-y-6">
        {/* Header & Back Button */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <Link href="/waka/laporan/guru">
                <Button variant="ghost" size="sm" className="h-7 px-2 gap-1 text-xs">
                  <ArrowLeft className="h-3.5 w-3.5" />
                  <span>Kembali ke Rekap Guru</span>
                </Button>
              </Link>
            </div>
            <h2 className="text-2xl font-bold tracking-tight">{guru.fullname}</h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Username: <span className="font-mono text-foreground">{guru.username}</span> 
              {guru.phone && ` • Kontak: ${guru.phone}`} 
              {guru.email && ` • Email: ${guru.email}`}
            </p>
          </div>

          <div className="flex items-center gap-3">
            <Card className="px-3 py-1.5 text-center">
              <span className="text-[10px] text-muted-foreground uppercase font-semibold">Total Jurnal</span>
              <p className="text-base font-bold text-emerald-600">{totalJournals}</p>
            </Card>
            <Card className="px-3 py-1.5 text-center">
              <span className="text-[10px] text-muted-foreground uppercase font-semibold">Jadwal Mengajar</span>
              <p className="text-base font-bold text-primary">{totalSchedules}</p>
            </Card>
          </div>
        </div>

        {/* Section 1: Riwayat Jurnal KBM yang Ditulis */}
        <Card className="p-6">
          <div className="mb-4">
            <h3 className="text-lg font-semibold tracking-tight">Riwayat Pengisian Jurnal Pembelajaran</h3>
            <p className="text-xs text-muted-foreground mt-0.5">
              Daftar seluruh materi, aktivitas KBM, catatan, dan dokumentasi foto yang diisi oleh guru bersangkutan.
            </p>
          </div>

          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead>Tanggal</TableHead>
                  <TableHead>Mata Pelajaran & Kelas</TableHead>
                  <TableHead className="text-center">Detail Jurnal & Presensi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {journals.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="h-24 text-center text-muted-foreground">
                      Guru ini belum mengisi jurnal pembelajaran pada sistem.
                    </TableCell>
                  </TableRow>
                ) : (
                  journals.map((j, idx) => {
                    return (
                      <TableRow key={j.id}>
                        <TableCell className="text-xs text-center text-muted-foreground">{idx + 1}</TableCell>
                        <TableCell className="whitespace-nowrap text-xs font-medium">
                          {formatDateIndo(j.date)}
                        </TableCell>
                        <TableCell>
                          <div className="font-semibold text-sm">
                            {j.schedule?.subject || 'KBM Reguler'}
                          </div>
                          <div className="text-[11px] text-muted-foreground">
                            {j.schedule?.class_name} • {j.schedule?.day_of_week} ({formatTime(j.schedule?.start_time || '')} - {formatTime(j.schedule?.end_time || '')})
                          </div>
                        </TableCell>
                        <TableCell className="text-center">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setSelectedJournal(j as unknown as JournalDetailData)}
                            className="h-8 gap-1.5 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border-slate-200 dark:bg-slate-900 dark:border-slate-800 text-foreground shadow-2xs"
                          >
                            <Eye className="h-3.5 w-3.5 text-muted-foreground" />
                            Lihat Detail
                          </Button>
                        </TableCell>
                      </TableRow>
                    );
                  })
                )}
              </TableBody>
            </Table>
          </div>
        </Card>

        {/* Section 2: Jadwal Mengajar Aktif Guru */}
        <Card className="p-6">
          <div className="mb-4">
            <h3 className="text-lg font-semibold tracking-tight">Jadwal Mengajar Terdaftar</h3>
            <p className="text-xs text-muted-foreground mt-0.5">
              Jadwal KBM yang diampu oleh {guru.fullname} pada kalender akademik sekolah.
            </p>
          </div>

          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12">No</TableHead>
                  <TableHead>Hari</TableHead>
                  <TableHead>Jam Pelajaran</TableHead>
                  <TableHead>Mata Pelajaran</TableHead>
                  <TableHead>Kelas</TableHead>
                  <TableHead>Ruangan</TableHead>
                  <TableHead className="text-center">Siklus</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {schedules.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="h-24 text-center text-muted-foreground">
                      Tidak ada jadwal mengajar yang terdaftar untuk guru ini.
                    </TableCell>
                  </TableRow>
                ) : (
                  schedules.map((s, idx) => (
                    <TableRow key={s.id}>
                      <TableCell className="text-xs text-muted-foreground">{idx + 1}</TableCell>
                      <TableCell className="font-semibold text-xs">{s.day_of_week}</TableCell>
                      <TableCell className="font-mono text-xs text-muted-foreground">
                        {formatTime(s.start_time)} - {formatTime(s.end_time)}
                      </TableCell>
                      <TableCell className="font-medium text-sm">{s.subject}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className="text-xs">{s.class_name}</Badge>
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">{s.room_name || '-'}</TableCell>
                      <TableCell className="text-center">
                        <Badge variant="secondary" className="text-xs">Putaran {s.cycle_number}</Badge>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </Card>
      </div>

      <JournalDetailModal
        isOpen={!!selectedJournal}
        onClose={() => setSelectedJournal(null)}
        journal={selectedJournal}
        canEdit={false}
      />
    </AppLayout>
  );
}

