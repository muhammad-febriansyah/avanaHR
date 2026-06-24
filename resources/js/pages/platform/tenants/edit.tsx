import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import TenantForm from '@/pages/platform/tenants/tenant-form';
import type {
    FeatureOption,
    StatusOption,
    TenantFull,
} from '@/types/tenant';

type EditProps = {
    tenant: TenantFull;
    statuses: StatusOption[];
    tiers: StatusOption[];
    featureCatalog: FeatureOption[];
    enabledFeatures: string[];
};

export default function TenantsEdit({
    tenant,
    statuses,
    tiers,
    featureCatalog,
    enabledFeatures,
}: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Edit Tenant" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Tenant"
                    description="Perbarui data tenant."
                />
                <TenantForm
                    statuses={statuses}
                    tiers={tiers}
                    featureCatalog={featureCatalog}
                    enabledFeatures={enabledFeatures}
                    tenant={tenant}
                />
            </div>
        </>
    );
}
