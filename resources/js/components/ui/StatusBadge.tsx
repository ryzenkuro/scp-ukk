import React from 'react';
import { motion } from 'motion/react';
import { CheckCircle2, XCircle } from 'lucide-react';

interface StatusBadgeProps {
  status: 'Pending' | 'Approved' | 'Rejected' | string;
  className?: string;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status, className = '' }) => {
  if (status === 'Approved' || status === 'Disetujui') {
    return (
      <motion.span
        initial={{ scale: 0.95 }}
        animate={{ scale: 1 }}
        className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80 ${className}`}
      >
        <CheckCircle2 className="w-3 h-3 text-emerald-600 shrink-0" />
        <span>Disetujui</span>
      </motion.span>
    );
  }

  if (status === 'Rejected' || status === 'Ditolak') {
    return (
      <span
        className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200/80 ${className}`}
      >
        <XCircle className="w-3 h-3 text-red-600 shrink-0" />
        <span>Ditolak</span>
      </span>
    );
  }

  // Default: Pending / Menunggu Verifikasi
  return (
    <span
      className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200/80 ${className}`}
    >
      <span>Menunggu</span>
    </span>
  );
};
