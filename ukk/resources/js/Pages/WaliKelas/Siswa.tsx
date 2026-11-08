import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@/Components/layout/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
  Table,
  TableHeader,
  TableBody,
  TableHead,
  TableRow,
  TableCell,
} from '@/Components/ui/table';
import { Users, Search, ShieldCheck } from 'lucide-react';

interface StudentItem {
  id: number;
  nis: string;
  name: string;
  kelas: string;
  jurusan: string;
  gender: string;
  status: string;
  user?: {
    id: number;
    username: string;
    is_active: boolean;
    must_change_credentials: boolean;
  };
  parent_user?: {
    id: number;
    username: string;
  };
}

interface PaginatedStudents {
  data: StudentItem[];
  current_page: number;
  last_page: number;
  total: number;
  links: { url: string | null; label: string; active: boolean }[];
}

interface SiswaProps {
  classNames: string[];
  students: PaginatedStudents;
  filters: {
    search: string;
  };
}

export default function Siswa({ classNames = [], students, filters }: SiswaProps) {
  const [search, setSearch] = useState(filters.search || '');

  const handleSearch = () => {
    router.get('/wali-kelas/siswa', { search }, { preserveState: true });
  };

  const handleReset = () => {
    setSearch('');
    router.get('/wali-kelas/siswa', {}, { preserveState: true });
  };

  return (
    <AppLayout title="Data Siswa Binaan">
      <Head title="Siswa Rombel Binaan - Wali Kelas" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Daftar Siswa Rombel Binaan</h2>
            <p className="text-sm text-muted-foreground mt-0.5">
              Data seluruh siswa aktif di kelas {classNames.join(', ') || 'yang Anda bina'}.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="text-xs">
              Total {students.total} Siswa
            </Badge>
          </div>
        </div>

        {/* Filter Card */}
        <Card className="p-4">
          <div className="flex flex-col sm:flex-row items-center gap-3">
            <div className="relative flex-1 w-full">
              <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari berdasarkan nama atau NIS siswa..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                className="pl-9 h-9 text-xs"
              />
            </div>
            <div className="flex items-center gap-2 w-full sm:w-auto">
              <Button onClick={handleSearch} size="sm" className="h-9 gap-1 text-xs">
                <span>Cari</span>
              </Button>
              {search && (
                <Button onClick={handleReset} variant="outline" size="sm" className="h-9 text-xs">
                  Reset
                </Button>
              )}
            </div>
          </div>
        </Card>

        {/* Data Table (Read-Only) */}
        <Card>
          <div className="rounded-md border border-border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12">No</TableHead>
                  <TableHead>Nama Siswa</TableHead>
                  <TableHead>NIS</TableHead>
                  <TableHead>Kelas</TableHead>
                  <TableHead>Gender</TableHead>
                  <TableHead>Akun Siswa</TableHead>
                  <TableHead>Akun Orang Tua</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {students.data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                      Tidak ada data siswa yang sesuai pencarian.
                    </TableCell>
                  </TableRow>
                ) : (
                  students.data.map((student, idx) => (
                    <TableRow key={student.id}>
                      <TableCell className="text-xs text-muted-foreground">
                        {(students.current_page - 1) * 20 + idx + 1}
                      </TableCell>
                      <TableCell className="font-semibold text-sm">
                        {student.name}
                      </TableCell>
                      <TableCell className="font-mono text-xs text-muted-foreground">
                        {student.nis}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="text-xs">
                          {student.kelas}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-xs">
                        {student.gender === 'L' ? 'Laki-laki' : 'Perempuan'}
                      </TableCell>
                      <TableCell>
                        {student.user ? (
                          <div className="flex items-center gap-1.5">
                            <span className="font-mono text-xs text-foreground font-medium">
                              {student.user.username}
                            </span>
                            {!student.user.is_active && (
                              <Badge variant="destructive" className="text-[10px] px-1 py-0">
                                Nonaktif
                              </Badge>
                            )}
                          </div>
                        ) : (
                          <span className="text-xs text-muted-foreground">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        {student.parent_user ? (
                          <span className="font-mono text-xs text-muted-foreground">
                            {student.parent_user.username}
                          </span>
                        ) : (
                          <span className="text-xs text-muted-foreground">-</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="text-emerald-700 border-emerald-300 bg-emerald-50 text-xs">
                          Aktif
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {/* Pagination */}
          {students.last_page > 1 && (
            <div className="flex items-center justify-between p-4 border-t border-border">
              <span className="text-xs text-muted-foreground">
                Halaman {students.current_page} dari {students.last_page} ({students.total} total siswa)
              </span>
              <div className="flex items-center gap-1">
                {students.links.map((link, lIdx) => (
                  <Button
                    key={lIdx}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    className="h-8 text-xs"
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                  />
                ))}
              </div>
            </div>
          )}
        </Card>
      </div>
    </AppLayout>
  );
}

