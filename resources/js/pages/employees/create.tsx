import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import EmployeeForm from '@/pages/employees/employee-form';
import type {CustomFieldDef} from '@/pages/employees/employee-form';
import type { StatusOption } from '@/types/employee';

type CreateProps = {
    statuses: StatusOption[];
    customFields: CustomFieldDef[];
};

export default function EmployeesCreate({
    statuses,
    customFields,
}: CreateProps) {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Karyawan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Karyawan"
                    description="Lengkapi data karyawan baru."
                />
                <EmployeeForm statuses={statuses} customFields={customFields} />
            </div>
        </>
    );
}
