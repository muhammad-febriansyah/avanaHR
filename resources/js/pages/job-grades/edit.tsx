import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import JobGradeForm from '@/pages/job-grades/job-grade-form';
import type {JobGrade} from '@/pages/job-grades/job-grade-form';

type EditProps = {
    jobGrade: JobGrade;
};

export default function JobGradesEdit({ jobGrade }: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Ubah Grade Jabatan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ubah Grade Jabatan"
                    description={`Perbarui data ${jobGrade.name}.`}
                />
                <JobGradeForm jobGrade={jobGrade} />
            </div>
        </>
    );
}
