import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const MONTHS_SHORT_ID = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

const MONTHS_LONG_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export function formatDateIndo(
    dateStr?: string | Date | null,
    options?: { month?: 'short' | 'long' }
): string {
    if (!dateStr) return '-';

    const monthStyle = options?.month || 'short';
    const monthNames = monthStyle === 'long' ? MONTHS_LONG_ID : MONTHS_SHORT_ID;

    if (typeof dateStr === 'string') {
        const cleanDate = dateStr.split('T')[0].trim();
        const parts = cleanDate.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            const year = parts[0];
            const monthIdx = parseInt(parts[1], 10) - 1;
            const day = parts[2].padStart(2, '0');
            if (monthIdx >= 0 && monthIdx < 12) {
                return `${day} ${monthNames[monthIdx]} ${year}`;
            }
        }
    }

    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return String(dateStr);

    const day = String(d.getDate()).padStart(2, '0');
    const month = monthNames[d.getMonth()];
    const year = d.getFullYear();

    return `${day} ${month} ${year}`;
}

export function formatDateRangeIndo(
    startDate?: string | Date | null,
    endDate?: string | Date | null,
    options?: { month?: 'short' | 'long' }
): string {
    if (!startDate && !endDate) return '-';
    if (!startDate) return formatDateIndo(endDate, options);
    if (!endDate) return formatDateIndo(startDate, options);

    const formattedStart = formatDateIndo(startDate, options);
    const formattedEnd = formatDateIndo(endDate, options);

    if (formattedStart === formattedEnd) {
        return formattedStart;
    }

    return `${formattedStart} s.d. ${formattedEnd}`;
}


