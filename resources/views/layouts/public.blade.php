<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A0A0A">

    <title>{{ $meta['title'] ?? 'Kay Factory Music' }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    <link rel="canonical" href="{{ $meta['canonical'] ?? url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kay Factory Music">
    <meta property="og:title" content="{{ $meta['title'] ?? '' }}">
    <meta property="og:description" content="{{ $meta['description'] ?? '' }}">
    <meta property="og:url" content="{{ $meta['canonical'] ?? url()->current() }}">
    @isset($meta['og_image'])
        <meta property="og:image" content="{{ $meta['og_image'] }}">
    @endisset

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/js/public.jsx'])
</head>
<body class="kfm-public-body">
    <div
        id="app"
        data-page="{{ $page ?? 'home' }}"
        data-props="{{ json_encode($pageProps ?? []) }}"
    ></div>
</body>
</html>