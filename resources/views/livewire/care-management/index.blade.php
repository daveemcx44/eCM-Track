<div x-data="{
    showAddProblemModal: false,
    showAddTaskModal: false,
    showAddResourceModal: false,
    showConfirmDialog: false,
    showResolveDialog: false,
    showUnconfirmModal: false,
    showUnresolveModal: false,
    showCompleteTaskModal: false,
    showUncompleteTaskModal: false,
    showReactivationDialog: false,
    showResolveReactivationDialog: false,
    showAddNoteModal: false,
    showOutreachModal: false,
    showNotificationSettingsModal: false,
    showLockInfoModal: false,
    lockInfoProblemName: '',
    lockInfoUserName: '',
    confirmProblemId: null,
    resolveProblemId: null,
    reactivationProblemId: null,
    reactivationTaskCount: 0,
    resolveReactivationProblemId: null,
    resolveReactivationTaskCount: 0,
    expanded: [],
    taskExpanded: [],
    toggle(id) {
        const idx = this.expanded.indexOf(id);
        if (idx === -1) { this.expanded.push(id); } else { this.expanded.splice(idx, 1); }
    },
    toggleTask(id) {
        const idx = this.taskExpanded.indexOf(id);
        if (idx === -1) { this.taskExpanded.push(id); } else { this.taskExpanded.splice(idx, 1); }
    },
    isExpanded(id) { return this.expanded.includes(id) },
    isTaskExpanded(id) { return this.taskExpanded.includes(id) }
}" @show-reactivation-dialog.window="reactivationProblemId = $event.detail.problemId; reactivationTaskCount = $event.detail.taskCount; showReactivationDialog = true" @show-resolve-reactivation-dialog.window="resolveReactivationProblemId = $event.detail.problemId; resolveReactivationTaskCount = $event.detail.taskCount; showResolveReactivationDialog = true" >
    <x-slot name="header">Care Management</x-slot>

    @if($jiConsentBlocked)
    <!-- JI Consent Block -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-8 py-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Access Restricted</h3>
        <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">Access to the Care Management module for this member is restricted due to Justice-Involved consent status. Please contact your administrator for more information.</p>
    </div>
    @else

    <!-- Member Header Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-4 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-4">
            <span class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $member->name }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->dob->format('m-d-Y') }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->member_id }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $member->organization }}</span>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
            <button type="button" @click="$wire.set('problemType', '{{ $activeFilter ?? '' }}'); showAddProblemModal = true" class="bg-gray-800 dark:bg-gray-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-500 whitespace-nowrap">Add Problem</button>
            <button type="button" wire:click="openAddNoteModal('member', {{ $member->id }})" @click="showAddNoteModal = true" class="bg-gray-500 dark:bg-gray-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-400 whitespace-nowrap">Notes</button>
            <button type="button" class="bg-indigo-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-indigo-500 whitespace-nowrap">Member Main</button>
            @if(auth()->user()->role?->canLogOutreach())
            <button type="button" @click="showOutreachModal = true" class="bg-teal-600 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-teal-500 whitespace-nowrap">Outreach</button>
            @endif
            @if(auth()->user()->role?->canReleaseLock())
            <button type="button" @click="showNotificationSettingsModal = true" class="bg-orange-500 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-orange-400 whitespace-nowrap">NOTIFY</button>
            @else
            <button type="button" class="bg-orange-500 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-orange-400 whitespace-nowrap">NOTIFY</button>
            @endif
        </div>
    </div>

    {{-- Outreach History Section (CM-OUT-002) --}}
    @php $outreachLogs = $this->outreachLogs; @endphp
    @if($outreachLogs->count() > 0 || (auth()->user()->role?->canLogOutreach() && $this->canLogOutreach))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Outreach Log ({{ $outreachLogs->count() }}/3)</h3>
            @if(auth()->user()->role?->canLogOutreach())
                @if($this->canLogOutreach)
                    <button type="button" @click="showOutreachModal = true" class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-800 font-medium">+ Log Attempt</button>
                @else
                    <span class="text-xs text-gray-400 italic">Maximum of 3 outreach attempts reached</span>
                @endif
            @endif
        </div>
        @if($outreachLogs->count() > 0)
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($outreachLogs as $log)
            <div class="py-2 flex items-center gap-4 text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-28">{{ $log->method->label() }}</span>
                <span class="text-gray-500 dark:text-gray-400 w-36">{{ $log->outreach_date->format('M j, Y g:ia') }}</span>
                <span @class(['px-2 py-0.5 rounded-full text-xs font-semibold', 'bg-green-100 text-green-700' => $log->outcome === \App\Enums\OutreachOutcome::SuccessfulContact, 'bg-gray-100 text-gray-600' => $log->outcome !== \App\Enums\OutreachOutcome::SuccessfulContact])>{{ $log->outcome->label() }}</span>
                <span class="text-gray-400 dark:text-gray-500 text-xs">{{ $log->staff->name ?? '' }}</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400">No outreach attempts logged yet.</p>
        @endif
    </div>
    @endif

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
        <div class="flex-1 min-w-0 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Search & Status Filter Bar -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]" style="position:relative;">
                    <div style="position:absolute;top:0;bottom:0;left:12px;display:flex;align-items:center;pointer-events:none;">
                        <svg style="width:16px;height:16px;color:#9ca3af;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by problem name or code..." style="padding-left:36px;" class="block w-full pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <select wire:model.live="statusFilter" class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\ProblemState::cases() as $state)
                        <option value="{{ $state->value }}">{{ ucfirst($state->value) }}</option>
                    @endforeach
                </select>
                @if($this->hasActiveFilters)
                <button type="button" wire:click="clearAllFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium whitespace-nowrap">Clear filters</button>
                @endif
                @if($viewMode === 'ptr' && $this->carePlans->count() > 0)
                <select wire:model.live="carePlanFilter" class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3">
                    <option value="">All Care Plans</option>
                    @foreach($this->carePlans as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->version_number ? 'Version ' . $cp->version_number : 'Plan #' . $cp->id }}</option>
                    @endforeach
                </select>
                @endif
                <div class="flex gap-1 ml-auto">
                    <button type="button" wire:click="switchView('ptr')" @class(['px-3 py-1.5 text-xs font-medium rounded-md transition', 'bg-indigo-600 text-white' => $viewMode === 'ptr', 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' => $viewMode !== 'ptr'])>PTR View</button>
                    <button type="button" wire:click="switchView('goal')" @class(['px-3 py-1.5 text-xs font-medium rounded-md transition', 'bg-indigo-600 text-white' => $viewMode === 'goal', 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' => $viewMode !== 'goal'])>Goal View</button>
                    <button type="button" wire:click="switchView('care_plan')" @class(['px-3 py-1.5 text-xs font-medium rounded-md transition', 'bg-indigo-600 text-white' => $viewMode === 'care_plan', 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' => $viewMode !== 'care_plan'])>Care Plan</button>
                </div>
            </div>

            @if($viewMode === 'care_plan')
            {{-- ══════ Care Plan View ══════ --}}
            <div class="px-4 py-4">
                {{-- Version Selector --}}
                <div class="flex items-center gap-4 mb-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Care Plan Version:</label>
                    <select wire:model.live="selectedCarePlanId" class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3">
                        <option value="">Select a version...</option>
                        @foreach($this->carePlans as $cp)
                            <option value="{{ $cp->id }}">{{ $cp->version_number ? 'Version ' . $cp->version_number : 'Plan #' . $cp->id }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Care Planning Summary (CM-CP-004) --}}
                @php $summary = $this->carePlanSummary; @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Assessment</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $summary['assessment_type'] ?? 'None' }}</p>
                        <p class="text-xs text-gray-400">{{ $summary['assessment_date'] ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Next Reassessment</p>
                        <p class="mt-1 text-sm font-semibold @if($summary && ($summary['is_overdue'] ?? false)) text-red-600 dark:text-red-400 @else text-gray-900 dark:text-white @endif">
                            {{ $summary['next_reassessment_date'] ?? '—' }}
                            @if($summary && ($summary['is_overdue'] ?? false)) <span class="text-xs ml-1">OVERDUE</span> @endif
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Risk Level</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $summary['risk_level'] ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan Versions</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $summary['version_count'] ?? 0 }} (v{{ $summary['current_version'] ?? '—' }})</p>
                    </div>
                </div>

                @if($selectedCarePlanId)
                {{-- Unsupported Problems (CM-CP-005) --}}
                @php $unsupported = $this->problems->where('is_unsupported', true)->where('unsupported_classification', null); @endphp
                @if($unsupported->count() > 0)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-4">
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2">Unsupported Problems Requiring Classification</h4>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">The following problems are no longer identified by the latest assessment. Please classify each one.</p>
                    @foreach($unsupported as $up)
                    <div class="flex items-center gap-3 py-2 border-t border-amber-200 dark:border-amber-700">
                        <span class="text-sm font-medium text-gray-900 dark:text-white flex-1">{{ $up->name }}</span>
                        <select wire:change="classifyUnsupportedProblem({{ $up->id }}, $event.target.value)" class="text-xs border border-amber-300 dark:border-amber-600 dark:bg-amber-900/30 rounded-md px-2 py-1">
                            <option value="">Classify...</option>
                            @foreach(\App\Enums\ProblemClassification::cases() as $cls)
                                <option value="{{ $cls->value }}">{{ $cls->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Filtered Problem List --}}
                <table class="w-full table-fixed">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            <th class="w-[35%] px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Problem</th>
                            <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tasks</th>
                            <th class="w-[35%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Classification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($this->problems as $problem)
                        <tr @class(['bg-amber-50/50 dark:bg-amber-900/10' => $problem->is_unsupported && !$problem->unsupported_classification])>
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $problem->name }}
                                @if($problem->is_unsupported && !$problem->unsupported_classification)
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200">Unsupported</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span @class(['px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-yellow-100 text-yellow-700' => $problem->state === \App\Enums\ProblemState::Added, 'bg-green-100 text-green-700' => $problem->state === \App\Enums\ProblemState::Confirmed, 'bg-gray-100 text-gray-700' => $problem->state === \App\Enums\ProblemState::Resolved])>{{ ucfirst($problem->state->value) }}</span></td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $problem->tasks->count() }} tasks</td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                @if($problem->unsupported_classification)
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ \App\Enums\ProblemClassification::from($problem->unsupported_classification)->label() }}</span>
                                @elseif($problem->is_unsupported)
                                    <span class="text-xs text-amber-600 italic">Pending classification</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No problems linked to this care plan version.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @else
                <div class="py-8 text-center text-sm text-gray-400">Select a care plan version to view linked problems.</div>
                @endif
            </div>

            @elseif($viewMode === 'goal')
            {{-- ══════ Goal View (Read-Only) ══════ --}}
            <table class="w-full table-fixed">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <th class="w-[40%] px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Goal</th>
                        <th class="w-[30%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Associated Task</th>
                        <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Problem</th>
                        <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($this->goals as $goal)
                        <tr class="bg-indigo-50 dark:bg-indigo-900/20 border-t-2 border-gray-200 dark:border-gray-600 first:border-t-0">
                            <td class="px-5 py-3.5 text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ $goal->name }}</td>
                            <td class="px-4 py-3.5"></td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 dark:text-gray-400">{{ $goal->problem->name }}</td>
                            <td class="px-4 py-3.5"><span @class(['px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' => $goal->state === \App\Enums\TaskState::Added, 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $goal->state === \App\Enums\TaskState::Started, 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $goal->state === \App\Enums\TaskState::Completed])>{{ ucfirst($goal->state->value) }}</span></td>
                        </tr>
                        @foreach($goal->associatedTasks as $assocTask)
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td class="pl-12 py-2.5 text-sm text-gray-400 select-none">↳</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">{{ $assocTask->name }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">{{ $assocTask->problem->name ?? '' }}</td>
                            <td class="px-4 py-2.5"><span @class(['px-2.5 py-1 rounded-full text-xs font-semibold', 'bg-yellow-100 text-yellow-700' => $assocTask->state === \App\Enums\TaskState::Added, 'bg-blue-100 text-blue-700' => $assocTask->state === \App\Enums\TaskState::Approved, 'bg-green-100 text-green-700' => $assocTask->state === \App\Enums\TaskState::Started, 'bg-gray-100 text-gray-700' => $assocTask->state === \App\Enums\TaskState::Completed])>{{ ucfirst($assocTask->state->value) }}</span></td>
                        </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No goals found for this member.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @else
            {{-- ══════ PTR View ══════ --}}
            <table class="w-full table-fixed">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <th class="w-[30%] px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Problem</th>
                        <th class="w-[25%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                        <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Resource</th>
                        <th class="w-[30%] px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($this->problems as $problem)
                        {{-- Problem Row --}}
                        <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border-t-2 border-gray-200 dark:border-gray-600 first:border-t-0">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white align-top">
                                @if($problem->tasks->count() > 0)
                                <button type="button" @click="toggle({{ $problem->id }})" class="inline-flex items-center gap-2 group">
                                    <svg :style="isExpanded({{ $problem->id }}) ? 'transform:rotate(90deg)' : ''" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 shrink-0" style="transition:transform 0.2s" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    <span>{{ $problem->name }}</span>
                                </button>
                                @else
                                {{ $problem->name }}
                                @endif
                            </td>
                            <td class="px-4 py-4"></td>
                            <td class="px-4 py-4"></td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    @if($problem->isLockedByAnother(auth()->id()))
                                        <span class="text-xs text-amber-600 dark:text-amber-400 italic">Locked by {{ $problem->lockedByUser?->name ?? 'another user' }}</span>
                                    @endif
                                    @can('confirm', $problem)
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Added && !$problem->isLockedByAnother(auth()->id())) @click="confirmProblemId = {{ $problem->id }}; showConfirmDialog = true" @endif
                                        @class([
                                            'px-3.5 py-1 rounded-full text-xs font-semibold transition',
                                            'bg-green-500 text-white hover:bg-green-600 shadow-sm' => $problem->state === \App\Enums\ProblemState::Added && !$problem->isLockedByAnother(auth()->id()),
                                            'bg-green-50 text-green-300 dark:bg-green-900/10 dark:text-green-800 cursor-default' => $problem->state !== \App\Enums\ProblemState::Added || $problem->isLockedByAnother(auth()->id()),
                                        ])>Confirm</button>
                                    @else
                                    <span class="px-3.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-300 dark:bg-green-900/10 dark:text-green-800 cursor-default">Confirm</span>
                                    @endcan
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Confirmed) @click="resolveProblemId = {{ $problem->id }}; showResolveDialog = true" @endif
                                        @class([
                                            'px-3.5 py-1 rounded-full text-xs font-semibold transition',
                                            'bg-rose-500 text-white hover:bg-rose-600 shadow-sm' => $problem->state === \App\Enums\ProblemState::Confirmed,
                                            'bg-rose-50 text-rose-300 dark:bg-rose-900/10 dark:text-rose-800 cursor-default' => $problem->state !== \App\Enums\ProblemState::Confirmed,
                                        ])>Resolve</button>
                                    @can('unconfirm', $problem)
                                    <button type="button"
                                        wire:click="openUnconfirmModal({{ $problem->id }})" @click="showUnconfirmModal = true"
                                        class="px-3.5 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600 shadow-sm transition">Unconfirm</button>
                                    @endcan
                                    @can('unresolve', $problem)
                                    <button type="button"
                                        wire:click="openUnresolveModal({{ $problem->id }})" @click="showUnresolveModal = true"
                                        class="px-3.5 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600 shadow-sm transition">Unresolve</button>
                                    @endcan
                                    <button type="button" wire:click="$dispatch('open-problem-detail', { problemId: {{ $problem->id }} })" title="Click to view Problem Details" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                    @if(auth()->user()->role?->canAddNote())
                                    <button type="button" wire:click="openAddNoteModal('problem', {{ $problem->id }})" @click="showAddNoteModal = true" title="Add Note" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-700 dark:text-gray-400 transition shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                    </button>
                                    @endif
                                    <button type="button" wire:click="showStateHistory('problem', {{ $problem->id }})" title="View History" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-700 dark:text-gray-400 transition shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </button>
                                    <button type="button"
                                        @if($problem->state === \App\Enums\ProblemState::Confirmed) wire:click="openAddTaskModal({{ $problem->id }})" @click="showAddTaskModal = true" @endif
                                        title="{{ $problem->state === \App\Enums\ProblemState::Confirmed ? 'Click to ADD Task to Problem' : 'Tasks may be added only if a Problem has been confirmed but not resolved' }}" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-lg text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">+</button>
                                    @if($problem->tasks->count() > 0)
                                    <button type="button" wire:click="$dispatch('open-problem-detail', { problemId: {{ $problem->id }} })" title="View details" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Task Rows (collapsible) --}}
                        @foreach($problem->tasks as $task)
                            <tr x-show="isExpanded({{ $problem->id }})" x-cloak class="bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                <td class="py-3 pl-12 pr-2 align-top">
                                    <span class="text-gray-300 dark:text-gray-600 text-sm">&#8627;</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 align-top">
                                    @if($task->resources->count() > 0)
                                    <button type="button" @click="toggleTask({{ $task->id }})" class="inline-flex items-center gap-1.5 group">
                                        <svg :style="isTaskExpanded({{ $task->id }}) ? 'transform:rotate(90deg)' : ''" class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 shrink-0" style="transition:transform 0.2s" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                        <span>{{ $task->name }}</span>
                                    </button>
                                    @else
                                    {{ $task->name }}
                                    @endif
                                </td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        @if($task->state === \App\Enums\TaskState::Completed && $task->completion_type)
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300">{{ $task->completion_type->label() }}</span>
                                            @can('uncomplete', $task)
                                            <button type="button" wire:click="openUncompleteTaskModal({{ $task->id }})" @click="showUncompleteTaskModal = true" class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600 shadow-sm transition">Uncomplete</button>
                                            @endcan
                                        @elseif($task->state === \App\Enums\TaskState::Added && $task->type->requiresApproval())
                                            @can('approve', $task)
                                            <button type="button" wire:click="approveTask({{ $task->id }})" class="px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600 transition shadow-sm">Approve</button>
                                            @else
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-300 dark:bg-blue-900/10 dark:text-blue-700 cursor-default">Awaiting Approval</span>
                                            @endcan
                                        @elseif($task->state === \App\Enums\TaskState::Added || $task->state === \App\Enums\TaskState::Approved)
                                            <button type="button" wire:click="startTask({{ $task->id }})" class="px-3.5 py-1 rounded-full text-xs font-semibold bg-green-500 text-white hover:bg-green-600 transition shadow-sm">Start</button>
                                        @elseif($task->state === \App\Enums\TaskState::Started)
                                            @if($task->isGoal())
                                                <button type="button" wire:click="openCompleteGoalModal({{ $task->id }})" class="px-3.5 py-1 rounded-full text-xs font-semibold bg-rose-500 text-white hover:bg-rose-600 transition shadow-sm">Complete</button>
                                            @else
                                                <button type="button" wire:click="openCompleteTaskModal({{ $task->id }})" @click="showCompleteTaskModal = true" class="px-3.5 py-1 rounded-full text-xs font-semibold bg-rose-500 text-white hover:bg-rose-600 transition shadow-sm">Complete</button>
                                            @endif
                                        @endif
                                        <button type="button" wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })" title="Click to view Task Details" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                        @if(($task->state === \App\Enums\TaskState::Started || $task->state === \App\Enums\TaskState::Completed) && !in_array($task->completion_type, [\App\Enums\TaskCompletionType::ProblemUnconfirmed, \App\Enums\TaskCompletionType::ProblemResolved]))
                                            <button type="button" wire:click="openAddResourceModal({{ $task->id }})" @click="showAddResourceModal = true" title="Click to ADD Resource to Task" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-lg text-gray-400 hover:bg-gray-300 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">+</button>
                                        @endif
                                        @if($task->resources->count() > 0)
                                        <button type="button" wire:click="$dispatch('open-task-detail', { taskId: {{ $task->id }} })" title="View details" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-gray-400 hover:bg-gray-300 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 transition shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Resource Rows (collapsible under both problem AND task) --}}
                            @foreach($task->resources as $resource)
                                <tr x-show="isExpanded({{ $problem->id }}) && isTaskExpanded({{ $task->id }})" x-cloak class="bg-gray-100/80 dark:bg-gray-700/20">
                                    <td class="py-2.5 pl-12 pr-2">
                                        <span class="text-gray-200 dark:text-gray-700 text-sm">&#8627;</span>
                                    </td>
                                    <td class="py-2.5 pl-12 pr-2">
                                        <span class="text-gray-200 dark:text-gray-700 text-sm">&#8627;</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">{{ $resource->survey_name }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" title="Click to view Resource Details" class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold bg-indigo-100 text-indigo-600 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 transition shrink-0">?</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm">
                                @if($this->hasActiveFilters)
                                    <p class="text-gray-400 dark:text-gray-500">No problems found matching your search criteria.</p>
                                    <button type="button" wire:click="clearAllFilters" class="mt-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium text-sm">Clear filters</button>
                                @else
                                    <p class="text-gray-400 dark:text-gray-500">No problems found for this member.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->problems->links() }}
            </div>
            @endif
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
                <div class="mt-4 space-y-5">
                    <div>
                        <x-label for="taskType" value="{{ __('Task Type') }}" />
                        <select id="taskType" wire:model.live="taskType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Enter Task Type') }}</option>
                            @foreach(\App\Enums\TaskType::cases() as $tt)
                                @if($tt === \App\Enums\TaskType::Goal)
                                    @if(auth()->user()->role?->canCreateGoal())
                                        <option value="{{ $tt->value }}">{{ $tt->label() }}</option>
                                    @endif
                                @else
                                    <option value="{{ $tt->value }}">{{ $tt->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                        <x-input-error for="taskType" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="taskName" value="{{ __('Task Name') }}" />
                        <x-input id="taskName" type="text" class="mt-1 block w-full" wire:model="taskName" />
                        <x-input-error for="taskName" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="taskCode" value="{{ __('Task Code') }}" />
                        <x-input id="taskCode" type="text" class="mt-1 block w-full" wire:model="taskCode" placeholder="{{ __('Optional') }}" />
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
                        <x-label for="taskProvider" value="{{ __('Task Provider') }}" />
                        <x-input id="taskProvider" type="text" class="mt-1 block w-full" wire:model="taskProvider" placeholder="{{ __('Optional') }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="taskDate" value="{{ __('Task Date') }}" />
                            <x-input id="taskDate" type="date" class="mt-1 block w-full" wire:model="taskDate" />
                        </div>
                        <div>
                            <x-label for="taskDueDate" value="{{ __('Due Date') }}" />
                            <x-input id="taskDueDate" type="date" class="mt-1 block w-full" wire:model="taskDueDate" />
                        </div>
                    </div>
                    <div>
                        <x-label value="{{ __('Associated Problem') }}" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $this->getTaskProblemName() }}</p>
                    </div>
                    @if($taskType && $taskType !== 'goal')
                    <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                        This task type may require approval before it can be started.
                    </div>
                    @php $availableGoals = $this->getAvailableGoals(); @endphp
                    @if(count($availableGoals) > 0)
                    <div>
                        <x-label value="{{ __('Associate with Goals (optional)') }}" />
                        <div class="mt-2 space-y-2 max-h-32 overflow-y-auto">
                            @foreach($availableGoals as $goalId => $goalName)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="selectedGoals" value="{{ $goalId }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                {{ $goalName }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endif
                    <x-input-error for="taskProblemId" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showAddTaskModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button class="ms-3" wire:click="saveTask" x-on:click="showAddTaskModal = false" wire:loading.attr="disabled">{{ __('Add Task') }}</x-button>
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

    {{-- Unconfirm Problem Modal --}}
    <div x-show="showUnconfirmModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showUnconfirmModal = false">
        <div class="fixed inset-0" @click="showUnconfirmModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Unconfirm Problem</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will revert the problem to Added status. All incomplete child tasks will be auto-completed with status "Complete – Problem Unconfirmed".</p>
                <div class="mt-4">
                    <x-label for="unconfirmNote" value="{{ __('Explanatory Note (required)') }}" />
                    <textarea id="unconfirmNote" wire:model="unconfirmNote" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Enter the reason for unconfirming this problem..."></textarea>
                    <x-input-error for="unconfirmNote" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showUnconfirmModal = false">{{ __('Cancel') }}</x-secondary-button>
                <button type="button" wire:click="unconfirmProblem" @click="showUnconfirmModal = false" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition">{{ __('Unconfirm') }}</button>
            </div>
        </div>
    </div>

    {{-- Reactivation Dialog (shown when re-confirming a problem that had cascaded tasks) --}}
    <div x-show="showReactivationDialog" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showReactivationDialog = false">
        <div class="fixed inset-0" @click="showReactivationDialog = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Reactivate Tasks?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This problem has <span class="font-semibold" x-text="reactivationTaskCount"></span> task(s) that were auto-completed when it was previously unconfirmed. Would you like to reactivate them?</p>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showReactivationDialog = false; $wire.confirmProblem(reactivationProblemId, false)">{{ __('No, just confirm') }}</x-secondary-button>
                <x-button type="button" @click="showReactivationDialog = false; $wire.confirmProblem(reactivationProblemId, true)">{{ __('Yes, reactivate tasks') }}</x-button>
            </div>
        </div>
    </div>

    {{-- Complete Task Modal --}}
    <div x-show="showCompleteTaskModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showCompleteTaskModal = false">
        <div class="fixed inset-0" @click="showCompleteTaskModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Complete Task</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Select a completion reason:</p>
                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <input type="radio" wire:model="completionReason" value="completed" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Complete – Task completed</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <input type="radio" wire:model="completionReason" value="cancelled" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Complete – Task cancelled</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <input type="radio" wire:model="completionReason" value="terminated" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Complete – Task terminated</span>
                    </label>
                </div>
                <x-input-error for="completionReason" class="mt-2" />
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showCompleteTaskModal = false">{{ __('Cancel') }}</x-secondary-button>
                <button type="button" wire:click="completeTask" @click="showCompleteTaskModal = false" class="inline-flex items-center px-4 py-2 bg-rose-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-600 transition">{{ __('Complete Task') }}</button>
            </div>
        </div>
    </div>

    {{-- Uncomplete Task Modal --}}
    <div x-show="showUncompleteTaskModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showUncompleteTaskModal = false">
        <div class="fixed inset-0" @click="showUncompleteTaskModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Uncomplete Task</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will revert the task to Started status.</p>
                <div class="mt-4">
                    <x-label for="uncompleteTaskNote" value="{{ __('Explanatory Note (required)') }}" />
                    <textarea id="uncompleteTaskNote" wire:model="uncompleteTaskNote" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Enter the reason for uncompleting this task..."></textarea>
                    <x-input-error for="uncompleteTaskNote" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showUncompleteTaskModal = false">{{ __('Cancel') }}</x-secondary-button>
                <button type="button" wire:click="uncompleteTask" @click="showUncompleteTaskModal = false" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition">{{ __('Uncomplete') }}</button>
            </div>
        </div>
    </div>

    {{-- Unresolve Problem Modal --}}
    <div x-show="showUnresolveModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showUnresolveModal = false">
        <div class="fixed inset-0" @click="showUnresolveModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Unresolve Problem</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will revert the problem to Confirmed status. Tasks that were auto-completed will be offered for reactivation.</p>
                <div class="mt-4">
                    <x-label for="unresolveNote" value="{{ __('Explanatory Note (required)') }}" />
                    <textarea id="unresolveNote" wire:model="unresolveNote" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Enter the reason for unresolving this problem..."></textarea>
                    <x-input-error for="unresolveNote" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showUnresolveModal = false">{{ __('Cancel') }}</x-secondary-button>
                <button type="button" wire:click="unresolveProblem" @click="showUnresolveModal = false" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition">{{ __('Unresolve') }}</button>
            </div>
        </div>
    </div>

    {{-- Resolve Reactivation Dialog --}}
    <div x-show="showResolveReactivationDialog" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showResolveReactivationDialog = false">
        <div class="fixed inset-0" @click="showResolveReactivationDialog = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Reactivate Tasks?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This problem has <span class="font-semibold" x-text="resolveReactivationTaskCount"></span> task(s) that were auto-completed when it was resolved. Would you like to reactivate them?</p>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showResolveReactivationDialog = false">{{ __('No, leave as is') }}</x-secondary-button>
                <x-button type="button" @click="showResolveReactivationDialog = false; $wire.reactivateResolvedTasks(resolveReactivationProblemId)">{{ __('Yes, reactivate tasks') }}</x-button>
            </div>
        </div>
    </div>

    {{-- Goal Completion Confirmation Dialog --}}
    @if($completeGoalId)
    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
        <div class="fixed inset-0" wire:click="cancelCompleteGoal"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative">
            <div class="px-6 py-5">
                <p class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('Complete Goal with Incomplete Tasks?') }}</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The following associated tasks are still incomplete:</p>
                <ul class="mt-3 space-y-1">
                    @foreach($incompleteGoalTasks as $it)
                    <li class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 shrink-0"></span>
                        {{ $it['name'] }} <span class="text-xs text-gray-400">({{ ucfirst($it['state']) }})</span>
                    </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">These tasks will remain in their current state.</p>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" wire:click="cancelCompleteGoal">{{ __('Cancel') }}</x-secondary-button>
                <x-button type="button" wire:click="confirmCompleteGoal">{{ __('Complete Anyway') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    {{-- Retroactive Goal Association Dialog --}}
    @if($newGoalId)
    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
        <div class="fixed inset-0" wire:click="skipRetroactiveAssociations"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto relative">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Associate Existing Tasks with Goal') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Select tasks to associate with the newly created Goal. You can skip this and associate tasks later.</p>
                <div class="mt-4 space-y-2 max-h-60 overflow-y-auto">
                    @php $retroTasks = $this->getRetroactiveTasks(); @endphp
                    @forelse($retroTasks as $taskId => $taskName)
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 py-1">
                        <input type="checkbox" wire:model="retroactiveTaskIds" value="{{ $taskId }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        {{ $taskName }}
                    </label>
                    @empty
                    <p class="text-sm text-gray-400">No existing tasks found.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" wire:click="skipRetroactiveAssociations">{{ __('Skip') }}</x-secondary-button>
                <x-button type="button" wire:click="saveRetroactiveAssociations">{{ __('Associate Selected') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ ADD NOTE MODAL (CM-AUD-001) ══════ --}}
    <div x-show="showAddNoteModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showAddNoteModal = false">
        <div class="fixed inset-0" @click="showAddNoteModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Add Note</h3>
                <div class="mt-4">
                    <textarea wire:model="noteContent" rows="4" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Enter your note..."></textarea>
                    <x-input-error for="noteContent" class="mt-2" />
                </div>
                <label class="flex items-center gap-2 mt-3 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" wire:model="noteNotify" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    Notify lead care manager
                </label>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showAddNoteModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button type="button" wire:click="saveNote" @click="showAddNoteModal = false">{{ __('Save Note') }}</x-button>
            </div>
        </div>
    </div>

    {{-- ══════ STATE CHANGE HISTORY MODAL (CM-AUD-002) ══════ --}}
    @if($showHistoryModal)
    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
        <div class="fixed inset-0" wire:click="closeHistoryModal"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-lg sm:mx-auto relative">
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">State Change History — {{ $historyEntityName }}</h3>
                <div class="mt-4 max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($stateHistoryRecords as $record)
                    <div class="py-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $record['from_state'] ?? '—' }}</span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $record['to_state'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            by {{ $record['changed_by'] }} ({{ $record['role'] }}) &middot; {{ $record['created_at'] }}
                        </p>
                        @if(!empty($record['note']))
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded px-2 py-1 italic">"{{ $record['note'] }}"</p>
                        @endif
                    </div>
                    @empty
                    <p class="py-4 text-sm text-gray-400 text-center">No state changes recorded.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" wire:click="closeHistoryModal">{{ __('Close') }}</x-secondary-button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ OUTREACH LOG MODAL (CM-OUT-001) ══════ --}}
    <div x-show="showOutreachModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showOutreachModal = false">
        <div class="fixed inset-0" @click="showOutreachModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Log Outreach Attempt</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <x-label for="outreachMethod" value="{{ __('Method') }}" />
                        <select id="outreachMethod" wire:model="outreachMethod" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Select method...</option>
                            @foreach(\App\Enums\OutreachMethod::cases() as $method)
                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="outreachMethod" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="outreachDate" value="{{ __('Date & Time') }}" />
                        <x-input id="outreachDate" type="datetime-local" class="mt-1 block w-full" wire:model="outreachDate" />
                        <x-input-error for="outreachDate" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="outreachOutcome" value="{{ __('Outcome') }}" />
                        <select id="outreachOutcome" wire:model="outreachOutcome" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Select outcome...</option>
                            @foreach(\App\Enums\OutreachOutcome::cases() as $outcome)
                                <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="outreachOutcome" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="outreachNotes" value="{{ __('Notes (optional)') }}" />
                        <textarea id="outreachNotes" wire:model="outreachNotes" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showOutreachModal = false">{{ __('Cancel') }}</x-secondary-button>
                <x-button type="button" wire:click="saveOutreach" @click="showOutreachModal = false">{{ __('Log Attempt') }}</x-button>
            </div>
        </div>
    </div>

    {{-- ══════ NOTIFICATION SETTINGS MODAL (CM-NOT-001) ══════ --}}
    @if(auth()->user()->role?->canReleaseLock())
    <div x-show="showNotificationSettingsModal" x-cloak class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showNotificationSettingsModal = false">
        <div class="fixed inset-0" @click="showNotificationSettingsModal = false"><div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl sm:w-full sm:max-w-md sm:mx-auto relative" @click.stop>
            <div class="px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Notification Settings</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toggle which events send notifications.</p>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach(\App\Models\NotificationSetting::all() as $setting)
                    <label class="flex items-center justify-between py-3 cursor-pointer">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $setting->event_type->label() }}</span>
                        <input type="checkbox" {{ $setting->enabled ? 'checked' : '' }}
                            wire:click="toggleNotificationSetting('{{ $setting->event_type->value }}')"
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    </label>
                    @endforeach
                </div>

                {{-- Admin Lock Management (CM-CON-003) --}}
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Active Problem Locks</h4>
                    @php $lockedProblems = \App\Models\Problem::whereNotNull('locked_by')->with('lockedByUser')->get(); @endphp
                    @forelse($lockedProblems as $lp)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ $lp->name }} <span class="text-xs text-gray-400">(by {{ $lp->lockedByUser?->name }})</span></span>
                        <button type="button" wire:click="adminReleaseLock({{ $lp->id }})" wire:confirm="Release this lock?" class="text-xs text-red-600 hover:text-red-800 font-medium">Release</button>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">No active locks.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex justify-end px-6 py-4 bg-gray-100 dark:bg-gray-700">
                <x-secondary-button type="button" @click="showNotificationSettingsModal = false">{{ __('Close') }}</x-secondary-button>
            </div>
        </div>
    </div>
    @endif

    {{-- Detail modals --}}
    @livewire('care-management.problem-detail', ['memberId' => $member->id], key('problem-detail-' . $member->id))
    @livewire('care-management.task-detail', key('task-detail'))

    @endif {{-- end JI consent check --}}
</div>
