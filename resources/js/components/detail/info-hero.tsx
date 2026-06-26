import type { LucideProps } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type InfoHeroProps = {
    /** Initials for the gradient avatar. Omit to use `icon` instead. */
    initials?: string;
    icon?: ComponentType<LucideProps>;
    title: ReactNode;
    subtitle?: ReactNode;
    /** Badges / pills rendered beside the title. */
    badges?: ReactNode;
    /** Right-aligned headline slot (a metric, an action, a status block). */
    aside?: ReactNode;
    className?: string;
};

/**
 * Hero banner for detail pages: gradient avatar (initials or icon), title with
 * inline badges, a muted subtitle, and an optional right-aligned aside.
 */
export function InfoHero({
    initials,
    icon: Icon,
    title,
    subtitle,
    badges,
    aside,
    className,
}: InfoHeroProps) {
    return (
        <Card className={cn('overflow-hidden py-0', className)}>
            <CardContent className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                <span className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-xl font-semibold text-white">
                    {Icon ? <Icon className="size-7" /> : initials}
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2.5">
                        <h2 className="text-lg font-semibold text-navy">
                            {title}
                        </h2>
                        {badges}
                    </div>
                    {subtitle ? (
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {subtitle}
                        </p>
                    ) : null}
                </div>
                {aside ? (
                    <div className="shrink-0 sm:text-right">{aside}</div>
                ) : null}
            </CardContent>
        </Card>
    );
}
