{{-- Navigation Links --}}
<nav class="flex flex-1 flex-col pt-2 pb-4 overflow-y-auto overflow-x-hidden min-h-0"
     :class="collapsed ? 'items-center px-2' : 'px-4'">
    <ul role="list" class="flex flex-1 flex-col gap-y-1 w-full" :class="collapsed ? 'items-center' : ''">
        {{-- Main Navigation --}}
        <li>
            <a href="{{ route('dashboard') }}"
               class="group flex items-center rounded-md text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
               :class="collapsed ? 'justify-center p-2.5' : 'gap-x-3 px-3 py-2'"
               :title="collapsed ? '{{ __('Dashboard') }}' : ''">
                <svg class="size-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">{{ __('Dashboard') }}</span>
            </a>
        </li>

        <li>
            <a href="{{ route('members.index') }}"
               class="group flex items-center rounded-md text-sm font-semibold transition {{ request()->routeIs('members.*') || request()->routeIs('care-management.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
               :class="collapsed ? 'justify-center p-2.5' : 'gap-x-3 px-3 py-2'"
               :title="collapsed ? '{{ __('Care Management') }}' : ''">
                <svg class="size-5 shrink-0 {{ request()->routeIs('members.*') || request()->routeIs('care-management.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">{{ __('Care Management') }}</span>
            </a>
        </li>

        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
            <li>
                <a href="{{ route('api-tokens.index') }}"
                   class="group flex items-center rounded-md text-sm font-semibold transition {{ request()->routeIs('api-tokens.index') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                   :class="collapsed ? 'justify-center p-2.5' : 'gap-x-3 px-3 py-2'"
                   :title="collapsed ? '{{ __('API Tokens') }}' : ''">
                    <svg class="size-5 shrink-0 {{ request()->routeIs('api-tokens.index') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">{{ __('API Tokens') }}</span>
                </a>
            </li>
        @endif

        {{-- Team Management --}}
        @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
            <li class="mt-4" x-show="!collapsed">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    {{ __('Manage Team') }}
                </div>
                <ul role="list" class="mt-2 space-y-1">
                    <li>
                        <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}"
                           class="group flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('teams.show') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                            <svg class="size-5 shrink-0 {{ request()->routeIs('teams.show') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            {{ __('Team Settings') }}
                        </a>
                    </li>

                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                        <li>
                            <a href="{{ route('teams.create') }}"
                               class="group flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('teams.create') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                                <svg class="size-5 shrink-0 {{ request()->routeIs('teams.create') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {{ __('Create New Team') }}
                            </a>
                        </li>
                    @endcan

                    @if (Auth::user()->allTeams()->count() > 1)
                        <li class="mt-2">
                            <div class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                {{ __('Switch Teams') }}
                            </div>
                            <ul role="list" class="mt-1 space-y-1">
                                @foreach (Auth::user()->allTeams() as $team)
                                    <x-switchable-team :team="$team" />
                                @endforeach
                            </ul>
                        </li>
                    @endif
                </ul>
            </li>
        @endif
    </ul>
</nav>

{{-- User profile section at bottom --}}
<div class="border-t border-gray-200 dark:border-gray-700 py-3" :class="collapsed ? 'px-2' : 'px-4'" x-data="{ open: false }">
    {{-- Collapsed: just show avatar --}}
    <template x-if="collapsed">
        <div class="flex flex-col items-center gap-2">
            <a href="{{ route('profile.show') }}" title="{{ Auth::user()->name }}"
               class="inline-flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 size-9 hover:ring-2 hover:ring-indigo-500 transition">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <img class="size-9 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                @else
                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                @endif
            </a>
        </div>
    </template>

    {{-- Expanded: full user section --}}
    <template x-if="!collapsed">
        <div>
            {{-- User name button --}}
            <button @click="open = !open" class="flex w-full items-center gap-x-3 rounded-md px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <img class="size-5 rounded-full object-cover shrink-0" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                @else
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 shrink-0">
                        <span class="text-[10px] font-medium text-indigo-600 dark:text-indigo-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </span>
                @endif
                <span class="truncate">{{ Auth::user()->name }}</span>
                <svg class="size-4 text-gray-400 dark:text-gray-500 transition-transform ml-auto" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                </svg>
            </button>

            {{-- Expandable Profile & Logout links --}}
            <div x-show="open" x-transition class="mt-1 space-y-1">
                <a href="{{ route('profile.show') }}"
                   class="group flex items-center gap-x-3 rounded-md px-3 py-2 text-sm {{ request()->routeIs('profile.show') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    <svg class="size-5 shrink-0 {{ request()->routeIs('profile.show') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    {{ __('Profile') }}
                </a>

                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <a href="{{ route('logout') }}" @click.prevent="$root.submit();"
                       class="group flex items-center gap-x-3 rounded-md px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400">
                        <svg class="size-5 shrink-0 text-gray-400 dark:text-gray-500 group-hover:text-red-600 dark:group-hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        {{ __('Log Out') }}
                    </a>
                </form>
            </div>
        </div>
    </template>
</div>
