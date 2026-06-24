import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Fragment  } from 'react';
import type {ReactNode} from 'react';
import type { BreadcrumbItem } from '@/types';

type PageHeaderProps = {
    title: string;
    description?: string;
    children?: ReactNode;
};

/**
 * Page heading with an in-content breadcrumb (read from shared props),
 * an optional description, and a right-aligned actions slot.
 */
export default function PageHeader({
    title,
    description,
    children,
}: PageHeaderProps) {
    const breadcrumbs =
        (usePage().props.breadcrumbs as BreadcrumbItem[] | undefined) ?? [];

    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
                {breadcrumbs.length > 0 && (
                    <nav
                        aria-label="Breadcrumb"
                        className="mb-2 flex flex-wrap items-center gap-1.5 text-[12.5px] text-muted-foreground"
                    >
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;

                            return (
                                <Fragment key={index}>
                                    {index > 0 && (
                                        <ChevronRight className="size-3.5" />
                                    )}
                                    {isLast ? (
                                        <span className="text-foreground">
                                            {item.title}
                                        </span>
                                    ) : (
                                        <Link
                                            href={item.href}
                                            className="transition-colors hover:text-foreground"
                                        >
                                            {item.title}
                                        </Link>
                                    )}
                                </Fragment>
                            );
                        })}
                    </nav>
                )}
                <h1 className="text-2xl font-semibold tracking-tight text-navy">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children && (
                <div className="flex flex-wrap items-center gap-2.5">
                    {children}
                </div>
            )}
        </div>
    );
}
