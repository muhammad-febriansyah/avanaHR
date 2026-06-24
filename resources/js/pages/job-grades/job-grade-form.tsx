import { Form, Link } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import { useState } from 'react';
import jobGrades from '@/actions/App/Http/Controllers/JobGradeController';
import InputError from '@/components/input-error';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';

export type JobGrade = {
    id: number;
    code: string;
    name: string;
    salary_band_min: number;
    salary_band_max: number;
};

type JobGradeFormProps = {
    jobGrade?: JobGrade;
};

/**
 * Shared create/edit form for a job grade (grade jabatan) with salary band.
 */
export default function JobGradeForm({ jobGrade }: JobGradeFormProps) {
    const formProps = jobGrade
        ? jobGrades.update.form(jobGrade.id)
        : jobGrades.store.form();

    const [min, setMin] = useState(String(jobGrade?.salary_band_min ?? ''));
    const [max, setMax] = useState(String(jobGrade?.salary_band_max ?? ''));

    return (
        <Form {...formProps} className="mx-auto w-full max-w-3xl">
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
                                defaultValue={jobGrade?.code ?? ''}
                                required
                                placeholder="GR1"
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
                                defaultValue={jobGrade?.name ?? ''}
                                required
                                placeholder="Grade A"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="salary_band_min">
                                Batas Bawah Gaji (Rp) <RequiredMark />
                            </Label>
                            <Input
                                id="salary_band_min"
                                name="salary_band_min"
                                type="number"
                                min={0}
                                step={1}
                                value={min}
                                onChange={(event) => setMin(event.target.value)}
                                required
                                placeholder="5000000"
                            />
                            <p className="text-xs text-muted-foreground tabular-nums">
                                {formatRupiah(Number(min) || 0)}
                            </p>
                            <InputError message={errors.salary_band_min} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="salary_band_max">
                                Batas Atas Gaji (Rp) <RequiredMark />
                            </Label>
                            <Input
                                id="salary_band_max"
                                name="salary_band_max"
                                type="number"
                                min={0}
                                step={1}
                                value={max}
                                onChange={(event) => setMax(event.target.value)}
                                required
                                placeholder="10000000"
                            />
                            <p className="text-xs text-muted-foreground tabular-nums">
                                {formatRupiah(Number(max) || 0)}
                            </p>
                            <InputError message={errors.salary_band_max} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex justify-end gap-3">
                        <Button asChild variant="outline" type="button">
                            <Link href={jobGrades.index.url()}>
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
