import { Head, useForm } from '@inertiajs/react';
import {
    Facebook,
    Instagram,
    Linkedin,
    Mail,
    MapPin,
    Music2,
    Phone,
    Save,
    Twitter,
    Youtube,
} from 'lucide-react';
import type { ComponentType, FormEvent } from 'react';
import web from '@/actions/App/Http/Controllers/Settings/WebController';
import FileDropzone from '@/components/file-dropzone';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useFlashToast } from '@/hooks/use-flash-toast';

type Social = {
    facebook: string;
    instagram: string;
    twitter: string;
    linkedin: string;
    youtube: string;
    tiktok: string;
};

type Settings = {
    site_name: string;
    tagline: string;
    meta_keywords: string;
    meta_description: string;
    contact_email: string;
    contact_phone: string;
    address: string;
    social: Social;
    logo_url: string | null;
    favicon_url: string | null;
};

type WebSettingsProps = {
    settings: Settings;
};

type WebForm = {
    site_name: string;
    tagline: string;
    meta_keywords: string;
    meta_description: string;
    contact_email: string;
    contact_phone: string;
    address: string;
    social: Social;
    logo: File | null;
    favicon: File | null;
};

const SOCIAL_FIELDS: Array<{
    key: keyof Social;
    label: string;
    icon: ComponentType<{ className?: string }>;
    placeholder: string;
}> = [
    {
        key: 'facebook',
        label: 'Facebook',
        icon: Facebook,
        placeholder: 'https://facebook.com/avanahr',
    },
    {
        key: 'instagram',
        label: 'Instagram',
        icon: Instagram,
        placeholder: 'https://instagram.com/avanahr',
    },
    {
        key: 'twitter',
        label: 'X / Twitter',
        icon: Twitter,
        placeholder: 'https://x.com/avanahr',
    },
    {
        key: 'linkedin',
        label: 'LinkedIn',
        icon: Linkedin,
        placeholder: 'https://linkedin.com/company/avanahr',
    },
    {
        key: 'youtube',
        label: 'YouTube',
        icon: Youtube,
        placeholder: 'https://youtube.com/@avanahr',
    },
    {
        key: 'tiktok',
        label: 'TikTok',
        icon: Music2,
        placeholder: 'https://tiktok.com/@avanahr',
    },
];

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="text-sm text-destructive">{message}</p>;
}

export default function WebSettings({ settings }: WebSettingsProps) {
    useFlashToast();

    const form = useForm<WebForm>({
        site_name: settings.site_name,
        tagline: settings.tagline,
        meta_keywords: settings.meta_keywords,
        meta_description: settings.meta_description,
        contact_email: settings.contact_email,
        contact_phone: settings.contact_phone,
        address: settings.address,
        social: { ...settings.social },
        logo: null,
        favicon: null,
    });

    function setSocial(key: keyof Social, value: string) {
        form.setData('social', { ...form.data.social, [key]: value });
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post(web.update.url(), { forceFormData: true });
    }

    return (
        <>
            <Head title="Pengaturan Web" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Pengaturan Web"
                    description="Identitas situs AvanaHR: nama, kata kunci SEO, logo, favicon, kontak, dan media sosial."
                />

                <form onSubmit={submit} className="grid max-w-3xl gap-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Identitas Situs</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="site_name">
                                    Nama Situs{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="site_name"
                                    value={form.data.site_name}
                                    onChange={(e) =>
                                        form.setData('site_name', e.target.value)
                                    }
                                    placeholder="AvanaHR"
                                />
                                <FieldError message={form.errors.site_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tagline">Tagline</Label>
                                <Input
                                    id="tagline"
                                    value={form.data.tagline}
                                    onChange={(e) =>
                                        form.setData('tagline', e.target.value)
                                    }
                                    placeholder="Advancing People, Empowering Growth"
                                />
                                <FieldError message={form.errors.tagline} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>SEO &amp; Meta</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="meta_keywords">
                                    Kata Kunci (Keywords)
                                </Label>
                                <Input
                                    id="meta_keywords"
                                    value={form.data.meta_keywords}
                                    onChange={(e) =>
                                        form.setData(
                                            'meta_keywords',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="HRIS, HCM, payroll Indonesia, absensi, BPJS, PPh21"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Pisahkan dengan koma.
                                </p>
                                <FieldError
                                    message={form.errors.meta_keywords}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="meta_description">
                                    Deskripsi Meta
                                </Label>
                                <Textarea
                                    id="meta_description"
                                    rows={3}
                                    value={form.data.meta_description}
                                    onChange={(e) =>
                                        form.setData(
                                            'meta_description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Platform HRIS/HCM multi-tenant untuk mengelola karyawan, absensi, dan payroll sesuai regulasi Indonesia."
                                />
                                <FieldError
                                    message={form.errors.meta_description}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Logo &amp; Favicon</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="logo">Logo</Label>
                                <FileDropzone
                                    id="logo"
                                    value={form.data.logo}
                                    onChange={(file) =>
                                        form.setData('logo', file)
                                    }
                                    currentUrl={settings.logo_url}
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    hint="PNG/JPG/WEBP/SVG · maks 2 MB"
                                    variant="image"
                                />
                                <FieldError message={form.errors.logo} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="favicon">Favicon</Label>
                                <FileDropzone
                                    id="favicon"
                                    value={form.data.favicon}
                                    onChange={(file) =>
                                        form.setData('favicon', file)
                                    }
                                    currentUrl={settings.favicon_url}
                                    accept="image/png,image/x-icon,image/svg+xml"
                                    hint="PNG/ICO/SVG · maks 512 KB"
                                    variant="image"
                                    shape="square"
                                />
                                <FieldError message={form.errors.favicon} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Kontak</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="contact_email">
                                    <Mail className="mr-1 inline size-4" />
                                    Email
                                </Label>
                                <Input
                                    id="contact_email"
                                    type="email"
                                    value={form.data.contact_email}
                                    onChange={(e) =>
                                        form.setData(
                                            'contact_email',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="support@avanahr.co.id"
                                />
                                <FieldError
                                    message={form.errors.contact_email}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="contact_phone">
                                    <Phone className="mr-1 inline size-4" />
                                    Telepon
                                </Label>
                                <Input
                                    id="contact_phone"
                                    value={form.data.contact_phone}
                                    onChange={(e) =>
                                        form.setData(
                                            'contact_phone',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="(021) 5099-9000"
                                />
                                <FieldError
                                    message={form.errors.contact_phone}
                                />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="address">
                                    <MapPin className="mr-1 inline size-4" />
                                    Alamat
                                </Label>
                                <Textarea
                                    id="address"
                                    rows={2}
                                    value={form.data.address}
                                    onChange={(e) =>
                                        form.setData('address', e.target.value)
                                    }
                                    placeholder="Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan 12190"
                                />
                                <FieldError message={form.errors.address} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Media Sosial</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            {SOCIAL_FIELDS.map(
                                ({ key, label, icon: Icon, placeholder }) => (
                                    <div key={key} className="grid gap-2">
                                        <Label htmlFor={`social-${key}`}>
                                            <Icon className="mr-1 inline size-4" />
                                            {label}
                                        </Label>
                                        <Input
                                            id={`social-${key}`}
                                            value={form.data.social[key]}
                                            onChange={(e) =>
                                                setSocial(key, e.target.value)
                                            }
                                            placeholder={placeholder}
                                        />
                                        <FieldError
                                            message={
                                                form.errors[
                                                    `social.${key}` as keyof typeof form.errors
                                                ]
                                            }
                                        />
                                    </div>
                                ),
                            )}
                        </CardContent>
                    </Card>

                    <div>
                        <Button type="submit" disabled={form.processing}>
                            <Save />
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
