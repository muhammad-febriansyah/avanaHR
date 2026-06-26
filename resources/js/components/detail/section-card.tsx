import type { LucideProps } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type SectionCardProps = {
    title: string;
    description?: ReactNode;
    icon?: ComponentType<LucideProps>;
    /** Right-aligned slot in the header (badge, button, count…). */
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
};

/**
 * Titled card with an optional leading icon, description and right-aligned
 * actions slot. Standard container for every detail-page section.
 */
export function SectionCard({
    title,
    description,
    icon: Icon,
    actions,
    children,
    className,
    contentClassName,
}: SectionCardProps) {
    return (
        <Card className={className}>
            <CardHeader
                className={cn(
                    actions &&
                        'flex flex-row items-start justify-between gap-3',
                )}
            >
                <div className="flex flex-col gap-1">
                    <CardTitle className="flex items-center gap-2 text-base text-navy">
                        {Icon ? (
                            <Icon className="size-4 text-muted-foreground" />
                        ) : null}
                        {title}
                    </CardTitle>
                    {description ? (
                        <p className="text-xs text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                </div>
                {actions}
            </CardHeader>
            <CardContent className={contentClassName}>{children}</CardContent>
        </Card>
    );
}
