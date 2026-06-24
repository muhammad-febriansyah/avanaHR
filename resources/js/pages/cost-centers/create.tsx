import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import CostCenterForm from '@/pages/cost-centers/cost-center-form';

export default function CostCentersCreate() {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Cost Center" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Cost Center"
                    description="Lengkapi data cost center baru."
                />
                <CostCenterForm />
            </div>
        </>
    );
}
