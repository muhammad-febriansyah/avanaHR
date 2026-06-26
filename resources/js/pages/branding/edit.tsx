import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import branding from '@/actions/App/Http/Controllers/BrandingController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';

type BrandingProps = {
    company: { id: number; name: string; logo_url: string | null };
};

export default function BrandingEdit({ company }: BrandingProps) {
    useFlashToast();

    const [preview, setPreview] = useState<string | null>(company.logo_url);
    const form = useForm<{ name: string; logo: File | null }>({
        name: company.name,
        logo: null,
    });

    function onLogoChange(file: File | null) {
        form.setData('logo', file);
        setPreview(file ? URL.createObjectURL(file) : company.logo_url);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(branding.update.url(), { forceFormData: true });
    }

    return (
        <>
            <Head title="Branding" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Branding"
                    description="Nama perusahaan dan logo yang tampil di aplikasi dan slip gaji."
                />

                <Card className="max-w-xl">
                    <CardHeader>
                        <CardTitle>Identitas Perusahaan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    Nama Perusahaan{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder="Mis. PT Avana Indonesia"
                                />
                                {form.errors.name && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="logo">Logo</Label>
                                <div className="flex items-center gap-4">
                                    <div className="flex h-16 w-32 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                                        {preview ? (
                                            <img
                                                src={preview}
                                                alt="Logo"
                                                className="max-h-full max-w-full object-contain"
                                            />
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                Belum ada logo
                                            </span>
                                        )}
                                    </div>
                                    <Input
                                        id="logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                        className="max-w-xs"
                                        onChange={(e) =>
                                            onLogoChange(
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    PNG/JPG/WEBP/SVG, maks 1 MB.
                                </p>
                                {form.errors.logo && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.logo}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <Save />
                                    Simpan
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
