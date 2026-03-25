<div>
    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            Add Resource
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Survey Name -->
                <div>
                    <x-label for="surveyName" value="Survey Name" />
                    <x-input id="surveyName" type="text" class="mt-1 block w-full" wire:model="surveyName" />
                    @error('surveyName') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- At Home -->
                <div>
                    <x-label for="atHome" value="At Home" />
                    <select id="atHome" wire:model="atHome" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                        <option value="">Select rating...</option>
                        @foreach(\App\Enums\ResourceRating::cases() as $rating)
                            <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                        @endforeach
                    </select>
                    @error('atHome') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- At Work -->
                <div>
                    <x-label for="atWork" value="At Work" />
                    <select id="atWork" wire:model="atWork" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                        <option value="">Select rating...</option>
                        @foreach(\App\Enums\ResourceRating::cases() as $rating)
                            <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                        @endforeach
                    </select>
                    @error('atWork') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>

                <!-- At Play -->
                <div>
                    <x-label for="atPlay" value="At Play" />
                    <select id="atPlay" wire:model="atPlay" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                        <option value="">Select rating...</option>
                        @foreach(\App\Enums\ResourceRating::cases() as $rating)
                            <option value="{{ $rating->value }}">{{ $rating->label() }}</option>
                        @endforeach
                    </select>
                    @error('atPlay') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button wire:click="$set('showModal', false)" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                Cancel
            </button>

            <x-button class="ms-3" wire:click="save" wire:loading.attr="disabled">
                OK
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
