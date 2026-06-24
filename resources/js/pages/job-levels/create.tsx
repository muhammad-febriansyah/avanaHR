import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import JobLevelForm from '@/pages/job-levels/job-level-form';

export default function JobLevelsCreate() {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Jenjang Jabatan" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Jenjang Jabatan"
                    description="Lengkapi data jenjang jabatan baru."
                />
                <JobLevelForm />
            </div>
        </>
    );
}
