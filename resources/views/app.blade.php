<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2b1d15">

        @isset($meta)
            <meta name="description" content="{{ $meta['description'] }}">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            <meta property="og:url" content="{{ url('/') }}">
            @if ($meta['image'])
                <meta property="og:image" content="{{ $meta['image'] }}">
            @endif
            <meta name="twitter:card" content="summary_large_image">
        @endisset

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
