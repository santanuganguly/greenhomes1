<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Green Hill CRM - Automated Lead Engine</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Inter', 'sans-serif'],
                            }
                        }
                    }
                }
            </script>
        @endif
        
        <style>
            body { 
                font-family: 'Inter', sans-serif; 
                background-color: #0b1120; /* Dark slate */
                color: #f8fafc;
            }
            .glass-header {
                background: rgba(11, 17, 32, 0.8);
                backdrop-filter: blur(12px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.05);
                transition: transform 0.3s ease, border-color 0.3s ease;
            }
            .glass-card:hover {
                transform: translateY(-2px);
                border-color: rgba(16, 185, 129, 0.3);
            }
            .text-gradient {
                background: linear-gradient(to right, #34d399, #10b981);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body class="antialiased min-h-screen relative selection:bg-emerald-500/30">
        
        <!-- Subtle Grid Background -->
        <div class="absolute inset-0 z-[-1] bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPHBhdGggZD0iTTAgNDBoNDBNNDAgMHY0MCIgc3Ryb2tlPSJyZ2JhKDI1NSwgMjU1LCAyNTUsIDAuMDIpIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiLz4KPC9zdmc+')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>

        <div class="flex flex-col min-h-screen">
            <!-- Header -->
            <header class="glass-header sticky top-0 z-50 w-full">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Green Hill Group" class="w-10 h-10 object-contain" />
                        <span class="text-xl font-semibold tracking-tight text-white">Green Hill <span class="font-normal text-slate-400">CRM</span></span>
                    </div>

                    @if (Route::has('login'))
                        <nav class="flex gap-4 items-center">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-emerald-400 hover:text-emerald-300 transition-colors">
                                    Dashboard →
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                                    Sign In
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-5 py-2 rounded-lg text-sm font-semibold transition-colors">
                                        Get Started
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </div>
            </header>

            <!-- Hero Section -->
            <main class="flex-1 flex flex-col justify-center items-center py-20">
                <div class="text-center max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-8 text-xs font-medium text-emerald-400">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        WhatsApp Meta API V19.0 Integration Active
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl font-bold tracking-tight mb-6 text-white leading-tight">
                        The Custom Lead Engine for <span class="text-gradient">Green Hill Group</span>
                    </h1>
                    
                    <p class="text-lg md:text-xl text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                        Exclusively engineered by Ronit to convert every WhatsApp inquiry into a structured lead. Manage premium properties, deploy dynamic conversation flows, and close deals faster than ever.
                    </p>

                    <div>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-8 py-3.5 rounded-xl font-semibold transition-colors">
                            Enter Workspace
                        </a>
                    </div>
                </div>
            </main>
            
            <!-- Features Grid -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-24">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass-card p-6 rounded-2xl">
                        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center mb-5 border border-slate-700">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-white">Smart Interactions</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Deploy intelligent State Machine Bots that route inquiries and nurture leads autonomously 24/7.</p>
                    </div>

                    <div class="glass-card p-6 rounded-2xl">
                        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center mb-5 border border-slate-700">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-white">Lead Enrichment</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Automatically populate comprehensive client profiles from chat history, identifying hot prospects instantly.</p>
                    </div>

                    <div class="glass-card p-6 rounded-2xl">
                        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center mb-5 border border-slate-700">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-white">Campaign Analytics</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Drive massive marketing blasts and track real-time delivery and read rates straight from the dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
