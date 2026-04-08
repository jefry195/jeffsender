<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', current_locale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-name" content="{{ config('app.name') }}">

    <link rel="icon" type="image/png" sizes="56x56"
        href="{{ asset(get_option('primary_data')['favicon'] ?? 'favicon.png') }}">
    <title inertia name="app-name">{{ config('app.name', 'Laravel') }}</title>

    <meta name="app-translations" content="{{ getTranslationFile() }}" />

    <script>
        "use strict"
        if (
            localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="{{ asset('assets/js/initialLoader.js') }}" async></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/custom.css') }}" media="all">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/custom-toastr.css') }}" media="all">
    <link rel="stylesheet" href="{{ asset('assets/css/payment.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/initial-loader.css') }}">

    <!-- Scripts -->
    @routes
    @php
        $activeModule = $page['props']['activeModule'] ?? null;
        $commonCss = ['resources/css/app.css', 'resources/scss/admin/main.scss'];

        $entryPoints = $activeModule
            ? ["modules/{$activeModule}/resources/js/app.js", "modules/{$activeModule}/resources/js/Pages/{$page['component']}.vue", ...$commonCss]
            : ['resources/js/app.js', ...$commonCss];

        $buildDir = $activeModule ? 'build-modules/' . Str::studly($activeModule) : 'build';
    @endphp
    @vite($entryPoints, $buildDir)
    @inertiaHead
</head>

<body>
    @include('layouts.loader')
    @inertia
</body>

</html>