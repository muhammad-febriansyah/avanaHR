import { CalendarIcon } from 'lucide-react';
import * as React from 'react';
import { Calendar } from '@/components/ui/calendar';
import { formatDateID, fromDateValue, toDateValue } from '@/lib/format';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    /** `yyyy-mm-dd` string (matches native date inputs / form payloads). */
    value: string | null | undefined;
    onChange: (value: string) => void;
    id?: string;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
};

/**
 * Clean date field: an input-styled trigger showing the localised date and a
 * dependency-free calendar popover. Drop-in replacement for `type="date"`.
 */
function DatePicker({
    value,
    onChange,
    id,
    placeholder = 'Pilih tanggal',
    disabled,
    className,
}: DatePickerProps) {
    const [open, setOpen] = React.useState(false);
    const containerRef = React.useRef<HTMLDivElement>(null);
    const selected = fromDateValue(value);

    React.useEffect(() => {
        if (!open) {
            return;
        }

        function handlePointer(event: MouseEvent) {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }

        function handleKey(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointer);
        document.addEventListener('keydown', handleKey);

        return () => {
            document.removeEventListener('mousedown', handlePointer);
            document.removeEventListener('keydown', handleKey);
        };
    }, [open]);

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                id={id}
                disabled={disabled}
                onClick={() => setOpen((prev) => !prev)}
                className={cn(
                    'border-input flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                    !selected && 'text-muted-foreground',
                    className,
                )}
            >
                <span className="truncate">
                    {selected ? formatDateID(value) : placeholder}
                </span>
                <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            </button>

            {open ? (
                <div className="bg-popover text-popover-foreground absolute left-0 top-[calc(100%+0.25rem)] z-50 rounded-md border shadow-md">
                    <Calendar
                        selected={selected}
                        onSelect={(date) => {
                            onChange(toDateValue(date));
                            setOpen(false);
                        }}
                    />
                </div>
            ) : null}
        </div>
    );
}

export { DatePicker };
