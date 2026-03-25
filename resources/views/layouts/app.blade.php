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

        <!-- Dark mode init (prevents flash) -->
        <script>
            (function() {
                const theme = document.cookie.match(/(?:^|;\s*)theme=(\w+)/)?.[1];
                if (theme === 'dark') document.documentElement.classList.add('dark');
            })();
        </script>

        <!-- Sidebar collapsed init (prevents flash) -->
        <script>
            (function() {
                const collapsed = document.cookie.match(/(?:^|;\s*)sidebar_collapsed=(\w+)/)?.[1];
                if (collapsed === 'true') document.documentElement.classList.add('sidebar-collapsed');
            })();
        </script>

        <style>
            [x-cloak] { display: none !important; }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div x-data="{
            sidebarOpen: false,
            collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
            toggleCollapse() {
                this.collapsed = !this.collapsed;
                document.cookie = 'sidebar_collapsed=' + this.collapsed + ';path=/;max-age=' + (365*24*60*60) + ';SameSite=Lax';
            }
        }" class="min-h-screen bg-gray-100 dark:bg-gray-900">
            {{-- Mobile sidebar overlay --}}
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-40 bg-gray-600/75 dark:bg-gray-950/80 lg:hidden" @click="sidebarOpen = false">
            </div>

            {{-- Sidebar --}}
            @livewire('navigation-menu')

            {{-- Main content area --}}
            <div class="transition-all duration-300" :class="collapsed ? 'lg:pl-0' : 'lg:pl-64'">
                {{-- Top bar --}}
                <div class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                    {{-- Mobile menu button --}}
                    <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    {{-- Desktop collapse toggle (visible when sidebar is collapsed) --}}
                    <button type="button" x-show="collapsed" x-cloak @click="toggleCollapse()"
                            class="-m-2.5 p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hidden lg:block">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    {{-- Separator --}}
                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-700" :class="collapsed ? '' : 'lg:hidden'" aria-hidden="true"></div>

                    {{-- Page Heading --}}
                    @if (isset($header))
                        <div class="flex flex-1 items-center">
                            <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                {{ $header }}
                            </h1>
                        </div>
                    @endif

                    {{-- Dark mode toggle --}}
                    <div x-data="{
                        darkMode: document.documentElement.classList.contains('dark'),
                        toggle() {
                            this.darkMode = !this.darkMode;
                            document.documentElement.classList.toggle('dark', this.darkMode);
                            document.cookie = 'theme=' + (this.darkMode ? 'dark' : 'light') + ';path=/;max-age=' + (365*24*60*60) + ';SameSite=Lax';
                        }
                    }" class="ml-auto">
                        <button @click="toggle()" type="button"
                                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition">
                            {{-- Sun icon (shown in dark mode) --}}
                            <svg x-show="darkMode" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            {{-- Moon icon (shown in light mode) --}}
                            <svg x-show="!darkMode" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Page Content --}}
                <main class="py-6">
                    <div class="px-4 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
