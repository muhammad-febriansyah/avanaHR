import * as React from 'react';
import { groupDigits, parseDigits } from '@/lib/format';
import { cn } from '@/lib/utils';

type RupiahInputProps = Omit<
    React.ComponentProps<'input'>,
    'value' | 'onChange' | 'type'
> & {
    /** Raw numeric value (string of digits or number). */
    value: string | number | null | undefined;
    /** Receives the raw digit string ("1000000"), ready to submit as a number. */
    onChange: (value: string) => void;
};

/**
 * Rupiah amount field: shows an "Rp" prefix and live thousand separators while
 * keeping the underlying value as plain digits for form submission.
 */
function RupiahInput({
    value,
    onChange,
    className,
    disabled,
    ...props
}: RupiahInputProps) {
    return (
        <div
            className={cn(
                'border-input flex h-9 w-full min-w-0 items-center rounded-md border bg-transparent shadow-xs transition-[color,box-shadow]',
                'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                disabled && 'pointer-events-none opacity-50',
                className,
            )}
        >
            <span className="pl-3 pr-2 text-sm text-muted-foreground select-none">
                Rp
            </span>
            <input
                {...props}
                type="text"
                inputMode="numeric"
                disabled={disabled}
                data-slot="rupiah-input"
                value={groupDigits(value)}
                onChange={(event) => onChange(parseDigits(event.target.value))}
                className="h-full w-full min-w-0 rounded-r-md bg-transparent pr-3 text-base tabular-nums outline-none placeholder:text-muted-foreground md:text-sm"
            />
        </div>
    );
}

export { RupiahInput };
