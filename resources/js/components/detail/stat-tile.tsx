import type { LucideProps } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type StatAccent =
    | 'blue'
    | 'green'
    | 'amber'
    | 'red'
    | 'violet'
    | 'teal'
    | 'slate';

const ACCENTS: Record<StatAccent, string> = {
    blue: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
    green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
    red: 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
    violet: 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
    teal: 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400',
    slate: 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
};

type StatTileProps = {
    label: string;
    value: ReactNode;
    icon?: ComponentType<LucideProps>;
    accent?: StatAccent;
    sub?: ReactNode;
    className?: string;
};

/**
 * KPI tile: accent icon chip + label + emphasised value with optional
 * sub-line. Used for the summary strip at the top of detail pages.
 */
export function StatTile({
    label,
    value,
    icon: Icon,
    accent = 'blue',
    sub,
    className,
}: StatTileProps) {
    return (
        <Card className={cn('gap-0 py-0', className)}>
            <div className="flex items-start gap-3 p-4 sm:p-5">
                {Icon ? (
                    <span
                        className={cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-xl',
                            ACCENTS[accent],
                        )}
                    >
                        <Icon className="size-5" />
                    </span>
                ) : null}
                <div className="flex min-w-0 flex-col gap-0.5">
                    <span className="text-xs font-medium text-muted-foreground">
                        {label}
                    </span>
                    <span className="truncate text-lg font-semibold text-navy">
                        {value}
                    </span>
                    {sub ? (
                        <span className="text-xs text-muted-foreground">
                            {sub}
                        </span>
                    ) : null}
                </div>
            </div>
        </Card>
    );
}
