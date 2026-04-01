<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Livewire\Component;

class TenantSwitcher extends Component
{
    public string $selectedTenantId = '';

    public string $newTenantName = '';

    public bool $showCreateForm = false;

    public function mount(): void
    {
        $this->selectedTenantId = (string) (auth()->user()->tenant_id ?? 1);
    }

    public function switchTenant(): void
    {
        if (! auth()->user()->role?->isAdmin()) {
            session()->flash('error', 'Only administrators can switch tenants.');

            return;
        }

        $tenant = Tenant::withoutGlobalScopes()->find($this->selectedTenantId);
        if (! $tenant) {
            session()->flash('error', 'Tenant not found.');

            return;
        }

        // Update only the admin's own tenant_id
        auth()->user()->update(['tenant_id' => $tenant->id]);

        // Update context immediately
        TenantContext::set($tenant->id);

        $this->redirect(request()->header('Referer', '/dashboard'), navigate: false);
    }

    public function createTenant(): void
    {
        if (! auth()->user()->role?->isAdmin()) {
            return;
        }

        $this->validate([
            'newTenantName' => 'required|string|min:2|max:100',
        ]);

        $slug = Str::slug($this->newTenantName);
        $tenant = Tenant::create([
            'name' => $this->newTenantName,
            'slug' => $slug,
        ]);

        $this->newTenantName = '';
        $this->showCreateForm = false;
        $this->selectedTenantId = (string) $tenant->id;

        // Switch to it immediately
        $this->switchTenant();
    }

    public function render()
    {
        $tenants = Tenant::withoutGlobalScopes()->orderBy('id')->get();

        return view('livewire.tenant-switcher', [
            'tenants' => $tenants,
        ]);
    }
}
