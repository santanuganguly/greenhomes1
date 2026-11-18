<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            /* Monolithic Tech / Terminal Theme Override */
            * {
                border-radius: 0 !important;
                box-shadow: none !important;
                font-family: 'Consolas', 'Courier New', Courier, monospace !important;
            }
            body, .min-h-screen, .bg-white, .bg-gray-100, .bg-gray-50, .bg-green-50, .bg-green-50, .bg-yellow-50, .bg-red-50 {
                background-color: #0a0a0a !important;
            }
            body, p, h1, h2, h3, h4, h5, h6, span, div, a, button, input, textarea, select, th, td, label {
                color: #a9c43d !important; /* Green Hill Green */
            }
            .text-gray-900, .text-gray-800, .text-gray-700, .text-gray-600, .text-gray-500, .text-gray-400 {
                color: #5f7022 !important; /* Slightly dimmer green for secondary text */
            }
            .border, .border-b, .border-t, .border-l, .border-r, .border-gray-100, .border-gray-200, .border-gray-300 {
                border-color: #a9c43d !important;
            }
            a:hover, button:hover {
                background-color: #a9c43d !important;
                color: #0a0a0a !important;
            }
            a:hover *, button:hover * {
                color: #0a0a0a !important;
            }
            svg {
                color: inherit !important;
                stroke: currentColor !important;
                fill: none !important;
            }
            input, textarea, select {
                background-color: #000 !important;
                border: 1px solid #a9c43d !important;
            }
            input:focus, textarea:focus {
                outline: none !important;
                border-color: #fff !important;
                ring: 0 !important;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/" wire:navigate>
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="w-48 h-auto" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
