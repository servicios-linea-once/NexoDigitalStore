<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSettings;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function edit(GeneralSettings $settings): Response
    {
        return Inertia::render('Admin/Settings/General', [
            'settings' => $settings->toArray(),
        ]);
    }

    public function update(Request $request, GeneralSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'        => ['required', 'string', 'max:255'],
            'site_active'      => ['required', 'boolean'],
            'contact_email'    => ['required', 'email'],
            'telegram_link'    => ['nullable', 'url'],
            'whatsapp_contact' => ['nullable', 'string'],
            'social_links'     => ['required', 'array'],
            'social_links.facebook'  => ['nullable', 'url'],
            'social_links.instagram' => ['nullable', 'url'],
            'social_links.twitter'   => ['nullable', 'url'],
            // Brand
            'logo_file'        => ['nullable', 'image', 'max:2048'],
            'favicon_file'     => ['nullable', 'image', 'max:1024'],
            // SEO
            'seo_title'        => ['required', 'string', 'max:70'],
            'seo_description'  => ['required', 'string', 'max:160'],
            'seo_keywords'     => ['nullable', 'array'],
        ]);

        // Manejo de Logo
        if ($request->hasFile('logo_file')) {
            $result = $this->cloudinary->uploadProductImage($request->file('logo_file'), 'site-branding');
            $settings->site_logo = $result['url'];
        }

        // Manejo de Favicon
        if ($request->hasFile('favicon_file')) {
            $result = $this->cloudinary->uploadProductImage($request->file('favicon_file'), 'site-branding');
            $settings->site_favicon = $result['url'];
        }

        $settings->site_name        = $validated['site_name'];
        $settings->site_active      = $validated['site_active'];
        $settings->contact_email    = $validated['contact_email'];
        $settings->telegram_link    = $validated['telegram_link'];
        $settings->whatsapp_contact = $validated['whatsapp_contact'];
        $settings->social_links     = $validated['social_links'];
        $settings->seo_title        = $validated['seo_title'];
        $settings->seo_description  = $validated['seo_description'];
        $settings->seo_keywords     = $validated['seo_keywords'] ?? [];

        $settings->save();

        return back()->with('success', 'Ajustes globales actualizados.');
    }
}
