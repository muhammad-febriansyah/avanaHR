import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type TimelineAccent =
    | 'blue'
    | 'green'
    | 'amber'
    | 'red'
    | 'violet'
    | 'slate';

const DOT_ACCENTS: Record<TimelineAccent, string> = {
    blue: 'bg-sky-500',
    green: 'bg-emerald-500',
    amber: 'bg-amber-500',
    red: 'bg-red-500',
    violet: 'bg-violet-500',
    slate: 'bg-slate-400',
};

export type TimelineEntry = {
    title: ReactNode;
    meta?: ReactNode;
    description?: ReactNode;
    accent?: TimelineAccent;
};

type TimelineProps = {
    items: TimelineEntry[];
    className?: string;
    emptyText?: string;
};

/**
 * Vertical timeline with a connecting rail and accent dots. Used for audit
 * trails, approval history and decision logs.
 */
export function Timeline({ items, className, emptyText }: TimelineProps) {
    if (items.length === 0) {
        return (
            <p className="py-4 text-sm text-muted-foreground">
                {emptyText ?? 'Belum ada riwayat.'}
            </p>
        );
    }

    return (
        <ol className={cn('relative flex flex-col', className)}>
            {items.map((item, index) => {
                const isLast = index === items.length - 1;

                return (
                    <li
                        key={index}
                        className="relative flex gap-3 pb-4 last:pb-0"
                    >
                        <div className="flex flex-col items-center">
                            <span
                                className={cn(
                                    'mt-1 size-2.5 shrink-0 rounded-full ring-4 ring-background',
                                    DOT_ACCENTS[item.accent ?? 'slate'],
                                )}
                            />
                            {!isLast ? (
                                <span className="w-px flex-1 bg-border" />
                            ) : null}
                        </div>
                        <div className="flex min-w-0 flex-col gap-0.5 pb-1">
                            <span className="text-sm font-medium text-foreground">
                                {item.title}
                            </span>
                            {item.description ? (
                                <span className="text-sm text-muted-foreground">
                                    {item.description}
                                </span>
                            ) : null}
                            {item.meta ? (
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {item.meta}
                                </span>
                            ) : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
