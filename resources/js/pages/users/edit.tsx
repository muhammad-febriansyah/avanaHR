import { Form, Link } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { Save, ShieldAlert, X } from 'lucide-react';
import { useState } from 'react';
import users from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { useFlashToast } from '@/hooks/use-flash-toast';

type User = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

type EditProps = {
    user: User;
    roles: string[];
    isSelf: boolean;
};

export default function UsersEdit({ user, roles, isSelf }: EditProps) {
    useFlashToast();

    const [selected, setSelected] = useState<string[]>(user.roles);

    const toggle = (role: string, checked: boolean) =>
        setSelected((current) =>
            checked
                ? [...current, role]
                : current.filter((value) => value !== role),
        );

    return (
        <>
            <Head title="Atur Role Pengguna" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Atur Role Pengguna"
                    description={`Tentukan role untuk ${user.name} (${user.email}).`}
                />

                <Form
                    {...users.update.form(user.id)}
                    className="mx-auto w-full max-w-2xl"
                >
                    {({ processing, errors }) => (
                        <Card>
                            <CardContent className="flex flex-col gap-4">
                                {selected.map((role) => (
                                    <input
                                        key={role}
                                        type="hidden"
                                        name="roles[]"
                                        value={role}
                                    />
                                ))}

                                {isSelf && (
                                    <div className="flex items-start gap-2.5 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-warning">
                                        <ShieldAlert className="mt-0.5 size-4 shrink-0" />
                                        <span>
                                            Ini akun Anda sendiri. Role tidak
                                            dapat diubah untuk mencegah
                                            kehilangan akses.
                                        </span>
                                    </div>
                                )}

                                <InputError message={errors.roles} />

                                <div className="flex flex-col gap-2.5">
                                    {roles.map((role) => (
                                        <label
                                            key={role}
                                            className="flex items-center gap-3 rounded-lg border border-border/60 px-3 py-2.5 text-sm hover:bg-muted/50"
                                        >
                                            <Checkbox
                                                checked={selected.includes(
                                                    role,
                                                )}
                                                disabled={isSelf}
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        role,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="font-medium">
                                                {role}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </CardContent>
                            <CardFooter className="flex justify-end gap-3">
                                <Button
                                    asChild
                                    variant="secondary"
                                    type="button"
                                >
                                    <Link href={users.index.url()}>
                                        <X />
                                        Batal
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || isSelf}
                                >
                                    <Save />
                                    Simpan
                                </Button>
                            </CardFooter>
                        </Card>
                    )}
                </Form>
            </div>
        </>
    );
}
