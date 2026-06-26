import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import jobLevels from '@/actions/App/Http/Controllers/JobLevelController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type JobLevel = {
    id: number;
    code: string;
    name: string;
    order: number;
};

type JobLevelFormProps = {
    jobLevel?: JobLevel;
};

/**
 * Shared create/edit form for a job level (jenjang jabatan).
 */
export default function JobLevelForm({ jobLevel }: JobLevelFormProps) {
    const formProps = jobLevel
        ? jobLevels.update.form(jobLevel.id)
        : jobLevels.store.form();

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
                                defaultValue={jobLevel?.code ?? ''}
                                required
                                placeholder="LV1"
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
                                defaultValue={jobLevel?.name ?? ''}
                                required
                                placeholder="Staff"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="order">Urutan</Label>
                            <Input
                                id="order"
                                name="order"
                                type="number"
                                min={0}
                                defaultValue={jobLevel?.order ?? 0}
                                placeholder="1"
                            />
                            <InputError message={errors.order} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="secondary" type="button">
                            <Link href={jobLevels.index.url()}>
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
