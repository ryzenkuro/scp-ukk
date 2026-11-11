import React, { ReactNode, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SidebarNav } from './SidebarNav';
import { CheckCircle2, AlertCircle, Menu } from 'lucide-react';

interface AppLayoutProps {
  title?: string;
  showYearBadge?: boolean;
  children: ReactNode;
}

export function AppLayout({ title = 'Dashboard', showYearBadge = true, children }: AppLayoutProps) {
  const { flash, academic_year } = usePage<any>().props;
  const [isMobileNavOpen, setIsMobileNavOpen] = useState(false);

  return (
    <div className="flex h-screen overflow-hidden bg-background text-foreground font-sans">
      <Head title={title} />

      {/* Backdrop Overlay Mobile */}
      {isMobileNavOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 backdrop-blur-xs md:hidden"
          onClick={() => setIsMobileNavOpen(false)}
        />
      )}

      {/* Sidebar Nav */}
      <SidebarNav
        isOpen={isMobileNavOpen}
        onClose={() => setIsMobileNavOpen(false)}
      />

      {/* Main Content Area */}
      <div className="flex flex-1 flex-col overflow-hidden relative min-w-0">
        {/* Top Header */}
        <header className="flex h-16 items-center justify-between border-b border-border bg-card px-4 sm:px-6 md:px-8">
          <div className="flex items-center gap-2.5 min-w-0">
            <button
              type="button"
              onClick={() => setIsMobileNavOpen(true)}
              className="md:hidden -ml-1.5 p-2 rounded-md text-muted-foreground hover:bg-accent hover:text-foreground shrink-0"
            >
              <Menu className="h-5 w-5" />
            </button>
            <h1 className="text-lg sm:text-xl font-bold tracking-tight truncate">{title}</h1>
          </div>
          {showYearBadge && (
            <div className="flex items-center gap-4 shrink-0">
              <div className="rounded-full bg-secondary px-2.5 py-1 sm:px-3 sm:py-1 text-[11px] sm:text-xs font-medium text-secondary-foreground">
                <span className="hidden sm:inline">Tahun Ajaran </span>{academic_year || '2026/2027'}
              </div>
            </div>
          )}
        </header>

        {/* Page Content Body with Flash Message Notifications */}
        <main className="flex-1 overflow-y-auto bg-muted/30 p-4 sm:p-6 md:p-8">
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
