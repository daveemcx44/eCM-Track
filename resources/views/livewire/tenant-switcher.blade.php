<div class="border-t border-gray-200 dark:border-gray-700 py-3 px-4">
    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
        Tenant
    </div>
    <div class="flex items-center gap-2">
        <select wire:model="selectedTenantId" wire:change="switchTenant"
                class="w-full text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2">
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }} (#{{ $tenant->id }})</option>
            @endforeach
        </select>
    </div>

    {{-- Create new tenant --}}
    <div class="mt-2">
        @if($showCreateForm)
            <div class="flex items-center gap-2">
                <input type="text" wire:model="newTenantName" placeholder="New tenant name..."
                       class="flex-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2" />
                <button wire:click="createTenant" class="text-xs font-medium text-green-600 hover:text-green-800 dark:text-green-400">Add</button>
                <button wire:click="$set('showCreateForm', false)" class="text-xs font-medium text-gray-400 hover:text-gray-600">Cancel</button>
            </div>
            @error('newTenantName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @else
            <button wire:click="$set('showCreateForm', true)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                + New Tenant
            </button>
        @endif
    </div>
</div>
