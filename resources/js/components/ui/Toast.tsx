import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { CheckCircle2, AlertTriangle, XCircle } from 'lucide-react';
import { FlashMessages } from '../../types';

interface FlashAlertProps {
  flash?: FlashMessages | null;
  errors?: Record<string, string[]> | null;
}

export const FlashAlert: React.FC<FlashAlertProps> = ({ flash, errors }) => {
  const hasErrors = errors && Object.keys(errors).length > 0;

  return (
    <div className="space-y-3">
      <AnimatePresence>
        {flash?.success && (
          <motion.div
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -6 }}
            className="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200/80 flex items-start gap-2.5 shadow-2xs"
          >
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
            <div>
              <h4 className="text-xs font-semibold text-emerald-900">Berhasil</h4>
              <p className="text-xs text-emerald-800 mt-0.5 leading-relaxed">{flash.success}</p>
            </div>
          </motion.div>
        )}

        {(flash?.error || hasErrors) && (
          <motion.div
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -6 }}
            className="p-3.5 rounded-lg bg-red-50 border border-red-200/80 flex items-start gap-2.5 shadow-2xs"
          >
            <XCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
            <div className="flex-1">
              <h4 className="text-xs font-semibold text-red-900">Perhatian</h4>
              {flash?.error && <p className="text-xs text-red-800 mt-0.5 leading-relaxed">{flash.error}</p>}
              {hasErrors && (
                <ul className="list-disc list-inside text-xs text-red-700 mt-1 space-y-0.5">
                  {Object.entries(errors!).map(([field, errList]) => (
                    <li key={field}>{errList.join(', ')}</li>
                  ))}
                </ul>
              )}
            </div>
          </motion.div>
        )}

        {flash?.import_errors && flash.import_errors.length > 0 && (
          <motion.div
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -6 }}
            className="p-3.5 rounded-lg bg-amber-50 border border-amber-200/80 flex items-start gap-2.5 shadow-2xs"
          >
            <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <h4 className="text-xs font-semibold text-amber-900">Catatan Import Jadwal</h4>
              <ul className="list-disc list-inside text-xs text-amber-800 mt-1 space-y-0.5">
                {flash.import_errors.map((err, i) => (
                  <li key={i}>{err}</li>
                ))}
              </ul>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};
