<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWebSettingRequest;
use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-wide web settings: AvanaHR site identity, SEO meta, logo, favicon,
 * contact, and social links. Stored globally (not per tenant) in `settings`.
 */
class WebController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->can('setting.manage'), 403);

        return Inertia::render('settings/web', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pengaturan Web', 'href' => route('web-settings.edit')],
            ],
            'settings' => SiteSettings::forView(),
        ]);
    }

    public function update(UpdateWebSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $current = SiteSettings::all();

        $value = [
            'site_name' => $data['site_name'],
            'tagline' => $data['tagline'] ?? '',
            'meta_keywords' => $data['meta_keywords'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'contact_email' => $data['contact_email'] ?? '',
            'contact_phone' => $data['contact_phone'] ?? '',
            'address' => $data['address'] ?? '',
            'social' => array_merge(SiteSettings::DEFAULTS['social'], $data['social'] ?? []),
            'logo_path' => $current['logo_path'],
            'favicon_path' => $current['favicon_path'],
        ];

        if ($request->hasFile('logo')) {
            if ($current['logo_path']) {
                Storage::disk('public')->delete($current['logo_path']);
            }
            $value['logo_path'] = $request->file('logo')->store('site', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($current['favicon_path']) {
                Storage::disk('public')->delete($current['favicon_path']);
            }
            $value['favicon_path'] = $request->file('favicon')->store('site', 'public');
        }

        Setting::query()->updateOrCreate(
            ['key' => SiteSettings::KEY],
            ['value' => $value, 'type' => 'web'],
        );

        SiteSettings::forget();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan web disimpan.']);

        return back();
    }
}
