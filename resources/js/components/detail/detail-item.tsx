import type { LucideProps } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type DetailItemProps = {
    label: string;
    value?: ReactNode;
    icon?: ComponentType<LucideProps>;
    className?: string;
    /** Hide the bottom divider (use inside compact groups). */
    flush?: boolean;
};

/**
 * Label/value pair for detail pages. Renders an em dash for empty values so
 * the layout stays aligned. Shared across all show pages.
 */
export function DetailItem({
    label,
    value,
    icon: Icon,
    className,
    flush,
}: DetailItemProps) {
    const isEmpty = value === null || value === undefined || value === '';

    return (
        <div
            className={cn(
                'flex flex-col gap-1 pb-3',
                !flush && 'border-b border-border/50 last:border-0 last:pb-0',
                className,
            )}
        >
            <span className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                {Icon ? <Icon className="size-3.5" /> : null}
                {label}
            </span>
            <span
                className={cn(
                    'text-sm font-medium',
                    isEmpty ? 'text-muted-foreground/50' : 'text-foreground',
                )}
            >
                {isEmpty ? '—' : value}
            </span>
        </div>
    );
}
