<div>
    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 lg:hidden">
        <div class="flex h-full flex-col">
            {{-- Close button --}}
            <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
                <a href="{{ route('dashboard') }}">
                    <x-application-mark class="block h-9 w-auto" />
                </a>
                <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-300" @click="sidebarOpen = false">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @include('layouts.partials.sidebar-content')
        </div>
    </div>

    {{-- Desktop sidebar --}}
    <div class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:flex-col transition-all duration-300 overflow-hidden"
         :class="collapsed ? 'lg:w-0' : 'lg:w-64'"
         x-show="!collapsed" x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
        <div class="flex grow flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            {{-- Logo + Collapse toggle --}}
            <div class="flex h-16 shrink-0 items-center border-b border-gray-200 dark:border-gray-700 px-4 gap-3"
                 :class="collapsed ? 'justify-center' : 'justify-between'">
                <a href="{{ route('dashboard') }}" x-show="!collapsed" x-transition.opacity class="shrink-0">
                    <x-application-mark class="block h-9 w-auto" />
                </a>
                {{-- Collapse/Expand toggle button --}}
                <button @click="toggleCollapse()" type="button"
                        class="shrink-0 rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition">
                    {{-- Hamburger / menu icon when collapsed --}}
                    <svg x-show="collapsed" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    {{-- Collapse icon (chevron left) when expanded --}}
                    <svg x-show="!collapsed" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>

            @include('layouts.partials.sidebar-content')
        </div>
    </div>
</div>
