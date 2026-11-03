import React from 'react';
import { LucideIcon } from 'lucide-react';

interface StatsCardProps {
  title: string;
  value: string | number;
  description?: string;
  subtitle?: string;
  icon?: LucideIcon | React.ComponentType<{ className?: string }>;
  variant?: 'default' | 'emerald' | 'amber' | 'red' | 'indigo';
  action?: React.ReactNode;
}

export const StatsCard: React.FC<StatsCardProps> = ({
  title,
  value,
  description,
  subtitle,
  icon: Icon,
  variant = 'default',
  action,
}) => {
  const displaySubtitle = subtitle || description;

  const colorStyles = {
    default: 'text-slate-900 border-slate-200/80',
    emerald: 'text-emerald-700 border-emerald-200/70',
    amber: 'text-amber-800 border-amber-200/70',
    red: 'text-red-700 border-red-200/70',
    indigo: 'text-indigo-700 border-indigo-200/70',
  }[variant];

  const iconBgStyles = {
    default: 'bg-slate-100 text-slate-600',
    emerald: 'bg-emerald-50 text-emerald-600 border border-emerald-100',
    amber: 'bg-amber-50 text-amber-600 border border-amber-100',
    red: 'bg-red-50 text-red-600 border border-red-100',
    indigo: 'bg-indigo-50 text-indigo-600 border border-indigo-100',
  }[variant];

  return (
    <div className={`p-5 rounded-xl bg-white border shadow-xs transition-all duration-150 flex flex-col justify-between ${colorStyles}`}>
      <div className="flex items-start justify-between gap-3">
        <div>
          <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">
            {title}
          </span>
          <span className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-mono">
            {value}
          </span>
        </div>
        {Icon && (
          <div className={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${iconBgStyles}`}>
            <Icon className="w-4 h-4" />
          </div>
        )}
      </div>

      {(displaySubtitle || action) && (
        <div className="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>{displaySubtitle}</span>
          {action}
        </div>
      )}
    </div>
  );
};
