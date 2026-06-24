import { CircleCheckBig, ShieldCheck, Sparkles } from 'lucide-react';
import type { AuthLayoutProps } from '@/types';

const features = [
    'Absensi berbasis GPS, face recognition & kiosk',
    'Payroll, PPh 21 & BPJS sesuai regulasi Indonesia',
    'ESS/MSS mobile — cuti, lembur & slip gaji',
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-dvh bg-background lg:grid-cols-2">
            {/* Form side */}
            <div className="flex items-center justify-center p-6 sm:p-10">
                <div className="w-full max-w-sm motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-500">
                    <img
                        src="/assets/logo-full.png"
                        alt="AvanaHR"
                        className="mb-10 h-11 w-auto object-contain"
                    />
                    {title && (
                        <h1 className="text-2xl font-semibold tracking-tight text-navy">
                            {title}
                        </h1>
                    )}
                    {description && (
                        <p className="mt-1.5 mb-8 text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                    {children}
                    <p className="mt-10 text-center text-xs text-muted-foreground">
                        © 2026 AvanaHR · Advancing People, Empowering Growth
                    </p>
                </div>
            </div>

            {/* Hero side */}
            <div className="relative hidden flex-col justify-center overflow-hidden bg-[linear-gradient(150deg,#0E1A3A_0%,#1c3175_55%,#2F54C9_100%)] p-16 lg:flex">
                {/* Dot grid texture */}
                <div
                    aria-hidden="true"
                    className="absolute inset-0 opacity-60"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle, rgba(255,255,255,0.10) 1px, transparent 1px)',
                        backgroundSize: '22px 22px',
                        maskImage:
                            'radial-gradient(ellipse at 60% 40%, black 30%, transparent 80%)',
                        WebkitMaskImage:
                            'radial-gradient(ellipse at 60% 40%, black 30%, transparent 80%)',
                    }}
                />
                <div
                    aria-hidden="true"
                    className="absolute -top-20 -right-16 size-80 rounded-full bg-sky/20 blur-[2px]"
                />
                <div
                    aria-hidden="true"
                    className="absolute -bottom-24 -left-20 size-96 rounded-full bg-sky/10"
                />

                <div className="relative max-w-md text-white motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-3 motion-safe:duration-700">
                    <span className="mb-7 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-1.5 text-xs font-medium backdrop-blur-sm">
                        <Sparkles className="size-3.5 text-sky" />
                        Platform HRIS / HCM Multi-tenant
                    </span>
                    <h2 className="text-[2.35rem] leading-tight font-bold tracking-tight text-balance">
                        Satu platform untuk seluruh siklus karyawan Anda.
                    </h2>
                    <p className="mt-5 text-[15px] leading-relaxed text-white/70">
                        Dari rekrutmen, absensi berbasis GPS, pengajuan cuti,
                        hingga payroll &amp; slip gaji — semua terintegrasi dan
                        sesuai regulasi Indonesia.
                    </p>

                    <ul className="mt-8 space-y-3">
                        {features.map((f) => (
                            <li key={f} className="flex items-start gap-3">
                                <CircleCheckBig className="mt-0.5 size-5 shrink-0 text-sky" />
                                <span className="text-sm text-white/85">
                                    {f}
                                </span>
                            </li>
                        ))}
                    </ul>

                    <div className="mt-10 flex gap-9 border-t border-white/10 pt-8">
                        <div>
                            <div className="text-2xl font-bold tabular-nums">
                                12.400+
                            </div>
                            <div className="mt-0.5 text-[13px] text-white/60">
                                Karyawan dikelola
                            </div>
                        </div>
                        <div>
                            <div className="text-2xl font-bold tabular-nums">
                                98,7%
                            </div>
                            <div className="mt-0.5 text-[13px] text-white/60">
                                Akurasi payroll
                            </div>
                        </div>
                        <div>
                            <div className="text-2xl font-bold tabular-nums">
                                320+
                            </div>
                            <div className="mt-0.5 text-[13px] text-white/60">
                                Perusahaan
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 inline-flex items-center gap-2 text-xs text-white/55">
                        <ShieldCheck className="size-4 text-sky" />
                        Keamanan setara enterprise · enkripsi end-to-end
                    </div>
                </div>
            </div>
        </div>
    );
}
