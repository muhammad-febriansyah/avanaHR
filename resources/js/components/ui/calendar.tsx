import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';
import { toDateValue } from '@/lib/format';
import { cn } from '@/lib/utils';

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const WEEKDAYS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

type CalendarProps = {
    selected?: Date | null;
    onSelect: (date: Date) => void;
    className?: string;
};

/**
 * Minimal, dependency-free month calendar in Bahasa Indonesia. Used inside
 * DatePicker; can also stand alone.
 */
function Calendar({ selected, onSelect, className }: CalendarProps) {
    const initial = selected ?? new Date();
    const [view, setView] = React.useState(
        () => new Date(initial.getFullYear(), initial.getMonth(), 1),
    );

    const year = view.getFullYear();
    const month = view.getMonth();
    const firstWeekday = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = toDateValue(new Date());
    const selectedValue = selected ? toDateValue(selected) : null;

    const cells: (number | null)[] = [
        ...Array.from({ length: firstWeekday }, () => null),
        ...Array.from({ length: daysInMonth }, (_, index) => index + 1),
    ];

    return (
        <div className={cn('w-64 p-3', className)}>
            <div className="mb-2 flex items-center justify-between">
                <button
                    type="button"
                    aria-label="Bulan sebelumnya"
                    onClick={() => setView(new Date(year, month - 1, 1))}
                    className="flex size-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                >
                    <ChevronLeft className="size-4" />
                </button>
                <span className="text-sm font-medium text-navy">
                    {MONTHS[month]} {year}
                </span>
                <button
                    type="button"
                    aria-label="Bulan berikutnya"
                    onClick={() => setView(new Date(year, month + 1, 1))}
                    className="flex size-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                >
                    <ChevronRight className="size-4" />
                </button>
            </div>

            <div className="grid grid-cols-7 gap-0.5">
                {WEEKDAYS.map((weekday) => (
                    <span
                        key={weekday}
                        className="flex h-7 items-center justify-center text-xs font-medium text-muted-foreground"
                    >
                        {weekday}
                    </span>
                ))}
                {cells.map((day, index) => {
                    if (day === null) {
                        return <span key={`empty-${index}`} className="h-8" />;
                    }

                    const value = toDateValue(new Date(year, month, day));
                    const isSelected = value === selectedValue;
                    const isToday = value === today;

                    return (
                        <button
                            key={value}
                            type="button"
                            onClick={() => onSelect(new Date(year, month, day))}
                            className={cn(
                                'flex h-8 items-center justify-center rounded-md text-sm tabular-nums transition-colors',
                                isSelected
                                    ? 'bg-primary font-medium text-primary-foreground'
                                    : 'hover:bg-accent hover:text-accent-foreground',
                                !isSelected &&
                                    isToday &&
                                    'font-medium text-primary',
                            )}
                        >
                            {day}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export { Calendar };
