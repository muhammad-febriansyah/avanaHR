import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import companies from '@/actions/App/Http/Controllers/CompanyController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type Company = {
    id: number;
    code: string;
    name: string;
    npwp: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
};

type CompanyFormProps = {
    company?: Company;
};

/**
 * Shared create/edit form for a company (perusahaan).
 */
export default function CompanyForm({ company }: CompanyFormProps) {
    const formProps = company
        ? companies.update.form(company.id)
        : companies.store.form();

    return (
        <Form {...formProps} className="mx-auto w-full max-w-4xl">
            {({ processing, errors }) => (
                <Card>
                    <CardContent className="grid gap-5 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="code">
                                Kode <RequiredMark />
                            </Label>
                            <Input
                                id="code"
                                name="code"
                                defaultValue={company?.code ?? ''}
                                required
                                placeholder="CMP001"
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nama Perusahaan <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={company?.name ?? ''}
                                required
                                placeholder="PT Avana Sejahtera"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="npwp">NPWP</Label>
                            <Input
                                id="npwp"
                                name="npwp"
                                defaultValue={company?.npwp ?? ''}
                                placeholder="01.234.567.8-901.000"
                            />
                            <InputError message={errors.npwp} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telepon</Label>
                            <Input
                                id="phone"
                                name="phone"
                                defaultValue={company?.phone ?? ''}
                                placeholder="021-1234567"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={company?.email ?? ''}
                                placeholder="info@avana.id"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="address">Alamat</Label>
                            <textarea
                                id="address"
                                name="address"
                                defaultValue={company?.address ?? ''}
                                rows={3}
                                placeholder="Jl. Sudirman No. 1, Jakarta"
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={errors.address} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={companies.index.url()}>
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
