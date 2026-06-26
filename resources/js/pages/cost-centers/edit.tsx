import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import CostCenterForm from '@/pages/cost-centers/cost-center-form';
import type { CostCenter } from '@/pages/cost-centers/cost-center-form';

type EditProps = {
    costCenter: CostCenter;
};

export default function CostCentersEdit({ costCenter }: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Ubah Cost Center" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ubah Cost Center"
                    description={`Perbarui data ${costCenter.name}.`}
                />
                <CostCenterForm costCenter={costCenter} />
            </div>
        </>
    );
}
