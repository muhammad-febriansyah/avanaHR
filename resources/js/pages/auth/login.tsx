import { Form, Head } from '@inertiajs/react';
import { Lock, LockKeyhole, LogIn, Mail } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { RequiredMark } from '@/components/required-mark';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="email">
                                Email <RequiredMark />
                            </Label>
                            <div className="relative">
                                <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="nama@perusahaan.co.id"
                                    className="h-11 pl-10"
                                />
                            </div>
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-1.5">
                            <div className="flex items-center">
                                <Label htmlFor="password">
                                    Kata Sandi <RequiredMark />
                                </Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                        tabIndex={5}
                                    >
                                        Lupa sandi?
                                    </TextLink>
                                )}
                            </div>
                            <div className="relative">
                                <Lock className="pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    className="h-11 pl-10"
                                />
                            </div>
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-2.5">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label
                                htmlFor="remember"
                                className="text-sm font-normal text-muted-foreground"
                            >
                                Ingat saya
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="h-11 w-full shadow-sm transition-shadow hover:shadow-md"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing ? <Spinner /> : <LogIn />}
                            Masuk
                        </Button>

                        <p className="flex items-center justify-center gap-1.5 text-xs text-muted-foreground">
                            <LockKeyhole className="size-3.5" />
                            Koneksi aman & terenkripsi
                        </p>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-success">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Masuk ke akun Anda',
    description: 'Kelola karyawan, absensi, dan payroll dalam satu platform.',
};
