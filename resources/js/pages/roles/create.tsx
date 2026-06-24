import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import RoleForm, { type PermissionGroup } from '@/pages/roles/role-form';

type CreateProps = {
    permissionGroups: PermissionGroup[];
};

export default function RolesCreate({ permissionGroups }: CreateProps) {
    useFlashToast();

    return (
        <>
            <Head title="Tambah Role" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Role"
                    description="Buat role baru dan tentukan hak aksesnya."
                />
                <RoleForm permissionGroups={permissionGroups} />
            </div>
        </>
    );
}
