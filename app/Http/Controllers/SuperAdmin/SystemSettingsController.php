<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WaSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $session = WaSession::where('admin_id', 0)->first();

        return Inertia::render('SuperAdmin/SystemSettings', [
            'settings' => $settings,
            'wa_session' => $session ? [
                'status' => $session->status,
                'phone_number' => $session->phone_number,
                'connected_at' => $session->connected_at?->toISOString(),
                'disconnected_at' => $session->disconnected_at?->toISOString(),
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'footer_text' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'ppn_percent' => 'nullable|numeric|min:0|max:100',
            'pph_percent' => 'nullable|numeric|min:0|max:100',
            'min_withdrawal' => 'nullable|numeric|min:0',
            'link_instagram' => 'nullable|string|max:255',
            'link_facebook' => 'nullable|string|max:255',
            'link_tiktok' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'about_us' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
        ]);

        // Process Text Settings
        $textSettings = [
            'app_name', 'footer_text', 'contact_email', 'contact_phone',
            'ppn_percent', 'pph_percent', 'min_withdrawal',
            'link_instagram', 'link_facebook', 'link_tiktok',
            'meta_description',
            'about_us', 'terms_conditions', 'privacy_policy',
        ];

        foreach ($textSettings as $key) {
            if ($request->has($key)) {
                Setting::setSetting($key, $request->input($key), 'string');
            }
        }

        // Process Logo Upload
        if ($request->hasFile('app_logo')) {
            $oldLogo = Setting::getSetting('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('app_logo')->store('settings', 'public');
            Setting::setSetting('app_logo', $path, 'image');
        }

        // Process OG Image Upload
        if ($request->hasFile('og_image')) {
            $oldOg = Setting::getSetting('og_image');
            if ($oldOg && Storage::disk('public')->exists($oldOg)) {
                Storage::disk('public')->delete($oldOg);
            }
            $path = $request->file('og_image')->store('settings', 'public');
            Setting::setSetting('og_image', $path, 'image');
        }

        // Clear Cache
        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
