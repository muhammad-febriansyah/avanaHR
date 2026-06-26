import { Check, ChevronsUpDown, Search } from 'lucide-react';
import * as React from 'react';
import { cn } from '@/lib/utils';

export type ComboboxOption = { value: string; label: string };

type ComboboxProps = {
    value: string;
    onChange: (value: string) => void;
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    id?: string;
    disabled?: boolean;
    className?: string;
};

/**
 * Searchable single-select (select2-style): input-styled trigger + popover
 * with a filter box and scrollable list. Dependency-free; drop-in for a
 * `Select` whose option list is long. Value/onChange use option `value`.
 */
function Combobox({
    value,
    onChange,
    options,
    placeholder = 'Pilih…',
    searchPlaceholder = 'Cari…',
    emptyText = 'Tidak ditemukan.',
    id,
    disabled,
    className,
}: ComboboxProps) {
    const [open, setOpen] = React.useState(false);
    const [query, setQuery] = React.useState('');
    const containerRef = React.useRef<HTMLDivElement>(null);
    const inputRef = React.useRef<HTMLInputElement>(null);

    const selected = options.find((option) => option.value === value) ?? null;
    const normalized = query.trim().toLowerCase();
    const filtered =
        normalized === ''
            ? options
            : options.filter((option) =>
                  option.label.toLowerCase().includes(normalized),
              );

    React.useEffect(() => {
        if (!open) {
            return;
        }

        inputRef.current?.focus();

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

    function select(next: string) {
        onChange(next);
        setOpen(false);
        setQuery('');
    }

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
                    {selected ? selected.label : placeholder}
                </span>
                <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
            </button>

            {open ? (
                <div className="bg-popover text-popover-foreground absolute left-0 top-[calc(100%+0.25rem)] z-50 w-full overflow-hidden rounded-md border shadow-md">
                    <div className="flex items-center gap-2 border-b px-3">
                        <Search className="size-4 shrink-0 text-muted-foreground" />
                        <input
                            ref={inputRef}
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={searchPlaceholder}
                            className="h-9 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto p-1">
                        {filtered.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                {emptyText}
                            </p>
                        ) : (
                            filtered.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => select(option.value)}
                                    className={cn(
                                        'flex w-full items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                                        option.value === value && 'bg-accent',
                                    )}
                                >
                                    <span className="truncate">
                                        {option.label}
                                    </span>
                                    {option.value === value ? (
                                        <Check className="size-4 shrink-0 text-primary" />
                                    ) : null}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

export { Combobox };
