<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $appName = \App\Models\Setting::getSetting('app_name') ?: config('app.name', 'Laravel');
            $metaDescription = \App\Models\Setting::getSetting('meta_description') ?: 'Platform cari kos mudah dan terpercaya.';
            $ogImage = \App\Models\Setting::getSetting('og_image') ? asset('storage/' . \App\Models\Setting::getSetting('og_image')) : '';
        @endphp

        <title inertia>{{ $appName }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:title" content="{{ $appName }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        @endif
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
