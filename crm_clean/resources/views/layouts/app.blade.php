<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

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
            body, p, h1, h2, h3, h4, h5, h6, span, div, a, button, input, textarea, select, th, td {
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
            /* Custom Scrollbar for terminal feel */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            ::-webkit-scrollbar-track {
                background: #000; 
                border-left: 1px solid #a9c43d;
            }
            ::-webkit-scrollbar-thumb {
                background: #a9c43d; 
            }

            /* Dense Data Tables */
            table {
                border-collapse: collapse !important;
                width: 100% !important;
                border: 1px solid #a9c43d !important;
            }
            th, td {
                padding: 0.25rem 0.5rem !important; /* Extremely dense padding */
                border: 1px solid #5f7022 !important; /* Dimmer green for cell borders */
            }
            thead th {
                border-bottom: 2px solid #a9c43d !important;
            }

            /* Terminal Marquee Status Bar */
            .terminal-marquee {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: #a9c43d;
                color: #0a0a0a !important;
                overflow: hidden;
                white-space: nowrap;
                padding: 4px 0;
                font-weight: bold;
                font-size: 0.875rem;
                z-index: 50;
                border-top: 1px solid #000;
            }
            .terminal-marquee-content {
                display: inline-block;
                animation: marquee 20s linear infinite;
            }
            .terminal-marquee-content span {
                color: #0a0a0a !important;
                margin-right: 2rem;
            }
            @keyframes marquee {
                0% { transform: translateX(100vw); }
                100% { transform: translateX(-100%); }
            }
            /* Add padding to body so it doesn't overlap the fixed marquee */
            body {
                padding-bottom: 30px;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @php
            try {
                $totalLeads = \App\Models\Lead::count();
                $totalMessages = \App\Models\Message::count();
                $bulkDispatched = \App\Models\BulkMessage::count();
                $activeProjects = \App\Models\Project::count();
                $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.05];
                $cpuLoad = isset($loadAvg[0]) ? number_format($loadAvg[0], 2) : '0.05';
            } catch (\Exception $e) {
                $totalLeads = 0; $totalMessages = 0; $bulkDispatched = 0; $activeProjects = 0; $cpuLoad = '0.00';
            }
        @endphp
        <div class="terminal-marquee">
            <div class="terminal-marquee-content">
                <span>SYS.STAT: ONLINE</span>
                <span>CPU.LOAD: {{ $cpuLoad }}</span>
                <span>MEM.ALLOC: STABLE</span>
                <span>DB.LEADS_IDX: {{ number_format($totalLeads) }}</span>
                <span>WABA.MSGS_PROCESSED: {{ number_format($totalMessages) }}</span>
                <span>WABA.BULK_DISPATCH: {{ number_format($bulkDispatched) }}</span>
                <span>PRJ.ACTIVE: {{ number_format($activeProjects) }}</span>
                <span>SEC.ENCRYPTION: AES-256</span>
                <span>WSS.PING: {{ rand(12, 45) }}ms</span>
            </div>
        </div>
    </body>
</html>
