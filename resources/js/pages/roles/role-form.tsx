import { Form, Link } from '@inertiajs/react';
import { Lock, Save, X } from 'lucide-react';
import { useState } from 'react';
import roles from '@/actions/App/Http/Controllers/RoleController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type PermissionOption = { name: string; label: string };
export type PermissionGroup = {
    key: string;
    label: string;
    permissions: PermissionOption[];
};

export type Role = {
    id: number;
    name: string;
    is_system: boolean;
    permissions: string[];
};

type RoleFormProps = {
    permissionGroups: PermissionGroup[];
    role?: Role;
};

/**
 * Shared create/edit form for an RBAC role with a grouped permission matrix.
 */
export default function RoleForm({ permissionGroups, role }: RoleFormProps) {
    const formProps = role ? roles.update.form(role.id) : roles.store.form();
    const isSystem = role?.is_system ?? false;

    const [selected, setSelected] = useState<string[]>(role?.permissions ?? []);

    const toggle = (name: string, checked: boolean) =>
        setSelected((current) =>
            checked
                ? [...current, name]
                : current.filter((value) => value !== name),
        );

    const toggleGroup = (group: PermissionGroup, checked: boolean) =>
        setSelected((current) => {
            const names = group.permissions.map((p) => p.name);

            return checked
                ? [...new Set([...current, ...names])]
                : current.filter((value) => !names.includes(value));
        });

    return (
        <Form {...formProps} className="mx-auto w-full max-w-4xl">
            {({ processing, errors }) => (
                <Card>
                    <CardContent className="flex flex-col gap-6">
                        {selected.map((name) => (
                            <input
                                key={name}
                                type="hidden"
                                name="permissions[]"
                                value={name}
                            />
                        ))}

                        <div className="grid max-w-md gap-2">
                            <Label htmlFor="name">
                                Nama Role <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={role?.name ?? ''}
                                required
                                readOnly={isSystem}
                                placeholder="mis. supervisor-cabang"
                                className={
                                    isSystem ? 'bg-muted text-muted-foreground' : ''
                                }
                            />
                            {isSystem && (
                                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Lock className="size-3" />
                                    Role bawaan sistem — nama terkunci, hanya
                                    hak akses yang dapat diubah.
                                </p>
                            )}
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Hak Akses</Label>
                            <p className="text-xs text-muted-foreground">
                                Pilih izin yang dimiliki role ini. Menu &
                                tombol akan menyesuaikan otomatis.
                            </p>
                            <InputError message={errors.permissions} />
                            <div className="grid gap-3 sm:grid-cols-2">
                                {permissionGroups.map((group) => {
                                    const names = group.permissions.map(
                                        (p) => p.name,
                                    );
                                    const allChecked = names.every((n) =>
                                        selected.includes(n),
                                    );

                                    return (
                                        <div
                                            key={group.key}
                                            className="rounded-lg border border-border/60 p-4"
                                        >
                                            <label className="flex items-center gap-2.5 border-b border-border/60 pb-2.5 text-sm font-medium">
                                                <Checkbox
                                                    checked={allChecked}
                                                    onCheckedChange={(checked) =>
                                                        toggleGroup(
                                                            group,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                {group.label}
                                            </label>
                                            <div className="mt-2.5 flex flex-col gap-2">
                                                {group.permissions.map((p) => (
                                                    <label
                                                        key={p.name}
                                                        className="flex items-center gap-2.5 text-sm"
                                                    >
                                                        <Checkbox
                                                            checked={selected.includes(
                                                                p.name,
                                                            )}
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                toggle(
                                                                    p.name,
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />
                                                        <span>{p.label}</span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {p.name}
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={roles.index.url()}>
                                <X />
                                Batal
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            Simpan
                        </Button>
                    </CardFooter>
                </Card>
            )}
        </Form>
    );
}
