import React, { ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SidebarNav } from './SidebarNav';
import { CheckCircle2, AlertCircle } from 'lucide-react';

interface AppLayoutProps {
  title?: string;
  showYearBadge?: boolean;
  children: ReactNode;
}

export function AppLayout({ title = 'Dashboard', showYearBadge = true, children }: AppLayoutProps) {
  const { flash, academic_year } = usePage<any>().props;

  return (
    <div className="flex h-screen overflow-hidden bg-background text-foreground font-sans">
      <Head title={title} />

      {/* Sidebar Nav */}
      <SidebarNav />

      {/* Main Content Area */}
      <div className="flex flex-1 flex-col overflow-hidden relative">
        {/* Top Header */}
        <header className="flex h-16 items-center justify-between border-b border-border bg-card px-8">
          <div>
            <h1 className="text-xl font-bold tracking-tight">{title}</h1>
          </div>
          {showYearBadge && (
            <div className="flex items-center gap-4">
              <div className="rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                Tahun Ajaran {academic_year || '2026/2027'}
              </div>
            </div>
          )}
        </header>

        {/* Page Content Body with Flash Message Notifications */}
        <main className="flex-1 overflow-y-auto bg-muted/30 p-8">
          {/* Flash Messages */}
          {flash?.success && (
            <div className="mb-6 flex items-center gap-3 rounded-lg border border-border bg-card p-4 text-sm shadow-sm">
              <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
              <span>{flash.success}</span>
            </div>
          )}

          {flash?.error && (
            <div className="mb-6 flex items-center gap-3 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive shadow-sm">
              <AlertCircle className="h-5 w-5 shrink-0" />
              <span>{flash.error}</span>
            </div>
          )}

          {children}
        </main>
      </div>
    </div>
  );
}
export default AppLayout;
