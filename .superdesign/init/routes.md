# Routes — School Class Progress (SCP)

Config-based (Laravel `routes/web.php` + `routes/auth.php`). Views are Blade, server-rendered; controllers pass ALL page data via `view('...',$data)`. Forms are plain POST + CSRF. Role middleware: `role:<roles>` and `schedule.unlocked`.

`/dashboard` global role router: super_admin,waka_kurikulum → `/admin` (Filament, TO BE REMOVED & rebuilt in React); guru → `guru.dashboard`; wali_kelas,guru_bk → `kesiswaan.dashboard`; siswa,orang_tua → `portal.dashboard`.

## Route → view map (web.php)
| URL | Name | Middleware/role | View |
|---|---|---|---|
| `/` | — | guest redirect → `/login` | — |
| `/admin/*` | Filament | — | Filament panel ( slated for removal ) |
| `/waka/dashboard` | waka.dashboard | role:waka_kurikulum,super_admin | `waka/dashboard` |
| `/waka/config/toggle-cycle|toggle-lock` | PATCH | | — |
| `/waka/classes|rooms|users` | POST | | — |
| `/waka/schedules/import` POST + `/template` GET | | | — |
| `/guru/dashboard` | guru.dashboard | role:guru | `guru/dashboard` |
| `/guru/schedules` | guru.schedules.index | role:guru | `guru/schedules/index` |
| `/guru/schedules` POST, `/guru/schedules/{id}` DELETE | | + schedule.unlocked | — |
| `/guru/journals` | guru.journals.index | role:guru | `guru/journals/index` |
| `/guru/schedules/{id}/journals/create` | guru.journals.create | role:guru | `guru/journals/create` |
| `/guru/schedules/{id}/journals` | POST | | — |
| `/guru/journals/{id}` | guru.journals.show | role:guru | `guru/journals/show` |
| `/kesiswaan/dashboard` | kesiswaan.dashboard | role:wali_kelas,guru_bk | `kesiswaan/dashboard` |
| `/kesiswaan/leave-requests/{id}/verify` | PATCH | | — |
| `/portal/dashboard` | portal.dashboard | role:siswa,orang_tua | `portal/dashboard` |
| `/portal/leave-requests` | POST | | — |
| `/reports/attendance` | reports.attendance.index | role:guru,wali_kelas,guru_bk,waka_kurikulum,super_admin | `reports/attendance_index` |
| `/reports/attendance/print` | reports.attendance.print | same | `reports/attendance_print` (standalone A4, no app layout) |
| `/profile` GET/PATCH/DELETE | profile.* | auth | `profile/edit` + partials |

## Auth routes (auth.php, Breeze; guest except noted)
| URL | Name | View |
|---|---|---|
| `/login` GET/POST | login | `auth/login` |
| `/register` | register | DISABLED → redirect login (closed system, accounts managed by Waka) |
| `/forgot-password` GET/POST | password.request / password.email | `auth/forgot-password` |
| `/reset-password/{token}` GET, `/reset-password` POST | password.reset / password.store | `auth/reset-password` |
| `/verify-email` (+`/{id}/{hash}` signed), resend POST | verification.* (auth) | `auth/verify-email` |
| `/confirm-password` GET/POST | password.confirm (auth) | `auth/confirm-password` |
| `PUT /password` | password.update (auth, profile page) | — |
| `POST /logout` | logout (auth) | — |

## Key controller-passed data (observed)
- guru.dashboard: `$teacher,$config(active_cycle,is_schedule_locked),$currentDayName,$today,$todaySchedules(+journals relation),$overdueSchedules,$mySchedulesCount,$cutoffTime`
- portal.dashboard: `$user,$isParent,$student(+schoolClass),$leaveRequests (collection of leave_type,start_date,end_date,leave_time,reason,status_approval,document_path,applicant,verifier)`
- kesiswaan.dashboard: pending/verified leave request lists (verification PATCH approve/reject)
- Login: username (NOT email) + password + remember.

## Future (approved plan)
Blade shell + React islands per page (same routes/controllers/POST endpoints). `/admin` Filament removed; admin CRUD (Users, Teachers, Students, Rooms, SchoolClasses, Schedules, Journals + config) rebuilt as React pages on the same portal shell.
