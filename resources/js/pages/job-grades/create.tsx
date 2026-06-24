import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import JobGradeForm from '@/pages/job-grades/job-grade-form';

export default function JobGradesCreate() {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Grade Jabatan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Grade Jabatan"
                    description="Lengkapi data grade jabatan dan rentang gaji."
                />
                <JobGradeForm />
            </div>
        </>
    );
}
