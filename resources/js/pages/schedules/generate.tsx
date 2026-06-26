import { Head, Link, useForm } from '@inertiajs/react';
import { Wand2, X } from 'lucide-react';
import type { FormEvent } from 'react';
import schedules from '@/actions/App/Http/Controllers/ScheduleController';
import PageHeader from '@/components/page-header';
import { RequiredMark } from '@/components/required-mark';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Option = { id: number; label: string };

type GenerateProps = {
    employees: Option[];
    patterns: Option[];
};

export default function SchedulesGenerate({
    employees,
    patterns,
}: GenerateProps) {
    useFlashToast();

    const form = useForm<{
        pattern_id: string;
        employee_ids: number[];
        start_date: string;
        end_date: string;
    }>({ pattern_id: '', employee_ids: [], start_date: '', end_date: '' });

    function toggle(id: number) {
        form.setData(
            'employee_ids',
            form.data.employee_ids.includes(id)
                ? form.data.employee_ids.filter((e) => e !== id)
                : [...form.data.employee_ids, id],
        );
    }

    function toggleAll() {
        form.setData(
            'employee_ids',
            form.data.employee_ids.length === employees.length
                ? []
                : employees.map((e) => e.id),
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(schedules.generate.url());
    }

    return (
        <>
            <Head title="Generate Roster" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Generate Roster dari Pola"
                    description="Buat jadwal shift massal untuk banyak karyawan sekaligus dari pola rotasi."
                >
                    <Button asChild variant="outline">
                        <Link href={schedules.index.url()}>
                            <X />
                            Batal
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Card>
                        <CardContent className="grid gap-5 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label>
                                    Pola Shift <RequiredMark />
                                </Label>
                                <Select
                                    value={form.data.pattern_id}
                                    onValueChange={(v) =>
                                        form.setData('pattern_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih pola" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {patterns.map((p) => (
                                            <SelectItem
                                                key={p.id}
                                                value={String(p.id)}
                                            >
                                                {p.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.pattern_id && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.pattern_id}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label>
                                    Mulai <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={form.data.start_date}
                                    onChange={(v) =>
                                        form.setData('start_date', v)
                                    }
                                    placeholder="Tanggal mulai"
                                />
                                {form.errors.start_date && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.start_date}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label>
                                    Selesai <RequiredMark />
                                </Label>
                                <DatePicker
                                    value={form.data.end_date}
                                    onChange={(v) =>
                                        form.setData('end_date', v)
                                    }
                                    placeholder="Tanggal selesai"
                                />
                                {form.errors.end_date && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.end_date}
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex flex-col gap-3 p-5">
                            <div className="flex items-center justify-between">
                                <Label>
                                    Karyawan <RequiredMark />
                                </Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={toggleAll}
                                >
                                    {form.data.employee_ids.length ===
                                    employees.length
                                        ? 'Hapus semua'
                                        : 'Pilih semua'}
                                </Button>
                            </div>
                            <div className="grid max-h-[420px] gap-1 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                                {employees.map((e) => (
                                    <label
                                        key={e.id}
                                        className="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                                    >
                                        <Checkbox
                                            checked={form.data.employee_ids.includes(
                                                e.id,
                                            )}
                                            onCheckedChange={() => toggle(e.id)}
                                        />
                                        {e.label}
                                    </label>
                                ))}
                            </div>
                            {form.errors.employee_ids && (
                                <p className="text-sm text-destructive">
                                    {form.errors.employee_ids}
                                </p>
                            )}
                        </CardContent>
                        <CardFooter className="justify-end">
                            <Button type="submit" disabled={form.processing}>
                                <Wand2 />
                                Generate
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
