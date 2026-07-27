<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show($slug)
    {
        $validSlugs = [
            'tentang-kami' => 'about_us',
            'syarat-ketentuan' => 'terms_conditions',
            'kebijakan-privasi' => 'privacy_policy',
        ];

        if (!array_key_exists($slug, $validSlugs)) {
            abort(404);
        }

        $settingKey = $validSlugs[$slug];
        $content = Setting::getSetting($settingKey);

        $titles = [
            'tentang-kami' => 'Tentang Kami',
            'syarat-ketentuan' => 'Syarat & Ketentuan',
            'kebijakan-privasi' => 'Kebijakan Privasi',
        ];

        return Inertia::render('Public/Page', [
            'title' => $titles[$slug],
            'content' => $content ?? 'Konten belum tersedia.',
        ]);
    }
}
