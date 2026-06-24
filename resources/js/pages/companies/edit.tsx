import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import CompanyForm, { type Company } from '@/pages/companies/company-form';

type EditProps = {
    company: Company;
};

export default function CompaniesEdit({ company }: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Ubah Perusahaan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ubah Perusahaan"
                    description={`Perbarui data ${company.name}.`}
                />
                <CompanyForm company={company} />
            </div>
        </>
    );
}
