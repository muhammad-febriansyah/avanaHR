import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X } from 'lucide-react';
import type { FormEvent } from 'react';
import shifts from '@/actions/App/Http/Controllers/ShiftController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';

type ShiftForm = {
    code: string;
    name: string;
    start_time: string;
    end_time: string;
    break_min: string;
    is_overnight: boolean;
    late_tolerance_min: string;
    grace_min: string;
};

const emptyForm: ShiftForm = {
    code: '',
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    break_min: '60',
    is_overnight: false,
    late_tolerance_min: '15',
    grace_min: '5',
};

export default function ShiftsCreate() {
    useFlashToast();

    const form = useForm<ShiftForm>(emptyForm);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            break_min: Number(data.break_min),
            late_tolerance_min: Number(data.late_tolerance_min),
            grace_min: Number(data.grace_min),
        }));

        form.post(shifts.store.url());
    }

    return (
        <>
            <Head title="Tambah Shift" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tambah Shift"
                    description="Kelola pola jam kerja dan toleransi keterlambatan."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">
                                        Kode <RequiredMark />
                                    </Label>
                                    <Input
                                        id="code"
                                        value={form.data.code}
                                        onChange={(e) =>
                                            form.setData('code', e.target.value)
                                        }
                                        placeholder="Mis. SH01"
                                        autoFocus
                                    />
                                    <InputError message={form.errors.code} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        Nama <RequiredMark />
                                    </Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        placeholder="Mis. Shift Pagi"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="start_time">
                                        Jam Mulai <RequiredMark />
                                    </Label>
                                    <Input
                                        id="start_time"
                                        type="time"
                                        value={form.data.start_time}
                                        onChange={(e) =>
                                            form.setData(
                                                'start_time',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.start_time}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_time">
                                        Jam Selesai <RequiredMark />
                                    </Label>
                                    <Input
                                        id="end_time"
                                        type="time"
                                        value={form.data.end_time}
                                        onChange={(e) =>
                                            form.setData(
                                                'end_time',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.end_time}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="break_min">
                                        Istirahat (mnt) <RequiredMark />
                                    </Label>
                                    <Input
                                        id="break_min"
                                        type="number"
                                        min={0}
                                        max={1440}
                                        value={form.data.break_min}
                                        onChange={(e) =>
                                            form.setData(
                                                'break_min',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.break_min}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="late_tolerance_min">
                                        Toleransi (mnt) <RequiredMark />
                                    </Label>
                                    <Input
                                        id="late_tolerance_min"
                                        type="number"
                                        min={0}
                                        max={1440}
                                        value={form.data.late_tolerance_min}
                                        onChange={(e) =>
                                            form.setData(
                                                'late_tolerance_min',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.late_tolerance_min}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="grace_min">
                                        Grace (mnt) <RequiredMark />
                                    </Label>
                                    <Input
                                        id="grace_min"
                                        type="number"
                                        min={0}
                                        max={1440}
                                        value={form.data.grace_min}
                                        onChange={(e) =>
                                            form.setData(
                                                'grace_min',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.grace_min}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <label className="flex items-center gap-2.5 text-sm">
                                        <Checkbox
                                            checked={form.data.is_overnight}
                                            onCheckedChange={(value) =>
                                                form.setData(
                                                    'is_overnight',
                                                    value === true,
                                                )
                                            }
                                        />
                                        Shift lintas hari (overnight)
                                    </label>
                                    <InputError
                                        message={form.errors.is_overnight}
                                    />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={shifts.index.url()}>
                                    <X />
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Simpan
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
