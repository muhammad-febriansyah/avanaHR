import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import bpjsParameters from '@/actions/App/Http/Controllers/BpjsParameterController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatRupiah } from '@/lib/format';

type TkRates = {
    jht_employee?: number;
    jht_employer?: number;
    jkk?: number;
    jkm?: number;
    jp_employee?: number;
    jp_employer?: number;
    jp_cap?: number;
};

type Parameter = {
    id: number;
    effective_date: string | null;
    kes_rate_employee: number;
    kes_rate_employer: number;
    kes_cap: number;
    tk_rates: TkRates;
};

type Defaults = {
    kes_rate_employee: number;
    kes_rate_employer: number;
    kes_cap: number;
    tk_rates: TkRates;
};

type IndexProps = { parameters: Parameter[]; defaults: Defaults };

const RATE_FIELDS: { key: string; label: string }[] = [
    { key: 'kes_rate_employee', label: 'Kesehatan Karyawan (%)' },
    { key: 'kes_rate_employer', label: 'Kesehatan Perusahaan (%)' },
    { key: 'jht_employee', label: 'JHT Karyawan (%)' },
    { key: 'jht_employer', label: 'JHT Perusahaan (%)' },
    { key: 'jkk', label: 'JKK (%)' },
    { key: 'jkm', label: 'JKM (%)' },
    { key: 'jp_employee', label: 'JP Karyawan (%)' },
    { key: 'jp_employer', label: 'JP Perusahaan (%)' },
];

const CAP_FIELDS: { key: string; label: string }[] = [
    { key: 'kes_cap', label: 'Plafon Kesehatan (Rp)' },
    { key: 'jp_cap', label: 'Plafon JP (Rp)' },
];

export default function BpjsParametersIndex({
    parameters,
    defaults,
}: IndexProps) {
    useFlashToast();

    const [open, setOpen] = useState(false);
    const form = useForm<Record<string, string>>({});

    function openCreate() {
        form.clearErrors();
        form.setData({
            effective_date: '',
            kes_rate_employee: String(defaults.kes_rate_employee),
            kes_rate_employer: String(defaults.kes_rate_employer),
            kes_cap: String(defaults.kes_cap),
            jht_employee: String(defaults.tk_rates.jht_employee ?? 0),
            jht_employer: String(defaults.tk_rates.jht_employer ?? 0),
            jkk: String(defaults.tk_rates.jkk ?? 0),
            jkm: String(defaults.tk_rates.jkm ?? 0),
            jp_employee: String(defaults.tk_rates.jp_employee ?? 0),
            jp_employer: String(defaults.tk_rates.jp_employer ?? 0),
            jp_cap: String(defaults.tk_rates.jp_cap ?? 0),
        });
        setOpen(true);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(bpjsParameters.store.url(), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    return (
        <>
            <Head title="Parameter BPJS" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Parameter BPJS"
                    description="Iuran & plafon BPJS per tenant, berlaku per tanggal efektif."
                >
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah Parameter
                    </Button>
                </PageHeader>

                <Card className="gap-0 py-0">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>Kes (Kary/Persh)</TableHead>
                                    <TableHead>JHT (Kary/Persh)</TableHead>
                                    <TableHead>JP (Kary/Persh)</TableHead>
                                    <TableHead>Plafon Kes</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {parameters.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada parameter BPJS.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    parameters.map((p) => (
                                        <TableRow key={p.id}>
                                            <TableCell className="font-medium">
                                                {p.effective_date}
                                            </TableCell>
                                            <TableCell>
                                                {p.kes_rate_employee}% /{' '}
                                                {p.kes_rate_employer}%
                                            </TableCell>
                                            <TableCell>
                                                {p.tk_rates.jht_employee ?? 0}% /{' '}
                                                {p.tk_rates.jht_employer ?? 0}%
                                            </TableCell>
                                            <TableCell>
                                                {p.tk_rates.jp_employee ?? 0}% /{' '}
                                                {p.tk_rates.jp_employer ?? 0}%
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(p.kes_cap)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Parameter BPJS"
                                                    description={`Hapus parameter berlaku ${p.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            bpjsParameters.destroy.url(
                                                                p.id,
                                                            ),
                                                            {
                                                                preserveScroll:
                                                                    true,
                                                            },
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                        >
                                                            <Trash2 />
                                                            Hapus
                                                        </Button>
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Tambah Parameter BPJS</DialogTitle>
                    </DialogHeader>

                    <form
                        id="bpjs-param-form"
                        onSubmit={submit}
                        className="grid gap-4"
                    >
                        <div className="grid gap-1">
                            <Label htmlFor="effective_date">
                                Tanggal Berlaku{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="effective_date"
                                type="date"
                                value={form.data.effective_date ?? ''}
                                onChange={(e) =>
                                    form.setData(
                                        'effective_date',
                                        e.target.value,
                                    )
                                }
                            />
                            {form.errors.effective_date && (
                                <p className="text-sm text-destructive">
                                    {form.errors.effective_date}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            {RATE_FIELDS.map((f) => (
                                <div key={f.key} className="grid gap-1">
                                    <Label htmlFor={f.key}>{f.label}</Label>
                                    <Input
                                        id={f.key}
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={form.data[f.key] ?? ''}
                                        onChange={(e) =>
                                            form.setData(f.key, e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                            {CAP_FIELDS.map((f) => (
                                <div key={f.key} className="grid gap-1">
                                    <Label htmlFor={f.key}>{f.label}</Label>
                                    <Input
                                        id={f.key}
                                        type="number"
                                        min={0}
                                        value={form.data[f.key] ?? ''}
                                        onChange={(e) =>
                                            form.setData(f.key, e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    </form>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="bpjs-param-form"
                            disabled={form.processing}
                        >
                            Simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
