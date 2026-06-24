import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import costCenters from '@/actions/App/Http/Controllers/CostCenterController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type CostCenter = {
    id: number;
    code: string;
    name: string;
};

type CostCenterFormProps = {
    costCenter?: CostCenter;
};

/**
 * Shared create/edit form for a cost center.
 */
export default function CostCenterForm({ costCenter }: CostCenterFormProps) {
    const formProps = costCenter
        ? costCenters.update.form(costCenter.id)
        : costCenters.store.form();

    return (
        <Form {...formProps} className="mx-auto w-full max-w-2xl">
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
                                defaultValue={costCenter?.code ?? ''}
                                required
                                placeholder="CC100"
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nama <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={costCenter?.name ?? ''}
                                required
                                placeholder="Operasional HQ"
                            />
                            <InputError message={errors.name} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={costCenters.index.url()}>
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
