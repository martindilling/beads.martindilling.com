<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="{{ mix('js/app.js') }}" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex h-full font-sans antialiased text-black leading-tight bg-grey-lighter">
<div id="app" class="flex-1 block md:flex flex-col border-t-4 border-indigo-lighter">
    <div class="flex-1 flex flex-col pb-4">
        @yield('body')
    </div>
    <div class="mb-6 text-xs text-grey text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
    </div>
</div>
</body>
</html>
