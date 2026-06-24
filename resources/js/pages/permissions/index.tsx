import { Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import PageHeader from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';
import type { PermissionGroup } from '@/pages/roles/role-form';

type IndexProps = {
    permissionGroups: PermissionGroup[];
};

export default function PermissionsIndex({ permissionGroups }: IndexProps) {
    return (
        <>
            <Head title="Permission" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Permission"
                    description="Daftar seluruh izin sistem yang dapat ditetapkan ke role."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {permissionGroups.map((group) => (
                        <Card key={group.key} className="gap-0 py-0">
                            <CardContent className="flex flex-col gap-3 p-5">
                                <div className="flex items-center gap-2 border-b border-border/60 pb-3 font-medium text-navy">
                                    <KeyRound className="size-4 text-primary" />
                                    {group.label}
                                </div>
                                <ul className="flex flex-col gap-2">
                                    {group.permissions.map((p) => (
                                        <li
                                            key={p.name}
                                            className="flex items-center justify-between gap-3 text-sm"
                                        >
                                            <span>{p.label}</span>
                                            <code className="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                                {p.name}
                                            </code>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
