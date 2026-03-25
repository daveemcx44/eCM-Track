<?php

namespace App\Livewire\CareManagement;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Models\Problem;
use Livewire\Attributes\On;
use Livewire\Component;

class AddProblemModal extends Component
{
    public bool $showModal = false;

    public int $memberId;

    public string $problemType = '';

    public string $problemName = '';

    public string $code = '';

    public string $encounterSetting = '';

    #[On('open-add-problem-modal')]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'problemType' => 'required|string',
            'problemName' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'encounterSetting' => 'nullable|string',
        ]);

        Problem::create([
            'member_id' => $this->memberId,
            'type' => $this->problemType,
            'name' => $this->problemName,
            'code' => $this->code ?: null,
            'encounter_setting' => $this->encounterSetting ?: null,
            'state' => ProblemState::Added,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'lock_version' => 0,
        ]);

        $this->dispatch('problem-created');
        $this->showModal = false;
        $this->reset(['problemType', 'problemName', 'code', 'encounterSetting']);
    }

    public function render()
    {
        return view('livewire.care-management.add-problem-modal');
    }
}
