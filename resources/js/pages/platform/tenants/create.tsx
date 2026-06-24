import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import TenantForm from '@/pages/platform/tenants/tenant-form';
import type { FeatureOption, StatusOption } from '@/types/tenant';

type CreateProps = {
    statuses: StatusOption[];
    tiers: StatusOption[];
    featureCatalog: FeatureOption[];
    enabledFeatures: string[];
};

export default function TenantsCreate({
    statuses,
    tiers,
    featureCatalog,
    enabledFeatures,
}: CreateProps) {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Tenant" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Tenant"
                    description="Lengkapi data tenant baru."
                />
                <TenantForm
                    statuses={statuses}
                    tiers={tiers}
                    featureCatalog={featureCatalog}
                    enabledFeatures={enabledFeatures}
                />
            </div>
        </>
    );
}
