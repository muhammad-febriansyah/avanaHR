<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;

class WebSettingSeeder extends Seeder
{
    /**
     * Seed realistic platform-wide web settings for AvanaHR.
     */
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => SiteSettings::KEY],
            [
                'type' => 'web',
                'value' => [
                    'site_name' => 'AvanaHR',
                    'tagline' => 'Advancing People, Empowering Growth',
                    'meta_keywords' => 'AvanaHR, HRIS, HCM, software HR Indonesia, aplikasi payroll, '
                        .'absensi online, slip gaji, PPh21, BPJS, cuti online, employee self service',
                    'meta_description' => 'AvanaHR adalah platform HRIS/HCM SaaS multi-tenant untuk mengelola '
                        .'karyawan, absensi berbasis GPS, cuti, hingga payroll & slip gaji sesuai regulasi Indonesia.',
                    'logo_path' => null,
                    'favicon_path' => null,
                    'contact_email' => 'support@avanahr.co.id',
                    'contact_phone' => '(021) 5099-9000',
                    'address' => 'Jl. Jend. Sudirman Kav. 52-53, SCBD Lot 8, Jakarta Selatan 12190',
                    'social' => [
                        'facebook' => 'https://facebook.com/avanahr',
                        'instagram' => 'https://instagram.com/avanahr.id',
                        'twitter' => 'https://x.com/avanahr',
                        'linkedin' => 'https://linkedin.com/company/avanahr',
                        'youtube' => 'https://youtube.com/@avanahr',
                        'tiktok' => 'https://tiktok.com/@avanahr.id',
                    ],
                ],
            ],
        );

        SiteSettings::forget();
    }
}
