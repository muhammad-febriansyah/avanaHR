import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import { useState } from 'react';
import tenants from '@/actions/App/Http/Controllers/Platform/TenantController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { StatusOption, TenantFull } from '@/types/tenant';

type FeatureOption = { key: string; label: string };

type TenantFormProps = {
    statuses: StatusOption[];
    tiers: StatusOption[];
    featureCatalog: FeatureOption[];
    enabledFeatures: string[];
    tenant?: TenantFull;
};

/**
 * Shared create/edit form for a tenant.
 */
export default function TenantForm({
    statuses,
    tiers,
    featureCatalog,
    enabledFeatures,
    tenant,
}: TenantFormProps) {
    const formProps = tenant
        ? tenants.update.form(tenant.id)
        : tenants.store.form();

    const [status, setStatus] = useState(tenant?.status ?? '');
    const [tier, setTier] = useState(tenant?.subscription?.tier ?? '');
    const [features, setFeatures] = useState<string[]>(enabledFeatures);

    const toggleFeature = (key: string, checked: boolean) =>
        setFeatures((current) =>
            checked
                ? [...current, key]
                : current.filter((value) => value !== key),
        );

    return (
        <Form {...formProps} className="mx-auto w-full max-w-4xl">
            {({ processing, errors }) => (
                <Card>
                    <CardContent className="grid gap-5 md:grid-cols-2">
                        <input type="hidden" name="status" value={status} />
                        <input type="hidden" name="tier" value={tier} />
                        {features.map((key) => (
                            <input
                                key={key}
                                type="hidden"
                                name="features[]"
                                value={key}
                            />
                        ))}

                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nama <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={tenant?.name ?? ''}
                                required
                                placeholder="PT Avana Sejahtera"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="slug">
                                Slug <RequiredMark />
                            </Label>
                            <Input
                                id="slug"
                                name="slug"
                                defaultValue={tenant?.slug ?? ''}
                                required
                                placeholder="avana"
                            />
                            <InputError message={errors.slug} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="locale">Locale</Label>
                            <Input
                                id="locale"
                                name="locale"
                                defaultValue={tenant?.locale ?? 'id'}
                                placeholder="id"
                            />
                            <InputError message={errors.locale} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="timezone">Timezone</Label>
                            <Input
                                id="timezone"
                                name="timezone"
                                defaultValue={
                                    tenant?.timezone ?? 'Asia/Jakarta'
                                }
                                placeholder="Asia/Jakarta"
                            />
                            <InputError message={errors.timezone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="currency">Currency</Label>
                            <Input
                                id="currency"
                                name="currency"
                                defaultValue={tenant?.currency ?? 'IDR'}
                                placeholder="IDR"
                            />
                            <InputError message={errors.currency} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">
                                Status <RequiredMark />
                            </Label>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger id="status" className="w-full">
                                    <SelectValue placeholder="Pilih status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tier">
                                Paket Langganan <RequiredMark />
                            </Label>
                            <Select value={tier} onValueChange={setTier}>
                                <SelectTrigger id="tier" className="w-full">
                                    <SelectValue placeholder="Pilih paket" />
                                </SelectTrigger>
                                <SelectContent>
                                    {tiers.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tier} />
                        </div>

                        <div className="grid gap-3 md:col-span-2">
                            <div>
                                <Label>Fitur / Modul Aktif</Label>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Pilih modul yang aktif untuk tenant ini.
                                    Modul yang nonaktif disembunyikan dari menu
                                    penggunanya.
                                </p>
                            </div>
                            <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                {featureCatalog.map((option) => (
                                    <label
                                        key={option.key}
                                        className="flex items-center gap-2.5 rounded-lg border border-border/60 px-3 py-2.5 text-sm hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            checked={features.includes(
                                                option.key,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleFeature(
                                                    option.key,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span>{option.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={tenants.index.url()}>
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
