import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import employees from '@/actions/App/Http/Controllers/EmployeeController';
import taxBpjs from '@/actions/App/Http/Controllers/EmployeeTaxBpjsController';
import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type TaxProfile = {
    id: number;
    effective_date: string | null;
    ptkp_status: string | null;
    npwp: string | null;
    tax_method: string;
    beginning_ytd: number;
};

type Flags = Record<string, boolean>;

type BpjsProfile = {
    id: number;
    effective_date: string | null;
    bpjs_kesehatan_no: string | null;
    bpjs_tk_no: string | null;
    kesehatan_basis: number;
    tk_basis: number;
    participation_flags: Flags;
};

type Props = {
    employee: { id: number; name: string; employee_no: string };
    taxProfiles: TaxProfile[];
    bpjsProfiles: BpjsProfile[];
    ptkpOptions: string[];
};

const PARTICIPATION = ['kesehatan', 'jht', 'jkk', 'jkm', 'jp'];

export default function EmployeeTaxBpjs({
    employee,
    taxProfiles,
    bpjsProfiles,
    ptkpOptions,
}: Props) {
    useFlashToast();

    const [taxOpen, setTaxOpen] = useState(false);
    const [bpjsOpen, setBpjsOpen] = useState(false);

    const taxForm = useForm({
        effective_date: '',
        ptkp_status: 'TK/0',
        npwp: '',
        tax_method: 'ter',
        beginning_ytd: '0',
    });

    const bpjsForm = useForm<{
        effective_date: string;
        bpjs_kesehatan_no: string;
        bpjs_tk_no: string;
        kesehatan_basis: string;
        tk_basis: string;
        kesehatan: boolean;
        jht: boolean;
        jkk: boolean;
        jkm: boolean;
        jp: boolean;
    }>({
        effective_date: '',
        bpjs_kesehatan_no: '',
        bpjs_tk_no: '',
        kesehatan_basis: '',
        tk_basis: '',
        kesehatan: true,
        jht: true,
        jkk: true,
        jkm: true,
        jp: true,
    });

    function submitTax(event: FormEvent) {
        event.preventDefault();
        taxForm.post(taxBpjs.storeTax.url(employee.id), {
            preserveScroll: true,
            onSuccess: () => setTaxOpen(false),
        });
    }

    function submitBpjs(event: FormEvent) {
        event.preventDefault();
        bpjsForm.post(taxBpjs.storeBpjs.url(employee.id), {
            preserveScroll: true,
            onSuccess: () => setBpjsOpen(false),
        });
    }

    return (
        <>
            <Head title={`Pajak & BPJS — ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title={`Pajak & BPJS — ${employee.name}`}
                    description="Profil PTKP/pajak dan BPJS karyawan (per tanggal efektif)."
                >
                    <Button asChild variant="outline">
                        <Link href={employees.show.url(employee.id)}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Tax profiles */}
                <Card className="gap-0 py-0">
                    <CardHeader className="flex-row items-center justify-between px-5 pt-5">
                        <CardTitle>Profil Pajak (PTKP)</CardTitle>
                        <Button size="sm" onClick={() => setTaxOpen(true)}>
                            <Plus />
                            Tambah
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0 pt-3">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>PTKP</TableHead>
                                    <TableHead>NPWP</TableHead>
                                    <TableHead>Metode</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taxProfiles.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada profil pajak.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    taxProfiles.map((tp) => (
                                        <TableRow key={tp.id}>
                                            <TableCell className="font-medium">
                                                {tp.effective_date}
                                            </TableCell>
                                            <TableCell>
                                                {tp.ptkp_status}
                                            </TableCell>
                                            <TableCell>
                                                {tp.npwp ?? '-'}
                                            </TableCell>
                                            <TableCell className="uppercase">
                                                {tp.tax_method}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Profil Pajak"
                                                    description={`Hapus profil pajak berlaku ${tp.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            taxBpjs.destroyTax.url(
                                                                tp.id,
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

                {/* BPJS profiles */}
                <Card className="gap-0 py-0">
                    <CardHeader className="flex-row items-center justify-between px-5 pt-5">
                        <CardTitle>Profil BPJS</CardTitle>
                        <Button size="sm" onClick={() => setBpjsOpen(true)}>
                            <Plus />
                            Tambah
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0 pt-3">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Berlaku</TableHead>
                                    <TableHead>Basis Kes</TableHead>
                                    <TableHead>Basis TK</TableHead>
                                    <TableHead>Ikut</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bpjsProfiles.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada profil BPJS.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    bpjsProfiles.map((bp) => (
                                        <TableRow key={bp.id}>
                                            <TableCell className="font-medium">
                                                {bp.effective_date}
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(
                                                    bp.kesehatan_basis,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatRupiah(bp.tk_basis)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {PARTICIPATION.filter(
                                                        (k) =>
                                                            bp
                                                                .participation_flags[
                                                                k
                                                            ],
                                                    ).map((k) => (
                                                        <Badge
                                                            key={k}
                                                            variant="secondary"
                                                            className="uppercase"
                                                        >
                                                            {k}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ConfirmDialog
                                                    title="Hapus Profil BPJS"
                                                    description={`Hapus profil BPJS berlaku ${bp.effective_date}?`}
                                                    confirmLabel="Hapus"
                                                    onConfirm={() =>
                                                        router.delete(
                                                            taxBpjs.destroyBpjs.url(
                                                                bp.id,
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

            {/* Tax dialog */}
            <Dialog open={taxOpen} onOpenChange={setTaxOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Profil Pajak</DialogTitle>
                    </DialogHeader>
                    <form
                        id="tax-form"
                        onSubmit={submitTax}
                        className="grid gap-4"
                    >
                        <div className="grid gap-1">
                            <Label>
                                Tanggal Berlaku{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                type="date"
                                value={taxForm.data.effective_date}
                                onChange={(e) =>
                                    taxForm.setData(
                                        'effective_date',
                                        e.target.value,
                                    )
                                }
                            />
                            {taxForm.errors.effective_date && (
                                <p className="text-sm text-destructive">
                                    {taxForm.errors.effective_date}
                                </p>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1">
                                <Label>Status PTKP</Label>
                                <Select
                                    value={taxForm.data.ptkp_status}
                                    onValueChange={(v) =>
                                        taxForm.setData('ptkp_status', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ptkpOptions.map((o) => (
                                            <SelectItem key={o} value={o}>
                                                {o}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1">
                                <Label>Metode</Label>
                                <Select
                                    value={taxForm.data.tax_method}
                                    onValueChange={(v) =>
                                        taxForm.setData('tax_method', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ter">TER</SelectItem>
                                        <SelectItem value="gross">
                                            Gross
                                        </SelectItem>
                                        <SelectItem value="nett">
                                            Nett
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1">
                                <Label>NPWP</Label>
                                <Input
                                    value={taxForm.data.npwp}
                                    onChange={(e) =>
                                        taxForm.setData('npwp', e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label>YTD Awal (Rp)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={taxForm.data.beginning_ytd}
                                    onChange={(e) =>
                                        taxForm.setData(
                                            'beginning_ytd',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </form>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setTaxOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="tax-form"
                            disabled={taxForm.processing}
                        >
                            Simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* BPJS dialog */}
            <Dialog open={bpjsOpen} onOpenChange={setBpjsOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Profil BPJS</DialogTitle>
                    </DialogHeader>
                    <form
                        id="bpjs-form"
                        onSubmit={submitBpjs}
                        className="grid gap-4"
                    >
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1">
                                <Label>
                                    Tanggal Berlaku{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    type="date"
                                    value={bpjsForm.data.effective_date}
                                    onChange={(e) =>
                                        bpjsForm.setData(
                                            'effective_date',
                                            e.target.value,
                                        )
                                    }
                                />
                                {bpjsForm.errors.effective_date && (
                                    <p className="text-sm text-destructive">
                                        {bpjsForm.errors.effective_date}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-1">
                                <Label>No. Kesehatan</Label>
                                <Input
                                    value={bpjsForm.data.bpjs_kesehatan_no}
                                    onChange={(e) =>
                                        bpjsForm.setData(
                                            'bpjs_kesehatan_no',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label>Basis Kesehatan (Rp)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={bpjsForm.data.kesehatan_basis}
                                    onChange={(e) =>
                                        bpjsForm.setData(
                                            'kesehatan_basis',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label>Basis TK (Rp)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={bpjsForm.data.tk_basis}
                                    onChange={(e) =>
                                        bpjsForm.setData(
                                            'tk_basis',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-4">
                            {PARTICIPATION.map((k) => (
                                <label
                                    key={k}
                                    className="flex items-center gap-2 text-sm uppercase"
                                >
                                    <Checkbox
                                        checked={
                                            bpjsForm.data[
                                                k as keyof typeof bpjsForm.data
                                            ] as boolean
                                        }
                                        onCheckedChange={(c) =>
                                            bpjsForm.setData(
                                                k as keyof typeof bpjsForm.data,
                                                Boolean(c),
                                            )
                                        }
                                    />
                                    {k}
                                </label>
                            ))}
                        </div>
                    </form>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setBpjsOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="bpjs-form"
                            disabled={bpjsForm.processing}
                        >
                            Simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
