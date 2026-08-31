

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="copyright"
            content="Copyright &copy; {{ now()->format('Y') }}. All Rights &reg; Reserved by {{ config('app.url') }}">

        @if (request()->is('/'))
            <meta http-equiv="refresh" content="300">
        @endif

        <title inertia>{{ config('app.name', 'News Portal') }}</title>

        <link href="{{ config('app.app_favicon') }}" rel="icon">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @routes

        @inertiaHead
    </head>

    <body>
        @inertia
    </body>

</html>
