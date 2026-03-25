<div>
    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            Add New Task
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Task Type -->
                <div>
                    <x-label for="taskType" value="Task Type" />
                    <select id="taskType" wire:model="taskType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                        <option value="">Select a type...</option>
                        @foreach(\App\Enums\TaskType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('taskType') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- Task Name -->
                <div>
                    <x-label for="taskName" value="Task Name" />
                    <x-input id="taskName" type="text" class="mt-1 block w-full" wire:model="taskName" />
                    @error('taskName') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- Task Code -->
                <div>
                    <x-label for="taskCode" value="Task Code" />
                    <x-input id="taskCode" type="text" class="mt-1 block w-full" wire:model="code" />
                    @error('code') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- Encounter Setting -->
                <div>
                    <x-label for="taskEncounterSetting" value="Encounter Setting" />
                    <select id="taskEncounterSetting" wire:model="encounterSetting" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                        <option value="">Select setting...</option>
                        @foreach(\App\Enums\EncounterSetting::cases() as $setting)
                            <option value="{{ $setting->value }}">{{ $setting->label() }}</option>
                        @endforeach
                    </select>
                    @error('encounterSetting') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- Associated Problem (read-only) -->
                @if($problemId)
                    <div>
                        <x-label value="Associated Problem" />
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                            {{ \App\Models\Problem::find($problemId)?->name ?? '—' }}
                        </p>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <button wire:click="$set('showModal', false)" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                Cancel
            </button>

            <x-button class="ms-3" wire:click="save" wire:loading.attr="disabled">
                ADD New Task
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
