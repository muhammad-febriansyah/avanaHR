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
