import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Save, Trash2, X } from 'lucide-react';
import type { FormEvent } from 'react';
import customFields from '@/actions/App/Http/Controllers/CustomFieldController';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
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
import { useFlashToast } from '@/hooks/use-flash-toast';

type Option = { value: string; label: string };

type CustomFieldRecord = {
    id: number;
    entity_type: string;
    key: string;
    label: string;
    type: string;
    options: string[];
    is_required: boolean;
    order: number;
};

type EditProps = {
    customField: CustomFieldRecord;
    entities: Option[];
    types: Option[];
};

type FieldForm = {
    entity_type: string;
    key: string;
    label: string;
    type: string;
    options: string[];
    is_required: boolean;
    order: string;
};

export default function CustomFieldsEdit({
    customField,
    entities,
    types,
}: EditProps) {
    useFlashToast();

    const form = useForm<FieldForm>({
        entity_type: customField.entity_type,
        key: customField.key,
        label: customField.label,
        type: customField.type,
        options: customField.options.length > 0 ? customField.options : [''],
        is_required: customField.is_required,
        order: String(customField.order),
    });

    function setOption(index: number, value: string) {
        const options = [...form.data.options];
        options[index] = value;
        form.setData('options', options);
    }

    function addOption() {
        form.setData('options', [...form.data.options, '']);
    }

    function removeOption(index: number) {
        const options = form.data.options.filter((_, i) => i !== index);
        form.setData('options', options.length > 0 ? options : ['']);
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            options: data.options.map((line) => line.trim()).filter(Boolean),
        }));

        form.put(customFields.update.url(customField.id));
    }

    return (
        <>
            <Head title="Ubah Custom Field" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Ubah Custom Field"
                    description="Perbarui definisi custom field. Entitas dan kunci tidak dapat diubah."
                />

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl"
                >
                    <Card>
                        <CardContent className="flex flex-col gap-6">
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="entity_type">Entitas</Label>
                                    <Select
                                        value={form.data.entity_type}
                                        onValueChange={(value) =>
                                            form.setData('entity_type', value)
                                        }
                                        disabled
                                    >
                                        <SelectTrigger
                                            id="entity_type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {entities.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">
                                        Tipe <RequiredMark />
                                    </Label>
                                    <Select
                                        value={form.data.type}
                                        onValueChange={(value) =>
                                            form.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="type"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Pilih tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="label">
                                        Label <RequiredMark />
                                    </Label>
                                    <Input
                                        id="label"
                                        value={form.data.label}
                                        onChange={(e) =>
                                            form.setData(
                                                'label',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Mis. Ukuran Seragam"
                                    />
                                    <InputError message={form.errors.label} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="key">Kunci</Label>
                                    <Input
                                        id="key"
                                        value={form.data.key}
                                        disabled
                                        className="font-mono"
                                        placeholder="Mis. nomor_induk"
                                    />
                                </div>
                            </div>

                            {form.data.type === 'select' && (
                                <div className="grid gap-3 rounded-lg border bg-muted/30 p-4">
                                    <div className="flex items-center justify-between">
                                        <Label>Pilihan (Dropdown)</Label>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            onClick={addOption}
                                        >
                                            <Plus />
                                            Tambah Pilihan
                                        </Button>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        {form.data.options.map(
                                            (option, index) => (
                                                <div
                                                    key={index}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Input
                                                        value={option}
                                                        onChange={(e) =>
                                                            setOption(
                                                                index,
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder={`Pilihan ${index + 1}`}
                                                    />
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            removeOption(index)
                                                        }
                                                        aria-label="Hapus pilihan"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Daftar pilihan yang muncul saat tipe
                                        field adalah dropdown.
                                    </p>
                                </div>
                            )}

                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={form.data.is_required}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'is_required',
                                                checked === true,
                                            )
                                        }
                                    />
                                    Wajib diisi
                                </label>
                                <div className="flex items-center gap-2">
                                    <Label htmlFor="order" className="text-sm">
                                        Urutan
                                    </Label>
                                    <Input
                                        id="order"
                                        type="number"
                                        min="0"
                                        max="999"
                                        value={form.data.order}
                                        onChange={(e) =>
                                            form.setData(
                                                'order',
                                                e.target.value,
                                            )
                                        }
                                        className="w-20"
                                        placeholder="Mis. 1"
                                    />
                                </div>
                            </div>
                            <InputError message={form.errors.order} />
                        </CardContent>
                        <CardFooter className="flex justify-end gap-3">
                            <Button asChild variant="secondary" type="button">
                                <Link href={customFields.index.url()}>
                                    <X />
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                Simpan Perubahan
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
