import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import { useFlashToast } from '@/hooks/use-flash-toast';
import RoleForm from '@/pages/roles/role-form';
import type {PermissionGroup, Role} from '@/pages/roles/role-form';

type EditProps = {
    role: Role;
    permissionGroups: PermissionGroup[];
};

export default function RolesEdit({ role, permissionGroups }: EditProps) {
    useFlashToast();

    return (
        <>
            <Head title="Ubah Role" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ubah Role"
                    description={`Perbarui hak akses role ${role.name}.`}
                />
                <RoleForm permissionGroups={permissionGroups} role={role} />
            </div>
        </>
    );
}
