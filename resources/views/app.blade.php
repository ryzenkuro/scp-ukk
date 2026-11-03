<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'School Class Progress') }}</title>

    <!-- Preconnect Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/main.tsx'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans selection:bg-indigo-600 selection:text-white">
    @php
        $pageName = $page ?? Route::currentRouteName() ?? 'app';
        $vars = $props ?? get_defined_vars();
        $cleanProps = [];
        $excludedKeys = ['__env', 'app', 'component', 'slot', 'attributes'];

        foreach ($vars as $k => $v) {
            if (in_array($k, $excludedKeys, true) || str_starts_with($k, '__')) {
                continue;
            }
            // Safely serialize Eloquent models, collections, arrays, and primitives
            $cleanProps[$k] = $v;
        }

        // Inject flash status & auth info
        $cleanProps['flash'] = [
            'success' => session('success'),
            'error' => session('error'),
            'status' => session('status'),
            'import_errors' => session('import_errors'),
        ];
        $cleanProps['authUser'] = auth()->user() ? auth()->user()->loadMissing(['role', 'schoolClass']) : null;
        $allErrors = collect($errors->getBags())
            ->map(fn ($bag) => $bag->toArray())
            ->collapse()
            ->toArray();
        $cleanProps['errors'] = !empty($allErrors) ? $allErrors : null;
        $cleanProps['csrfToken'] = csrf_token();
    @endphp

    <div id="root" 
         data-page="{{ $pageName }}" 
         data-props="{{ json_encode($cleanProps, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
    </div>
</body>
</html>
