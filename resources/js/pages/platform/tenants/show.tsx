import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import type { ReactNode } from 'react';
import tenants from '@/actions/App/Http/Controllers/Platform/TenantController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDateID } from '@/lib/format';
import type { TenantFull } from '@/types/tenant';

type ShowProps = {
    tenant: TenantFull;
};

const STATUS_PILL: Record<string, { label: string; className: string }> = {
    active: {
        label: 'Aktif',
        className: 'bg-emerald-100 text-emerald-700',
    },
    suspended: {
        label: 'Ditangguhkan',
        className: 'bg-amber-100 text-amber-700',
    },
    inactive: {
        label: 'Nonaktif',
        className: 'bg-muted text-muted-foreground',
    },
};

function TenantStatusPill({ status }: { status: string }) {
    const pill = STATUS_PILL[status] ?? {
        label: status,
        className: 'bg-muted text-muted-foreground',
    };

    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${pill.className}`}
        >
            {pill.label}
        </span>
    );
}

function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function DetailRow({ label, value }: { label: string; value: ReactNode }) {
    const display =
        value === null || value === undefined || value === '' ? '-' : value;

    return (
        <div className="flex flex-col gap-1 border-b border-border/50 pb-3 last:border-0 last:pb-0">
            <span className="text-xs text-muted-foreground">{label}</span>
            <span className="text-sm font-medium text-foreground">
                {display}
            </span>
        </div>
    );
}

export default function TenantsShow({ tenant }: ShowProps) {
    useFlashToast();

    const initials = tenant.name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    const subscription = tenant.subscription;

    return (
        <>
            <Head title={tenant.name} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader title="Detail Tenant">
                    <Button asChild variant="outline">
                        <Link href={tenants.index.url()}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <Button asChild variant="success">
                        <Link href={tenants.edit.url(tenant.id)}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                </PageHeader>

                {/* Hero */}
                <Card>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <span className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#2F54C9,#6E9BE6)] text-xl font-semibold text-white">
                            {initials}
                        </span>
                        <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-2.5">
                                <h2 className="text-lg font-semibold text-navy">
                                    {tenant.name}
                                </h2>
                                <TenantStatusPill status={tenant.status} />
                            </div>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {tenant.slug}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-5 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Informasi Tenant
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            <DetailRow label="Slug" value={tenant.slug} />
                            <DetailRow label="Locale" value={tenant.locale} />
                            <DetailRow
                                label="Timezone"
                                value={tenant.timezone}
                            />
                            <DetailRow
                                label="Currency"
                                value={tenant.currency}
                            />
                            <DetailRow
                                label="Dibuat"
                                value={formatDateID(tenant.created_at)}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Langganan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <DetailRow
                                label="Paket"
                                value={
                                    subscription?.tier
                                        ? capitalize(subscription.tier)
                                        : '-'
                                }
                            />
                            <DetailRow
                                label="Status"
                                value={subscription?.status}
                            />
                            <DetailRow
                                label="Mulai"
                                value={formatDateID(subscription?.starts_at)}
                            />
                            <DetailRow
                                label="Berakhir"
                                value={formatDateID(subscription?.ends_at)}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base text-navy">
                                Statistik
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <DetailRow
                                label="Jumlah Karyawan"
                                value={tenant.employees_count ?? 0}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
