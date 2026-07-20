<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

    <script>
        // This code should be added to <head>.
        // It's used to prevent page load glitches.
        const html = document.querySelector('html');

        const localStorageKey = "{{ env('JS_LOCAL_STORAGE_KEY', 'layoutConfig') }}";
        const localStorageApp = JSON.parse(localStorage.getItem(localStorageKey) || '{}');

        const isLightOrAuto = !localStorageApp.darkTheme || (localStorageApp.darkTheme === undefined && !window.matchMedia('(prefers-color-scheme: dark)').matches);
        const isDarkOrAuto = localStorageApp.darkTheme || (localStorageApp.darkTheme === undefined && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (isLightOrAuto && html.classList.contains('app-dark')) html.classList.remove('app-dark');
        else if (isDarkOrAuto && !html.classList.contains('app-dark')) html.classList.add('app-dark');
    </script>
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>