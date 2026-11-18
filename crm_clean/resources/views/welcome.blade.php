<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Green Hill Group CRM - Ronit</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
        
        <style>
            /* Monolithic Tech / Terminal Theme Override */
            * {
                border-radius: 0 !important;
                box-shadow: none !important;
                font-family: 'Consolas', 'Courier New', Courier, monospace !important;
            }
            body, .min-h-screen, .bg-white, .bg-gray-100, .bg-gray-50, .bg-green-50, .bg-green-50, .bg-yellow-50, .bg-red-50, header, footer, main, .glass-panel {
                background-color: #0a0a0a !important;
                backdrop-filter: none !important;
            }
            body, p, h1, h2, h3, h4, h5, h6, span, div, a, button, input, textarea, select, th, td, label {
                color: #a9c43d !important; /* Green Hill Green */
            }
            .text-gray-900, .text-gray-800, .text-gray-700, .text-gray-600, .text-gray-500, .text-gray-400, .text-dark-green {
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
        </style>
    </head>
    <body class="antialiased min-h-screen overflow-x-hidden relative">
        
        <!-- Professional Header -->
        <header class="absolute top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Green Hill Group" class="h-12 w-auto" />
                    <div class="hidden md:block h-8 w-px bg-gray-300"></div>
                    <span class="hidden md:block text-lg font-bold text-dark-green tracking-tight">CRM - Ronit</span>
                </div>

                @if (Route::has('login'))
                    <nav class="flex gap-4 items-center">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-dark-green hover:text-logo-green transition-colors px-4 py-2">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-600 hover:text-dark-green transition-colors px-4 py-2">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-dark-green hover:bg-[#112417] text-white px-5 py-2.5 rounded-md text-sm font-semibold transition-all duration-300 shadow-md">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <!-- Hero Section -->
        <main class="relative pt-32 pb-16 md:pt-48 md:pb-32 flex items-center justify-center min-h-screen">
            <!-- Decorative background blob -->
            <div class="absolute top-1/4 right-0 w-[600px] h-[600px] bg-logo-green/10 rounded-full mix-blend-multiply filter blur-[100px] opacity-70 transform translate-x-1/3 z-0"></div>
            <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-dark-green/5 rounded-full mix-blend-multiply filter blur-[80px] opacity-70 transform -translate-x-1/4 z-0"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-12 items-center">
                
                <!-- Text Content -->
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-50 border border-green-200 text-sm font-semibold text-dark-green mb-8 shadow-sm">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-logo-green opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-logo-green"></span>
                        </span>
                        WhatsApp Meta API V19.0 Integration
                    </div>
                    
                    <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight mb-6 text-gray-900 leading-[1.1]">
                        Automated WhatsApp <br/>
                        <span class="text-dark-green">Lead Engine</span>
                    </h1>
                    
                    <p class="text-lg md:text-xl text-gray-600 mb-10 leading-relaxed">
                        Transform every WhatsApp inquiry into a structured lead. Manage dynamic conversational flows, real estate projects, and seamlessly hand over hot leads to your sales experts.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('login') }}" class="bg-dark-green text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-[#112417] transition-all duration-300 shadow-lg flex items-center justify-center gap-2 group">
                            Enter Dashboard
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                </div>

                <!-- Feature Cards Grid (Right Side) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 relative">
                    <!-- Card 1 -->
                    <div class="glass-panel p-6 rounded-2xl transform translate-y-8 sm:translate-y-12">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-logo-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">State Machine Bot</h3>
                        <p class="text-sm text-gray-600">Intelligent conversational routing built for real estate.</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-logo-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Lead Enrichment</h3>
                        <p class="text-sm text-gray-600">Automatically captures user names and numbers.</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="glass-panel p-6 rounded-2xl sm:col-span-2 mt-4 sm:mt-0">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-logo-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Dynamic Flow Builder</h3>
                        <p class="text-sm text-gray-600">Customize logic nodes, conditions, and API interactions seamlessly.</p>
                    </div>
                </div>

            </div>
        </main>
        
        <footer class="bg-white border-t border-gray-200 py-8 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" class="h-8 grayscale opacity-50" />
                </div>
                <p class="text-sm text-gray-500 font-medium">
                    &copy; {{ date('Y') }} Green Hill Group. All rights reserved.
                </p>
            </div>
        </footer>

    </body>
</html>
