/**
 * Locale-aware formatting helpers (Bahasa Indonesia / WIB / Rupiah).
 */

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Asia/Jakarta',
});

const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

/** "19 Mar 1980" — returns "-" for empty/invalid input. */
export function formatDateID(value?: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '-' : dateFormatter.format(date);
}

/** "19 Mar 1980 14:30" in WIB — returns "-" for empty/invalid input. */
export function formatDateTimeID(value?: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '-' : dateTimeFormatter.format(date);
}

/** "Rp 10.000.000" */
export function formatRupiah(value?: number | null): string {
    return rupiahFormatter.format(value ?? 0);
}

/** Strip every non-digit, returning the raw numeric string ("1.000" → "1000"). */
export function parseDigits(value: string): string {
    return value.replace(/\D/g, '');
}

/** Group digits with thousand separators for display ("1000000" → "1.000.000"). */
export function groupDigits(value: string | number | null | undefined): string {
    const digits = parseDigits(String(value ?? ''));

    if (digits === '') {
        return '';
    }

    return new Intl.NumberFormat('id-ID').format(Number(digits));
}

/** Format a Date as a `yyyy-mm-dd` string in local time (no timezone shift). */
export function toDateValue(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/** Parse a `yyyy-mm-dd` string into a local Date, or null when empty/invalid. */
export function fromDateValue(value?: string | null): Date | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    const date = new Date(year, month - 1, day);

    return Number.isNaN(date.getTime()) ? null : date;
}
