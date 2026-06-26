import { Head, useForm } from '@inertiajs/react';
import { KeyRound, Save, Timer } from 'lucide-react';
import type { FormEvent } from 'react';
import securitySettings from '@/actions/App/Http/Controllers/SecuritySettingController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Settings = {
    password_min_length: number;
    password_require_uppercase: boolean;
    password_require_number: boolean;
    password_require_symbol: boolean;
    password_expiry_days: number;
    session_timeout_minutes: number;
    max_login_attempts: number;
    lockout_minutes: number;
    enforce_2fa: boolean;
};

type EditProps = {
    settings: Settings;
};

export default function SecuritySettingsEdit({ settings }: EditProps) {
    const form = useForm<Settings>({ ...settings });

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        form.put(securitySettings.update.url(), { preserveScroll: true });
    }

    function toggle(field: keyof Settings) {
        return (checked: boolean | 'indeterminate') =>
            form.setData(field, (checked === true) as never);
    }

    function num(field: keyof Settings) {
        return (event: React.ChangeEvent<HTMLInputElement>) =>
            form.setData(field, Number(event.target.value) as never);
    }

    return (
        <>
            <Head title="Pengaturan Keamanan" />

            <form
                onSubmit={handleSubmit}
                className="flex flex-1 flex-col gap-5 p-4 md:p-6"
            >
                <PageHeader
                    title="Pengaturan Keamanan"
                    description="Kebijakan keamanan tingkat tenant: sandi & sesi login."
                >
                    <Button type="submit" disabled={form.processing}>
                        <Save />
                        Simpan
                    </Button>
                </PageHeader>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <KeyRound className="size-4" />
                            Kebijakan Kata Sandi
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="min-length">Panjang minimal</Label>
                            <Input
                                id="min-length"
                                type="number"
                                min="6"
                                max="64"
                                value={form.data.password_min_length}
                                onChange={num('password_min_length')}
                            />
                            {form.errors.password_min_length && (
                                <p className="text-sm text-destructive">
                                    {form.errors.password_min_length}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="expiry">
                                Masa berlaku (hari, 0 = tanpa batas)
                            </Label>
                            <Input
                                id="expiry"
                                type="number"
                                min="0"
                                max="365"
                                value={form.data.password_expiry_days}
                                onChange={num('password_expiry_days')}
                            />
                        </div>
                        <div className="flex flex-col gap-2 sm:col-span-2">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={
                                        form.data.password_require_uppercase
                                    }
                                    onCheckedChange={toggle(
                                        'password_require_uppercase',
                                    )}
                                />
                                Wajib huruf kapital
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.password_require_number}
                                    onCheckedChange={toggle(
                                        'password_require_number',
                                    )}
                                />
                                Wajib angka
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.password_require_symbol}
                                    onCheckedChange={toggle(
                                        'password_require_symbol',
                                    )}
                                />
                                Wajib simbol
                            </label>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Timer className="size-4" />
                            Sesi & Percobaan Login
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-5 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="timeout">
                                Timeout sesi (menit)
                            </Label>
                            <Input
                                id="timeout"
                                type="number"
                                min="5"
                                max="1440"
                                value={form.data.session_timeout_minutes}
                                onChange={num('session_timeout_minutes')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="attempts">
                                Maks. percobaan login
                            </Label>
                            <Input
                                id="attempts"
                                type="number"
                                min="3"
                                max="20"
                                value={form.data.max_login_attempts}
                                onChange={num('max_login_attempts')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="lockout">
                                Durasi kunci (menit)
                            </Label>
                            <Input
                                id="lockout"
                                type="number"
                                min="1"
                                max="1440"
                                value={form.data.lockout_minutes}
                                onChange={num('lockout_minutes')}
                            />
                        </div>
                    </CardContent>
                </Card>
            </form>
        </>
    );
}
