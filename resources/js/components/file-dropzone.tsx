import { FileText, ImageOff, UploadCloud, X } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { cn } from '@/lib/utils';

type FileDropzoneProps = {
    /** Currently selected (newly picked) file. */
    value: File | null;
    onChange: (file: File | null) => void;
    /** Accept attribute, e.g. "image/png,image/jpeg" or ".pdf,.png". */
    accept?: string;
    /** Existing stored image URL (edit mode), shown when no new file picked. */
    currentUrl?: string | null;
    /** Existing stored file name (non-image), shown when no new file picked. */
    currentName?: string | null;
    /** Helper line, e.g. "PNG/JPG/SVG · maks 2 MB". */
    hint?: string;
    /** 'image' renders a thumbnail preview; 'file' renders a file chip. */
    variant?: 'image' | 'file';
    /** Frame shape for image previews. */
    shape?: 'wide' | 'square';
    id?: string;
    disabled?: boolean;
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function FileDropzone({
    value,
    onChange,
    accept,
    currentUrl = null,
    currentName = null,
    hint,
    variant = 'image',
    shape = 'wide',
    id,
    disabled = false,
}: FileDropzoneProps) {
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);

    const objectUrl = useMemo(
        () =>
            value && value.type.startsWith('image/')
                ? URL.createObjectURL(value)
                : null,
        [value],
    );

    useEffect(
        () => () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        },
        [objectUrl],
    );

    const previewUrl = objectUrl ?? currentUrl;
    const fileName = value?.name ?? currentName ?? null;
    const hasContent = Boolean(value || currentUrl || currentName);

    function pick(file: File | null) {
        onChange(file);
    }

    function onDrop(event: DragEvent<HTMLDivElement>) {
        event.preventDefault();
        setDragging(false);

        if (disabled) {
            return;
        }

        const file = event.dataTransfer.files?.[0] ?? null;

        if (file) {
            pick(file);
        }
    }

    function clear(event: React.MouseEvent) {
        event.preventDefault();
        event.stopPropagation();
        pick(null);

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }

    const isImageVariant = variant === 'image';
    const frameSize = shape === 'square' ? 'size-20' : 'h-20 w-36';

    return (
        <div className="grid gap-2">
            <input
                ref={inputRef}
                id={inputId}
                type="file"
                accept={accept}
                disabled={disabled}
                className="sr-only"
                onChange={(e) => pick(e.target.files?.[0] ?? null)}
            />

            <label
                htmlFor={inputId}
                onDragOver={(e) => {
                    e.preventDefault();

                    if (!disabled) {
                        setDragging(true);
                    }
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                className={cn(
                    'group relative flex cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed border-input bg-muted/30 p-4 transition-colors',
                    'hover:border-primary/50 hover:bg-primary/5',
                    dragging && 'border-primary bg-primary/10',
                    disabled && 'pointer-events-none opacity-60',
                )}
            >
                {hasContent && isImageVariant && (
                    <div
                        className={cn(
                            'flex shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-background',
                            frameSize,
                        )}
                    >
                        {previewUrl ? (
                            <img
                                src={previewUrl}
                                alt="Pratinjau"
                                className="max-h-full max-w-full object-contain"
                            />
                        ) : (
                            <ImageOff className="size-5 text-muted-foreground" />
                        )}
                    </div>
                )}

                {hasContent && !isImageVariant && (
                    <div className="flex size-12 shrink-0 items-center justify-center rounded-lg border bg-background">
                        <FileText className="size-6 text-primary" />
                    </div>
                )}

                {!hasContent && (
                    <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary/10">
                        <UploadCloud className="size-6 text-primary" />
                    </div>
                )}

                <div className="min-w-0 flex-1">
                    {hasContent ? (
                        <>
                            <p className="truncate text-sm font-medium">
                                {fileName ?? 'Gambar tersimpan'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {value
                                    ? formatBytes(value.size)
                                    : 'Tersimpan · klik untuk mengganti'}
                            </p>
                        </>
                    ) : (
                        <>
                            <p className="text-sm font-medium">
                                Seret &amp; lepas atau{' '}
                                <span className="text-primary">
                                    klik untuk pilih
                                </span>
                            </p>
                            {hint && (
                                <p className="text-xs text-muted-foreground">
                                    {hint}
                                </p>
                            )}
                        </>
                    )}
                </div>

                {hasContent && (
                    <button
                        type="button"
                        onClick={clear}
                        className="flex size-7 shrink-0 items-center justify-center rounded-full border bg-background text-muted-foreground transition-colors hover:border-destructive hover:text-destructive"
                        aria-label="Hapus berkas"
                    >
                        <X className="size-4" />
                    </button>
                )}
            </label>

            {hasContent && hint && (
                <p className="text-xs text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}
