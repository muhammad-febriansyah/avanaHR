import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import CompanyForm from '@/pages/companies/company-form';

export default function CompaniesCreate() {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Perusahaan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Perusahaan"
                    description="Lengkapi data perusahaan baru."
                />
                <CompanyForm />
            </div>
        </>
    );
}
