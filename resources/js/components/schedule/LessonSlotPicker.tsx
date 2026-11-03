import React, { useState, useEffect, useMemo } from 'react';
import { Clock, Sparkles, SlidersHorizontal, Info, CheckCircle2 } from 'lucide-react';
import { Select } from '../ui/FormFields';

export interface SlotTime {
  slot: number;
  start: string;
  end: string;
  durationMinutes: number;
}

// 1. Data Alokasi Waktu Resmi SMKN 1 Bantul (TA 2025/2026)
export const SLOTS_SENIN_UPACARA: SlotTime[] = [
  { slot: 1, start: '08:00', end: '08:35', durationMinutes: 35 },
  { slot: 2, start: '08:35', end: '09:10', durationMinutes: 35 },
  { slot: 3, start: '09:10', end: '09:45', durationMinutes: 35 },
  { slot: 4, start: '10:00', end: '10:35', durationMinutes: 35 },
  { slot: 5, start: '10:35', end: '11:10', durationMinutes: 35 },
  { slot: 6, start: '11:10', end: '11:45', durationMinutes: 35 },
  { slot: 7, start: '12:45', end: '13:25', durationMinutes: 40 },
  { slot: 8, start: '13:25', end: '14:00', durationMinutes: 35 },
  { slot: 9, start: '14:00', end: '14:35', durationMinutes: 35 },
  { slot: 10, start: '14:35', end: '15:10', durationMinutes: 35 },
];

export const SLOTS_DEFAULT: SlotTime[] = [
  { slot: 1, start: '07:15', end: '07:55', durationMinutes: 40 },
  { slot: 2, start: '07:55', end: '08:35', durationMinutes: 40 },
  { slot: 3, start: '08:35', end: '09:15', durationMinutes: 40 },
  { slot: 4, start: '09:30', end: '10:10', durationMinutes: 40 },
  { slot: 5, start: '10:10', end: '10:50', durationMinutes: 40 },
  { slot: 6, start: '10:50', end: '11:30', durationMinutes: 40 },
  { slot: 7, start: '12:30', end: '13:10', durationMinutes: 40 },
  { slot: 8, start: '13:10', end: '13:50', durationMinutes: 40 },
  { slot: 9, start: '13:50', end: '14:30', durationMinutes: 40 },
  { slot: 10, start: '14:30', end: '15:10', durationMinutes: 40 },
];

export const SLOTS_SELASA: SlotTime[] = [
  { slot: 1, start: '07:30', end: '08:10', durationMinutes: 40 },
  { slot: 2, start: '08:10', end: '08:50', durationMinutes: 40 },
  { slot: 3, start: '08:50', end: '09:30', durationMinutes: 40 },
  { slot: 4, start: '09:45', end: '10:25', durationMinutes: 40 },
  { slot: 5, start: '10:25', end: '11:05', durationMinutes: 40 },
  { slot: 6, start: '11:05', end: '11:45', durationMinutes: 40 },
  { slot: 7, start: '12:45', end: '13:25', durationMinutes: 40 },
  { slot: 8, start: '13:25', end: '14:00', durationMinutes: 35 },
  { slot: 9, start: '14:00', end: '14:35', durationMinutes: 35 },
  { slot: 10, start: '14:35', end: '15:10', durationMinutes: 35 },
];

export const SLOTS_JUMAT: SlotTime[] = [
  { slot: 1, start: '07:15', end: '07:55', durationMinutes: 40 },
  { slot: 2, start: '07:55', end: '08:35', durationMinutes: 40 },
  { slot: 3, start: '08:35', end: '09:15', durationMinutes: 40 },
  { slot: 4, start: '09:30', end: '10:10', durationMinutes: 40 },
  { slot: 5, start: '10:10', end: '10:50', durationMinutes: 40 },
  { slot: 6, start: '10:50', end: '11:25', durationMinutes: 35 },
  { slot: 7, start: '12:40', end: '13:15', durationMinutes: 35 },
  { slot: 8, start: '13:15', end: '13:50', durationMinutes: 35 },
];

interface LessonSlotPickerProps {
  day: string; // 'Senin' | 'Selasa' | 'Rabu' | 'Kamis' | 'Jumat' | 'Sabtu'
  initialStart?: string;
  initialEnd?: string;
  onChangeTime?: (start: string, end: string) => void;
}

export const LessonSlotPicker: React.FC<LessonSlotPickerProps> = ({
  day,
  initialStart = '08:00',
  initialEnd = '09:45',
  onChangeTime,
}) => {
  const [isManual, setIsManual] = useState(false);
  const [hasCeremony, setHasCeremony] = useState(true); // Khusus Senin
  const [startSlot, setStartSlot] = useState(1);
  const [endSlot, setEndSlot] = useState(day === 'Jumat' ? 3 : 4);

  // Manual values
  const [manualStart, setManualStart] = useState(initialStart);
  const [manualEnd, setManualEnd] = useState(initialEnd);

  // Tentukan tabel slot berdasarkan hari & upacara
  const activeSlots = useMemo(() => {
    if (day === 'Jumat') {
      return SLOTS_JUMAT;
    }
    if (day === 'Senin') {
      return hasCeremony ? SLOTS_SENIN_UPACARA : SLOTS_DEFAULT;
    }
    if (day === 'Selasa') {
      return SLOTS_SELASA;
    }
    // Rabu, Kamis, Sabtu
    return SLOTS_DEFAULT;
  }, [day, hasCeremony]);

  const maxSlot = activeSlots.length; // 8 di Jumat, 10 di hari lain

  // Sesuaikan slot jika melebihi batas (misal ganti dari Senin ke Jumat)
  useEffect(() => {
    if (startSlot > maxSlot) {
      setStartSlot(1);
    }
    if (endSlot > maxSlot) {
      setEndSlot(maxSlot);
    }
    if (endSlot < startSlot) {
      setEndSlot(startSlot);
    }
  }, [maxSlot, startSlot, endSlot]);

  // Hitung jam mulai dan selesai
  const currentStartTime = useMemo(() => {
    if (isManual) return manualStart;
    const found = activeSlots.find((s) => s.slot === startSlot);
    return found ? found.start : '07:15';
  }, [isManual, manualStart, activeSlots, startSlot]);

  const currentEndTime = useMemo(() => {
    if (isManual) return manualEnd;
    const found = activeSlots.find((s) => s.slot === endSlot);
    return found ? found.end : '09:30';
  }, [isManual, manualEnd, activeSlots, endSlot]);

  // Sinkronkan ke callback jika ada
  useEffect(() => {
    if (onChangeTime) {
      onChangeTime(currentStartTime, currentEndTime);
    }
  }, [currentStartTime, currentEndTime, onChangeTime]);

  const totalSlots = endSlot - startSlot + 1;

  return (
    <div className="space-y-3 pt-1">
      {/* Header Pemilihan Mode */}
      <div className="flex items-center justify-between">
        <label className="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wide">
          <Clock className="w-4 h-4 text-indigo-600" />
          <span>Waktu Pembelajaran (SMKN 1 Bantul)</span>
        </label>
        <button
          type="button"
          onClick={() => setIsManual(!isManual)}
          className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer transition"
        >
          <SlidersHorizontal className="w-3.5 h-3.5" />
          <span>{isManual ? 'Gunakan Slot Jam Resmi' : 'Input Jam Bebas (Manual)'}</span>
        </button>
      </div>

      {/* Input Tersembunyi (Hidden Inputs) agar form POST selalu menerima data yang valid */}
      <input type="hidden" name="start_time" value={currentStartTime} />
      <input type="hidden" name="end_time" value={currentEndTime} />

      {!isManual ? (
        <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          {/* Opsi Khusus Hari Senin (Upacara 60 Menit) */}
          {day === 'Senin' && (
            <div className="flex items-center justify-between p-2.5 bg-indigo-50/80 border border-indigo-100 rounded-lg text-xs">
              <label className="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={hasCeremony}
                  onChange={(e) => setHasCeremony(e.target.checked)}
                  className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                />
                <span className="font-semibold text-indigo-950">
                  Ada Upacara Bendera / Briefing (07.00 - 08.00)
                </span>
              </label>
              <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-200/60 text-indigo-800">
                {hasCeremony ? 'Jam 1 Mulai 08:00' : 'Mulai 07:15 (Default)'}
              </span>
            </div>
          )}

          {day === 'Jumat' && (
            <div className="p-2.5 bg-amber-50/80 border border-amber-200 rounded-lg text-xs text-amber-900 flex items-center gap-2">
              <Info className="w-4 h-4 text-amber-600 shrink-0" />
              <span>Hari Jumat maksimal <strong>8 Jam Pelajaran</strong> (Selesai 13.50 WIB).</span>
            </div>
          )}

          {/* Grid Pemilihan Slot Jam Ke- */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                Dari Jam Ke-
              </label>
              <select
                value={startSlot}
                onChange={(e) => {
                  const val = parseInt(e.target.value, 10);
                  setStartSlot(val);
                  if (val > endSlot) {
                    setEndSlot(val);
                  }
                }}
                className="w-full text-xs font-medium rounded-lg border-slate-300 bg-white py-2 px-2.5 text-slate-900 focus:border-indigo-600 focus:ring-indigo-600"
              >
                {activeSlots.map((s) => (
                  <option key={s.slot} value={s.slot}>
                    Jam {s.slot} ({s.start})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                Sampai Jam Ke-
              </label>
              <select
                value={endSlot}
                onChange={(e) => setEndSlot(parseInt(e.target.value, 10))}
                className="w-full text-xs font-medium rounded-lg border-slate-300 bg-white py-2 px-2.5 text-slate-900 focus:border-indigo-600 focus:ring-indigo-600"
              >
                {activeSlots
                  .filter((s) => s.slot >= startSlot)
                  .map((s) => (
                    <option key={s.slot} value={s.slot}>
                      Jam {s.slot} (s.d {s.end})
                    </option>
                  ))}
              </select>
            </div>
          </div>

          {/* Kartu Ringkasan Waktu Otomatis */}
          <div className="p-3 bg-white border border-indigo-100 rounded-lg flex items-center justify-between shadow-sm">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">
                {totalSlots} JP
              </div>
              <div>
                <span className="text-xs uppercase font-medium text-slate-400 block tracking-wider">
                  Waktu Pelaksanaan
                </span>
                <span className="text-sm font-bold text-indigo-950 font-mono tracking-tight">
                  {currentStartTime} — {currentEndTime} WIB
                </span>
              </div>
            </div>

            <div className="text-right">
              <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                <span>Jam Ke-{startSlot} s.d {endSlot}</span>
              </span>
            </div>
          </div>
        </div>
      ) : (
        /* Mode Manual: Menampilkan input type="time" biasa jika diperlukan */
        <div className="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <div className="p-2 bg-amber-50 text-amber-800 text-xs rounded-lg border border-amber-200 flex items-center gap-1.5">
            <Info className="w-4 h-4 shrink-0 text-amber-600" />
            <span>Mode Jam Bebas aktif. Anda dapat menentukan jam mulai dan selesai secara kustom.</span>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Jam Mulai</label>
              <input
                type="time"
                value={manualStart}
                onChange={(e) => setManualStart(e.target.value)}
                required
                className="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-600 focus:ring-indigo-600"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Jam Selesai</label>
              <input
                type="time"
                value={manualEnd}
                onChange={(e) => setManualEnd(e.target.value)}
                required
                className="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-600 focus:ring-indigo-600"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
