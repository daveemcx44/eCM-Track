<div x-data="{
    showAddProblemModal: false,
    showAddTaskModal: false,
    showAddResourceModal: false,
    showConfirmDialog: false,
    showResolveDialog: false,
    confirmProblemId: null,
    resolveProblemId: null
}">
    <x-slot name="header">Care Management</x-slot>

    <!-- Member Header Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-4 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-4">
            <span class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $member->name }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->dob->format('m-d-Y') }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->member_id }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->organization }}</span>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
            <button type="button" @click="showAddProblemModal = true" class="bg-gray-800 dark:bg-gray-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-500 whitespace-nowrap">Add Problem</button>
            <button type="button" class="bg-gray-500 dark:bg-gray-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-400 whitespace-nowrap">Notes</button>
            <button type="button" class="bg-indigo-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-indigo-500 whitespace-nowrap">Member Main</button>
            <button type="button" class="bg-orange-500 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-orange-400 whitespace-nowrap">NOTIFY</button>
        </div>
    </div>

    <div class="flex gap-4">
        <!-- Left Sidebar: Category Filters -->
        <div class="shrink-0" style="width: 11rem;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow py-2 w-full">
                @foreach(\App\Enums\ProblemType::cases() as $type)
                    <button type="button" wire:click="setFilter('{{ $type->value }}')"
                        @class([
                            'w-full text-center px-3 py-3 text-sm font-medium transition',
                            'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' => $activeFilter === $type->value,
                            'text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700/50' => $activeFilter !== $type->value,
                        ])>
                        {{ $type->label() }}
                    </button>
                @endforeach
                <button type="button" wire:click="clearFilter"
                    @class([
                        'w-full text-center px-3 py-3 text-sm font-medium transition',
                        'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' => !$activeFilter,
                        'text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700/50' => $activeFilter,
                    ])>
                    All Categories
                </button>
            </div>
        </div>

        <!-- Main Content: PTR Table -->
        <div class="flex-1 min-w-0 bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Problem</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Resource</th>
                        <th class="py-3 pr-5 text-right" colspan="5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->problems as $problem)
                        {{-- Problem Row --}}
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                            <td colspan="3" class="px-5 py-3.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $problem->name }}</td>
                            <td class="py-3.5 pr-5" colspan="5">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Added) @click="confirmProblemId = {{ $problem->id }}; showConfirmDialog = true" @endif
                                        @class([
                                            'px-4 py-1.5 rounded-full text-xs font-semibold transition',
                                            'bg-green-500 text-white hover:bg-green-600 shadow-sm' => $problem->state === \App\Enums\ProblemState::Added,
                                            'bg-green-100 text-green-400 dark:bg-green-900/20 dark:text-green-700 cursor-default' => $problem->state !== \App\Enums\ProblemState::Added,
                                        ])>Confirm</button>
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Confirmed) @click="resolveProblemId = {{ $problem->id }}; showResolveDialog = true" @endif
                                        @class([
                                            'px-4 py-1.5 rounded-full text-xs font-semibold transition',
                                            'bg-rose-400 text-white hover:bg-rose-500 shadow-sm' => $problem->state === \App\Enums\ProblemState::Confirmed,
                                            'bg-rose-100 text-rose-300 dark:bg-rose-900/20 dark:text-rose-700 cursor-default' => $problem->state !== \App\Enums\ProblemState::Confirmed,
                                        ])>Resolve</button>
                                    <button type="button" wire:click="$dispatch('open-problem-detail', { problemId: {{ $problem->id }} })" title="Click to view Problem Details" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Confirmed) wire:click="openAddTaskModal({{ $problem->id }})" @click="showAddTaskModal = true" @endif
                                        title="Click to ADD Task to Problem" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xl text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">+</button>
                                    @if($problem->tasks->count() > 0)
                                    <button type="button" wire:click="$dispatch('open-problem-detail', { problemId: {{ $problem->id }} })" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Task Rows --}}
                        @foreach($problem->tasks as $task)
                            <tr class="border-b border-gray-100 dark:border-gray-700/40 bg-gray-100 dark:bg-gray-700/20 hover:bg-gray-200/60 dark:hover:bg-gray-700/40">
                                <td class="py-3 pl-10 text-gray-400 dark:text-gray-500 text-sm select-none">↳</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $task->name }}</td>
                                <td></td>
                                <td class="py-3 pr-5" colspan="5">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        @if($task->state === \App\Enums\TaskState::Added || $task->state === \App\Enums\TaskState::Approved)
                                            <button type="button" wire:click="startTask({{ $task->id }})" class="px-4 py-1.5 rounded-full text-xs font-semibold bg-green-500 text-white hover:bg-green-600 transition shadow-sm">Start</button>
                                        @endif
                                        @if($task->state === \App\Enums\TaskState::Started)
                                            <button type="button" wire:click="completeTask({{ $task->id }})" class="px-4 py-1.5 rounded-full text-xs font-semibold bg-rose-400 text-white hover:bg-rose-500 transition shadow-sm">Complete</button>
                                        @endif
                                        <button type="button" wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })" title="Click to view Task Details" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                        @if($task->state === \App\Enums\TaskState::Started || $task->state === \App\Enums\TaskState::Completed)
                                            <button type="button" wire:click="openAddResourceModal({{ $task->id }})" @click="showAddResourceModal = true" title="Click to ADD Resource to Task" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xl text-gray-400 hover:bg-gray-300 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">+</button>
                                        @endif
                                        @if($task->resources->count() > 0)
                                        <button type="button" wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:bg-gray-300 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Resource Rows --}}
                            @foreach($task->resources as $resource)
                                <tr class="border-b border-gray-100 dark:border-gray-700/30 bg-gray-100 dark:bg-gray-700/20">
                                    <td class="py-2.5 pl-10 text-gray-300 dark:text-gray-600 text-sm select-none">↳</td>
                                    <td class="py-2.5 pl-10 text-gray-300 dark:text-gray-600 text-sm select-none">↳</td>
                                    <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400">{{ $resource->survey_name }}</td>
                                    <td class="py-2.5 pr-5" colspan="5">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" title="Click to view Resource Details" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No problems found for this member.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->problems->links() }}
            </div>
        </div>
    </div>

    {{-- ══════ MODALS ══════ --}}

    {{-- Confirm Problem Dialog --}}
    <div x-show="showConfirmDialog" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showConfirmDialog = false">
        <div class="fixed inset-0" @click="showConfirmDialog = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5"><p class="text-base font-medium text-gray-900 dark:text-gray-100">Would you like to CONFIRM this Problem?</p></div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <button type="button" @click="showConfirmDialog = false; $wire.confirmProblem(confirmProblemId)" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 px-4 py-2">OK</button>
            </div>
        </div>
    </div>

    {{-- Resolve Problem Dialog --}}
    <div x-show="showResolveDialog" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showResolveDialog = false">
        <div class="fixed inset-0" @click="showResolveDialog = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5"><p class="text-base font-medium text-gray-900 dark:text-gray-100">Would you like to RESOLVE this Problem?</p></div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <button type="button" @click="showResolveDialog = false; $wire.resolveProblem(resolveProblemId)" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 px-4 py-2">OK</button>
            </div>
        </div>
    </div>

    {{-- Add Problem Modal --}}
    <div x-show="showAddProblemModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showAddProblemModal = false">
        <div class="fixed inset-0" @click="showAddProblemModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto relative" @click.stop>
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Add New Problem') }}</div>
                <div class="mt-4 space-y-6">
                    <div>
                        <x-label for="problemType" value="{{ __('Problem Type') }}" />
                        <select id="problemType" wire:model="problemType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Select a type...') }}</option>
                            @foreach(\App\Enums\ProblemType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="problemType" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="problemName" value="{{ __('Problem Name') }}" />
                        <x-input id="problemName" type="text" class="mt-1 block w-full" wire:model="problemName" />
                        <x-input-error for="problemName" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="problemCode" value="{{ __('Code') }}" />
                        <x-input id="problemCode" type="text" class="mt-1 block w-full" wire:model="problemCode" placeholder="{{ __('Optional') }}" />
                    </div>
                    <div>
                        <x-label for="problemEncounterSetting" value="{{ __('Encounter Setting') }}" />
                        <select id="problemEncounterSetting" wire:model="problemEncounterSetting" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Select a setting...') }}</option>
                            @foreach(\App\Enums\EncounterSetting::cases() as $setting)
                                <option value="{{ $setting->value }}">{{ $setting->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showAddProblemModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button class="ms-3" wire:click="saveProblem" @click="showAddProblemModal = false">{{ __('Add Problem') }}</x-button>
            </div>
        </div>
    </div>

    {{-- Add Task Modal --}}
    <div x-show="showAddTaskModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showAddTaskModal = false">
        <div class="fixed inset-0" @click="showAddTaskModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto relative" @click.stop>
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Add New Task') }}</div>
                <div class="mt-4 space-y-6">
                    <div>
                        <x-label for="taskType" value="{{ __('Task Type') }}" />
                        <select id="taskType" wire:model="taskType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Enter Task Type') }}</option>
                            @foreach(\App\Enums\TaskType::cases() as $tt)
                                @if($tt !== \App\Enums\TaskType::Goal)
                                    <option value="{{ $tt->value }}">{{ $tt->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                        <x-input-error for="taskType" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="taskName" value="{{ __('Task') }}" />
                        <x-input id="taskName" type="text" class="mt-1 block w-full" wire:model="taskName" />
                        <x-input-error for="taskName" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="taskCode" value="{{ __('Task Code') }}" />
                        <x-input id="taskCode" type="text" class="mt-1 block w-full" wire:model="taskCode" />
                    </div>
                    <div>
                        <x-label for="taskEncounterSetting" value="{{ __('Encounter Setting') }}" />
                        <select id="taskEncounterSetting" wire:model="taskEncounterSetting" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Choose Setting') }}</option>
                            @foreach(\App\Enums\EncounterSetting::cases() as $setting)
                                <option value="{{ $setting->value }}">{{ $setting->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-label value="{{ __('Associated Problem') }}" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $this->getTaskProblemName() }}</p>
                    </div>
                    <x-input-error for="taskProblemId" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showAddTaskModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button class="ms-3" wire:click="saveTask" @click="showAddTaskModal = false">{{ __('Add Task') }}</x-button>
            </div>
        </div>
    </div>

    {{-- Add Resource Modal --}}
    <div x-show="showAddResourceModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showAddResourceModal = false">
        <div class="fixed inset-0" @click="showAddResourceModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto relative" @click.stop>
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Add Resource') }}</div>
                <div class="mt-4 space-y-6">
                    <div>
                        <x-label for="surveyName" value="{{ __('Survey Name') }}" />
                        <x-input id="surveyName" type="text" class="mt-1 block w-full" wire:model="surveyName" />
                        <x-input-error for="surveyName" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="atHome" value="{{ __('At Home') }}" />
                        <select id="atHome" wire:model="atHome" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach(\App\Enums\ResourceRating::cases() as $rating)
                                <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="atHome" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="atWork" value="{{ __('At Work') }}" />
                        <select id="atWork" wire:model="atWork" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach(\App\Enums\ResourceRating::cases() as $rating)
                                <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="atWork" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="atPlay" value="{{ __('At Play') }}" />
                        <select id="atPlay" wire:model="atPlay" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Choose') }}</option>
                            @foreach(\App\Enums\ResourceRating::cases() as $rating)
                                <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="atPlay" class="mt-2" />
                    </div>
                    <x-input-error for="resourceTaskId" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showAddResourceModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button class="ms-3" wire:click="saveResource" @click="showAddResourceModal = false">{{ __('OK') }}</x-button>
            </div>
        </div>
    </div>

    {{-- Detail modals --}}
    @livewire('care-management.problem-detail', ['memberId' => $member->id], key('problem-detail-' . $member->id))
    @livewire('care-management.task-detail', key('task-detail'))
</div>
