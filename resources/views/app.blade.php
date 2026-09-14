<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f0a1e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Biertappen">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title inertia>{{ config('app.name', 'Biertappen') }}</title>
    {{-- Lemon Squeezy overlay checkout. Loaded here so a purchase opens over
         the app rather than navigating away, which matters most inside an
         installed PWA where a redirect can escape the standalone window. --}}
    @lemonJS

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full bg-night text-white antialiased">
    @inertia
</body>
</html>
