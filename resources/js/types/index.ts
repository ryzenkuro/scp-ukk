export interface Role {
  id: number;
  role_name: 'waka_kurikulum' | 'guru' | 'wali_kelas' | 'guru_bk' | 'siswa' | 'orang_tua';
}

export interface Department {
  id: number;
  department_code: string;
  department_name: string;
  description?: string | null;
  classes_count?: number;
}

export interface SchoolClass {
  id: number;
  class_name: string;
  department_id?: number | null;
  grade?: 'X' | 'XI' | 'XII' | null;
  department?: Department | null;
}

export interface Room {
  id: number;
  room_name: string;
}

export interface User {
  id: number;
  username: string;
  fullname: string;
  role_id: number;
  class_id?: number | null;
  parent_id?: number | null;
  role?: Role;
  school_class?: SchoolClass;
  schoolClass?: SchoolClass;
  parent?: User | null;
  children?: User[];
}

export interface SystemConfig {
  id: number;
  active_cycle: 'Putaran 1' | 'Putaran 2';
  is_schedule_locked: boolean;
  school_name?: string;
}

export interface Schedule {
  id: number;
  teacher_id: number;
  class_id: number;
  room_id: number;
  cycle_type: 'Putaran 1' | 'Putaran 2';
  day_of_week: string;
  start_time: string;
  end_time: string;
  subject: string;
  teacher?: User;
  school_class?: SchoolClass;
  schoolClass?: SchoolClass;
  room?: Room;
  journals?: Journal[];
}

export interface Attendance {
  id: number;
  journal_id: number;
  student_id: number;
  status: 'Hadir' | 'Sakit' | 'Izin' | 'Alpa' | 'Alpha';
  notes?: string | null;
  student?: User;
}

export interface Journal {
  id: number;
  schedule_id: number;
  date: string;
  topic?: string;
  topic_description?: string;
  teaching_activity?: string;
  photo_path?: string | null;
  notes?: string | null;
  schedule?: Schedule;
  attendances?: Attendance[];
}

export interface LeaveRequest {
  id: number;
  user_id: number;
  class_id?: number;
  leave_type: 'Sakit' | 'Izin' | 'Pulang Cepat' | 'Kegiatan Luar';
  start_date: string;
  end_date: string;
  leave_time?: string | null;
  reason: string;
  document_path?: string | null;
  status_approval: 'Pending' | 'Approved' | 'Rejected';
  verified_by?: number | null;
  verified_at?: string | null;
  rejection_note?: string | null;
  applicant?: User;
  user?: User;
  verifier?: User;
}

export interface FlashMessages {
  success?: string | null;
  error?: string | null;
  status?: string | null;
  import_errors?: string[] | null;
}

export interface BasePageProps {
  authUser?: User | null;
  flash?: FlashMessages;
  errors?: Record<string, string[]> | null;
  csrfToken?: string;
  [key: string]: any;
}
