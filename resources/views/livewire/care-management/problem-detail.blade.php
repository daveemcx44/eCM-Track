<div>
    <x-dialog-modal wire:model="showModal" maxWidth="2xl">
        <x-slot name="title">
            Problem Detail
        </x-slot>

        <x-slot name="content">
            @if($this->problem)
                <div class="grid grid-cols-2 gap-6">
                    <!-- Left: Problem Details -->
                    <div class="space-y-3">
                        <div>
                            <x-label value="Problem Name" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->name }}</p>
                        </div>
                        <div>
                            <x-label value="Type" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->type?->label() }}</p>
                        </div>
                        <div>
                            <x-label value="Code" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->code ?? '—' }}</p>
                        </div>
                        <div>
                            <x-label value="Encounter Setting" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->encounter_setting?->label() ?? '—' }}</p>
                        </div>
                        <div>
                            <x-label value="State" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->state->label() }}</p>
                        </div>
                        <div>
                            <x-label value="Submitted By" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->submittedByUser?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <x-label value="Confirmed By" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->confirmedByUser?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <x-label value="Resolved By" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->problem->resolvedByUser?->name ?? '—' }}</p>
                        </div>
                    </div>

                    <!-- Right: Notes -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Notes</h4>
                        <div class="max-h-64 overflow-y-auto space-y-2 mb-4">
                            @forelse($this->problem->notes as $note)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        <span>{{ $note->creator?->name ?? 'System' }}</span>
                                        <span>{{ $note->created_at->format('m-d-Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $note->content }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">No notes yet.</p>
                            @endforelse
                        </div>

                        <!-- Add Note Form -->
                        <div class="space-y-2">
                            <x-label for="newNote" value="Add Note" />
                            <textarea id="newNote" wire:model="newNote" rows="3" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm text-sm"></textarea>
                            @error('newNote') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            <x-button wire:click="addNote" wire:loading.attr="disabled">
                                Add Note
                            </x-button>
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button class="inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Notify
            </button>

            <button wire:click="$set('showModal', false)" class="ms-3 inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                Close
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
