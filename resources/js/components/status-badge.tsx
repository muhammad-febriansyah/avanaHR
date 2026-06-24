import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type StatusBadgeProps = {
    status: string;
};

type StatusConfig = {
    label: string;
    className: string;
};

const STATUS_MAP: Record<string, StatusConfig> = {
    active: {
        label: 'Aktif',
        className: 'border-transparent bg-green-100 text-green-700',
    },
    probation: {
        label: 'Masa Percobaan',
        className: 'border-transparent bg-amber-100 text-amber-700',
    },
    on_leave: {
        label: 'Cuti',
        className: 'border-transparent bg-blue-100 text-blue-700',
    },
    suspended: {
        label: 'Diskors',
        className: 'border-transparent bg-orange-100 text-orange-700',
    },
    resigned: {
        label: 'Mengundurkan Diri',
        className: 'border-transparent bg-gray-100 text-gray-700',
    },
    terminated: {
        label: 'Diberhentikan',
        className: 'border-transparent bg-red-100 text-red-700',
    },
};

/**
 * Maps an employee status string to a colored badge.
 */
export default function StatusBadge({ status }: StatusBadgeProps) {
    const config = STATUS_MAP[status] ?? {
        label: status,
        className: 'border-transparent bg-gray-100 text-gray-700',
    };

    return (
        <Badge variant="outline" className={cn(config.className)}>
            {config.label}
        </Badge>
    );
}
