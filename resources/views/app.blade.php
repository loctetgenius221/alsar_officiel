<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="{{ '#faf7f1' }}" />

        <link rel="icon" href="/images/favicon-32.png" sizes="32x32" type="image/png" />
        <link rel="icon" href="/images/favicon-16.png" sizes="16x16" type="image/png" />
        <link rel="icon" href="/images/favicon-192.png" sizes="192x192" type="image/png" />
        <link rel="apple-touch-icon" href="/images/favicon-180.png" />
        <link rel="manifest" href="/site.webmanifest" />

        <title inertia>{{ config('app.name', 'ALSAR') }}</title>

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="bg-paper text-ink antialiased">
        @inertia
    </body>
</html>
