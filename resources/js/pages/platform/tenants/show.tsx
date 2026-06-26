import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    CreditCard,
    Globe,
    Pencil,
    UserCog,
    Users,
} from 'lucide-react';
import tenants from '@/actions/App/Http/Controllers/Platform/TenantController';
import { DetailItem } from '@/components/detail/detail-item';
import { InfoHero } from '@/components/detail/info-hero';
import { SectionCard } from '@/components/detail/section-card';
import { StatTile } from '@/components/detail/stat-tile';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
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
                    <Button asChild variant="secondary">
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
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.post(
                                `/platform/tenants/${tenant.id}/impersonate`,
                            )
                        }
                    >
                        <UserCog />
                        Masuk sebagai Tenant
                    </Button>
                </PageHeader>

                <InfoHero
                    initials={initials}
                    title={tenant.name}
                    subtitle={tenant.slug}
                    badges={<TenantStatusPill status={tenant.status} />}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Jumlah Karyawan"
                        value={tenant.employees_count ?? 0}
                        icon={Users}
                        accent="blue"
                    />
                    <StatTile
                        label="Paket"
                        value={
                            subscription?.tier
                                ? capitalize(subscription.tier)
                                : '—'
                        }
                        icon={CreditCard}
                        accent="violet"
                        sub={subscription?.status ?? undefined}
                    />
                    <StatTile
                        label="Mata Uang"
                        value={tenant.currency}
                        icon={Globe}
                        accent="green"
                        sub={tenant.locale}
                    />
                    <StatTile
                        label="Dibuat"
                        value={formatDateID(tenant.created_at)}
                        icon={CalendarDays}
                        accent="amber"
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <SectionCard
                        title="Informasi Tenant"
                        icon={Building2}
                        className="lg:col-span-2"
                        contentClassName="grid gap-3 sm:grid-cols-2"
                    >
                        <DetailItem label="Nama" value={tenant.name} />
                        <DetailItem label="Slug" value={tenant.slug} />
                        <DetailItem
                            label="Locale"
                            value={tenant.locale}
                            icon={Globe}
                        />
                        <DetailItem label="Timezone" value={tenant.timezone} />
                        <DetailItem label="Currency" value={tenant.currency} />
                        <DetailItem
                            label="Dibuat"
                            value={formatDateID(tenant.created_at)}
                            icon={CalendarDays}
                        />
                    </SectionCard>

                    <SectionCard
                        title="Langganan"
                        icon={CreditCard}
                        contentClassName="grid gap-3"
                    >
                        <DetailItem
                            label="Paket"
                            value={
                                subscription?.tier
                                    ? capitalize(subscription.tier)
                                    : null
                            }
                        />
                        <DetailItem
                            label="Status"
                            value={subscription?.status}
                        />
                        <DetailItem
                            label="Mulai"
                            value={formatDateID(subscription?.starts_at)}
                        />
                        <DetailItem
                            label="Berakhir"
                            value={formatDateID(subscription?.ends_at)}
                        />
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
