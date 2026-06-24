import { Head } from '@inertiajs/react';
import {
    ArrowUpRight,
    Check,
    CheckCheck,
    ChevronRight,
    CircleDot,
    Clock,
    Download,
    FileText,
    Fingerprint,
    Plus,
    TrendingUp,
    UserCheck,
    UserPlus,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

/**
 * NOTE: dashboard data is mocked from the AvanaHR design template.
 * Wire to a controller (Inertia props) once HR Core / Attendance ship.
 */

type Kpi = {
    label: string;
    value: string;
    icon: LucideIcon;
    iconClass: string;
    delta: string;
    deltaIcon: LucideIcon;
    deltaClass: string;
};

const kpis: Kpi[] = [
    {
        label: 'Total Karyawan',
        value: '1.248',
        icon: Users,
        iconClass: 'bg-primary/10 text-primary',
        delta: '+24',
        deltaIcon: TrendingUp,
        deltaClass: 'text-success',
    },
    {
        label: 'Hadir Hari Ini',
        value: '1.156',
        icon: UserCheck,
        iconClass: 'bg-success/10 text-success',
        delta: '92,6%',
        deltaIcon: TrendingUp,
        deltaClass: 'text-success',
    },
    {
        label: 'Pending Approval',
        value: '18',
        icon: Clock,
        iconClass: 'bg-warning/10 text-warning',
        delta: '4 baru',
        deltaIcon: ArrowUpRight,
        deltaClass: 'text-warning',
    },
    {
        label: 'Payroll Juni',
        value: 'Rp 4,82 M',
        icon: Wallet,
        iconClass: 'bg-sky/15 text-primary',
        delta: 'Draft',
        deltaIcon: CircleDot,
        deltaClass: 'text-muted-foreground',
    },
];

const activities = [
    {
        icon: UserPlus,
        className: 'bg-primary/10 text-primary',
        text: 'Bagus Pratama ditambahkan sebagai Software Engineer',
        time: '5 menit lalu',
    },
    {
        icon: CheckCheck,
        className: 'bg-success/10 text-success',
        text: 'Cuti tahunan Dewi Lestari disetujui',
        time: '48 menit lalu',
    },
    {
        icon: Wallet,
        className: 'bg-sky/15 text-primary',
        text: 'Payroll periode Mei 2026 telah dikunci',
        time: '2 jam lalu',
    },
    {
        icon: FileText,
        className: 'bg-warning/10 text-warning',
        text: 'Andi Wijaya mengunggah dokumen kontrak baru',
        time: '4 jam lalu',
    },
    {
        icon: Fingerprint,
        className: 'bg-destructive/10 text-destructive',
        text: '12 karyawan tercatat terlambat hari ini',
        time: 'Hari ini, 09:14',
    },
];

const approvals = [
    {
        ini: 'SN',
        avClass: 'bg-primary',
        name: 'Siti Nurhaliza',
        type: 'Cuti Tahunan · 3 hari',
    },
    {
        ini: 'RM',
        avClass: 'bg-sky',
        name: 'Rizki Maulana',
        type: 'Lembur · 4 jam',
    },
    {
        ini: 'FN',
        avClass: 'bg-navy',
        name: 'Fajar Nugroho',
        type: 'Reimbursement · Rp 850.000',
    },
    {
        ini: 'IP',
        avClass: 'bg-warning',
        name: 'Intan Permata',
        type: 'Koreksi Absen',
    },
];

const headcount = [
    { label: 'Jan', value: 1180 },
    { label: 'Feb', value: 1192 },
    { label: 'Mar', value: 1205 },
    { label: 'Apr', value: 1218 },
    { label: 'Mei', value: 1231 },
    { label: 'Jun', value: 1248 },
];

const attendance = [
    { label: 'Sen', value: 96 },
    { label: 'Sel', value: 94 },
    { label: 'Rab', value: 97 },
    { label: 'Kam', value: 93 },
    { label: 'Jum', value: 89 },
];

function HeadcountChart() {
    const W = 420;
    const H = 150;
    const pad = 8;
    const min = 1160;
    const max = 1260;
    const pts = headcount.map((d, i) => {
        const x = pad + i * ((W - pad * 2) / (headcount.length - 1));
        const y = H - 12 - ((d.value - min) / (max - min)) * (H - 40);

        return [x, y] as const;
    });
    const line = pts
        .map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`)
        .join(' ');
    const area = `${line} L${pts[pts.length - 1][0].toFixed(1)} ${H - 12} L${pts[0][0].toFixed(1)} ${H - 12} Z`;

    return (
        <svg
            viewBox={`0 0 ${W} ${H + 10}`}
            className="h-[180px] w-full overflow-visible"
            role="img"
            aria-label="Tren headcount 6 bulan terakhir"
        >
            <defs>
                <linearGradient id="hcg" x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0%"
                        stopColor="var(--primary)"
                        stopOpacity="0.18"
                    />
                    <stop
                        offset="100%"
                        stopColor="var(--primary)"
                        stopOpacity="0"
                    />
                </linearGradient>
            </defs>
            <path d={area} fill="url(#hcg)" />
            <path
                d={line}
                fill="none"
                stroke="var(--primary)"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            {pts.map((p, i) => (
                <circle
                    key={headcount[i].label}
                    cx={p[0]}
                    cy={p[1]}
                    r="3.5"
                    fill="#fff"
                    stroke="var(--primary)"
                    strokeWidth="2"
                />
            ))}
            {headcount.map((d, i) => (
                <text
                    key={d.label}
                    x={pts[i][0]}
                    y={H + 4}
                    fontSize="11"
                    fill="var(--muted-foreground)"
                    textAnchor="middle"
                >
                    {d.label}
                </text>
            ))}
        </svg>
    );
}

function AttendanceChart() {
    return (
        <div className="flex h-[200px] items-end gap-4 pt-2.5">
            {attendance.map((d, i) => {
                const last = i === attendance.length - 1;

                return (
                    <div
                        key={d.label}
                        className="flex h-full flex-1 flex-col items-center justify-end gap-2.5"
                    >
                        <div className="text-xs font-semibold text-navy">
                            {d.value}%
                        </div>
                        <div
                            className={`w-full max-w-[34px] rounded-t-md ${last ? 'bg-warning' : 'bg-[linear-gradient(180deg,#6E9BE6,#2F54C9)]'}`}
                            style={{ height: `${d.value * 1.5}px` }}
                        />
                        <div className="text-xs text-muted-foreground">
                            {d.label}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <nav
                            aria-label="Breadcrumb"
                            className="mb-2 flex items-center gap-1.5 text-[12.5px] text-muted-foreground"
                        >
                            <span>Beranda</span>
                            <ChevronRight className="size-3.5" />
                            <span className="text-foreground">Dashboard</span>
                        </nav>
                        <h1 className="text-2xl font-semibold tracking-tight text-navy">
                            Selamat datang kembali, Rina 👋
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ringkasan aktivitas HR — Selasa, 23 Jun 2026
                        </p>
                    </div>
                    <div className="flex gap-2.5">
                        <Button
                            variant="outline"
                            onClick={() =>
                                toast.success('Export dimulai', {
                                    description:
                                        'Laporan akan diunduh setelah selesai.',
                                })
                            }
                        >
                            <Download />
                            Export
                        </Button>
                        <Button
                            onClick={() =>
                                toast.info('Modul Karyawan segera hadir')
                            }
                        >
                            <Plus />
                            Tambah Karyawan
                        </Button>
                    </div>
                </div>

                {/* KPI */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {kpis.map((k) => (
                        <Card key={k.label} className="gap-0 py-0">
                            <CardContent className="p-5">
                                <div className="flex items-center justify-between">
                                    <div
                                        className={`flex size-10 items-center justify-center rounded-[10px] ${k.iconClass}`}
                                    >
                                        <k.icon className="size-5" />
                                    </div>
                                    <div
                                        className={`flex items-center gap-1 text-xs font-semibold ${k.deltaClass}`}
                                    >
                                        <k.deltaIcon className="size-3.5" />
                                        {k.delta}
                                    </div>
                                </div>
                                <div className="mt-3.5 text-[27px] font-bold tracking-tight text-navy">
                                    {k.value}
                                </div>
                                <div className="mt-0.5 text-sm text-muted-foreground">
                                    {k.label}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1.6fr_1fr]">
                    <Card className="gap-0 py-0">
                        <CardHeader className="flex flex-row items-center justify-between border-b px-5 py-4">
                            <div>
                                <CardTitle className="text-[15px] text-navy">
                                    Tren Headcount
                                </CardTitle>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    6 bulan terakhir
                                </p>
                            </div>
                            <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                <span className="size-2.5 rounded-full bg-primary" />
                                Total
                            </span>
                        </CardHeader>
                        <CardContent className="p-5">
                            <HeadcountChart />
                        </CardContent>
                    </Card>

                    <Card className="gap-0 py-0">
                        <CardHeader className="border-b px-5 py-4">
                            <CardTitle className="text-[15px] text-navy">
                                Kehadiran Minggu Ini
                            </CardTitle>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                Persentase hadir per hari
                            </p>
                        </CardHeader>
                        <CardContent className="p-5">
                            <AttendanceChart />
                        </CardContent>
                    </Card>
                </div>

                {/* Activity + Approvals */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1.4fr_1fr]">
                    <Card className="gap-0 py-0">
                        <CardHeader className="flex flex-row items-center justify-between border-b px-5 py-4">
                            <CardTitle className="text-[15px] text-navy">
                                Aktivitas Terbaru
                            </CardTitle>
                            <button
                                type="button"
                                className="text-xs font-medium text-primary hover:underline"
                                onClick={() => toast.info('Segera hadir')}
                            >
                                Lihat semua
                            </button>
                        </CardHeader>
                        <CardContent className="px-5 pt-1.5 pb-3.5">
                            {activities.map((a, i) => (
                                <div
                                    key={a.text}
                                    className={`flex gap-3 py-3 ${i < activities.length - 1 ? 'border-b border-border/60' : ''}`}
                                >
                                    <div
                                        className={`flex size-[34px] shrink-0 items-center justify-center rounded-[9px] ${a.className}`}
                                    >
                                        <a.icon className="size-4" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm text-foreground">
                                            {a.text}
                                        </div>
                                        <div className="mt-0.5 text-xs text-muted-foreground">
                                            {a.time}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="gap-0 py-0">
                        <CardHeader className="flex flex-row items-center justify-between border-b px-5 py-4">
                            <CardTitle className="text-[15px] text-navy">
                                Menunggu Persetujuan
                            </CardTitle>
                            <span className="rounded-full bg-warning/10 px-2.5 py-1 text-xs font-semibold text-warning">
                                4 baru
                            </span>
                        </CardHeader>
                        <CardContent className="px-5 pt-1.5 pb-4">
                            {approvals.map((ap, i) => (
                                <div
                                    key={ap.name}
                                    className={`flex items-center gap-3 py-2.5 ${i < approvals.length - 1 ? 'border-b border-border/60' : ''}`}
                                >
                                    <div
                                        className={`flex size-[34px] shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white ${ap.avClass}`}
                                    >
                                        {ap.ini}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate text-sm font-medium text-foreground">
                                            {ap.name}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {ap.type}
                                        </div>
                                    </div>
                                    <div className="flex gap-1.5">
                                        <button
                                            type="button"
                                            aria-label={`Setujui pengajuan ${ap.name}`}
                                            onClick={() =>
                                                toast.success('Disetujui', {
                                                    description: `${ap.name} — pengajuan disetujui`,
                                                })
                                            }
                                            className="flex size-[30px] items-center justify-center rounded-[7px] bg-success/10 text-success transition-colors hover:bg-success hover:text-white"
                                        >
                                            <Check className="size-4" />
                                        </button>
                                        <button
                                            type="button"
                                            aria-label={`Tolak pengajuan ${ap.name}`}
                                            onClick={() =>
                                                toast.error('Ditolak', {
                                                    description: `${ap.name} — pengajuan ditolak`,
                                                })
                                            }
                                            className="flex size-[30px] items-center justify-center rounded-[7px] bg-destructive/10 text-destructive transition-colors hover:bg-destructive hover:text-white"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
