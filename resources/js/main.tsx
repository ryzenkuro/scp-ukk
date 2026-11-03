import React from 'react';
import { createRoot } from 'react-dom/client';
import { AppShell } from './components/layout/AppShell';
import { Login } from './pages/auth/Login';
import { GuruDashboard } from './pages/guru/Dashboard';
import { ScheduleList } from './pages/guru/ScheduleList';
import { JournalList } from './pages/guru/JournalList';
import { JournalCreate } from './pages/guru/JournalCreate';
import { JournalDetail } from './pages/guru/JournalDetail';
import { KesiswaanDashboard } from './pages/kesiswaan/Dashboard';
import { PortalDashboard } from './pages/portal/Dashboard';
import { WakaDashboard } from './pages/waka/Dashboard';
import { AttendanceRecap } from './pages/reports/AttendanceRecap';
import { ProfileEdit } from './pages/profile/Edit';
import { BasePageProps } from './types';

// Page Registry mapping route names to components
const pageRegistry: Record<string, React.ComponentType<any>> = {
  // Auth
  'login': Login,
  'auth.login': Login,

  // Guru
  'guru.dashboard': GuruDashboard,
  'guru.schedules.index': ScheduleList,
  'guru.journals.index': JournalList,
  'guru.journals.create': JournalCreate,
  'guru.journals.show': JournalDetail,

  // Kesiswaan (Wali Kelas & Guru BK)
  'kesiswaan.dashboard': KesiswaanDashboard,

  // Portal Siswa & Orang Tua
  'portal.dashboard': PortalDashboard,

  // Waka Kurikulum
  'waka.dashboard': WakaDashboard,

  // Reports
  'reports.attendance.index': AttendanceRecap,

  // Profile
  'profile.edit': ProfileEdit,
};

document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.getElementById('root');
  if (!rootEl) return;

  const page = rootEl.getAttribute('data-page') || 'login';
  let props: BasePageProps = {};

  try {
    const rawProps = rootEl.getAttribute('data-props');
    if (rawProps) {
      props = JSON.parse(rawProps);
    }
  } catch (err) {
    console.error('[SCP] Failed to parse page props:', err);
  }

  const PageComponent = pageRegistry[page] || Login;
  const isAuthPage = page === 'login' || page === 'auth.login';

  const root = createRoot(rootEl);

  if (isAuthPage) {
    root.render(
      <React.StrictMode>
        <PageComponent {...props} />
      </React.StrictMode>
    );
  } else {
    root.render(
      <React.StrictMode>
        <AppShell
          user={props.authUser}
          config={props.config}
          currentPage={page}
          csrfToken={props.csrfToken}
          pageProps={props}
        >
          <PageComponent {...props} />
        </AppShell>
      </React.StrictMode>
    );
  }
});
