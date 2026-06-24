import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import EmployeeForm from '@/pages/employees/employee-form';
import type { EmployeeFull, StatusOption } from '@/types/employee';

type EditProps = {
    employee: EmployeeFull;
    statuses: StatusOption[];
};

export default function EmployeesEdit({ employee, statuses }: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Edit Karyawan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Edit Karyawan"
                    description="Perbarui data karyawan."
                />
                <EmployeeForm statuses={statuses} employee={employee} />
            </div>
        </>
    );
}

