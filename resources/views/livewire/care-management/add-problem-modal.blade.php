<div>
    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            {{ __('Add New Problem') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <!-- Problem Type -->
                <div>
                    <x-label for="problemType" value="{{ __('Problem Type') }}" />
                    <select id="problemType" wire:model="problemType" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Select a type...') }}</option>
                        @foreach(\App\Enums\ProblemType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="problemType" class="mt-2" />
                </div>

                <!-- Problem Name -->
                <div>
                    <x-label for="problemName" value="{{ __('Problem Name') }}" />
                    <x-input id="problemName" type="text" class="mt-1 block w-full" wire:model="problemName" />
                    <x-input-error for="problemName" class="mt-2" />
                </div>

                <!-- Code -->
                <div>
                    <x-label for="code" value="{{ __('Code') }}" />
                    <x-input id="code" type="text" class="mt-1 block w-full" wire:model="code" placeholder="{{ __('Optional') }}" />
                </div>

                <!-- Encounter Setting -->
                <div>
                    <x-label for="encounterSetting" value="{{ __('Encounter Setting') }}" />
                    <select id="encounterSetting" wire:model="encounterSetting" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Select a setting...') }}</option>
                        @foreach(\App\Enums\EncounterSetting::cases() as $setting)
                            <option value="{{ $setting->value }}">{{ $setting->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="encounterSetting" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="save" wire:loading.attr="disabled">
                {{ __('Add Problem') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
