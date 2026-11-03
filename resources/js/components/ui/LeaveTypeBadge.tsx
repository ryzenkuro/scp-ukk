import React from 'react';
import { HeartPulse, FileText, Timer, Trophy } from 'lucide-react';

interface LeaveTypeBadgeProps {
  type: 'Sakit' | 'Izin' | 'Pulang Cepat' | 'Kegiatan Luar' | string;
  className?: string;
}

export const LeaveTypeBadge: React.FC<LeaveTypeBadgeProps> = ({ type, className = '' }) => {
  switch (type) {
    case 'Sakit':
      return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200/80 ${className}`}>
          <HeartPulse className="w-3 h-3 text-rose-600 shrink-0" />
          <span>Sakit</span>
        </span>
      );
    case 'Izin':
      return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/80 ${className}`}>
          <FileText className="w-3 h-3 text-blue-600 shrink-0" />
          <span>Izin</span>
        </span>
      );
    case 'Pulang Cepat':
      return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200/80 ${className}`}>
          <Timer className="w-3 h-3 text-amber-600 shrink-0" />
          <span>Pulang Cepat</span>
        </span>
      );
    case 'Kegiatan Luar':
      return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200/80 ${className}`}>
          <Trophy className="w-3 h-3 text-purple-600 shrink-0" />
          <span>Kegiatan Luar</span>
        </span>
      );
    default:
      return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-50 text-slate-700 border border-slate-200 ${className}`}>
          <span>{type}</span>
        </span>
      );
  }
};
