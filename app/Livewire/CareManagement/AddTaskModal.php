<?php

namespace App\Livewire\CareManagement;

use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Models\Problem;
use App\Models\Task;
use App\Services\CareManagement\PtrValidationService;
use Livewire\Attributes\On;
use Livewire\Component;

class AddTaskModal extends Component
{
    public bool $showModal = false;

    public int $memberId;

    public ?int $problemId = null;

    public string $taskType = '';

    public string $taskName = '';

    public string $code = '';

    public string $encounterSetting = '';

    #[On('open-add-task-modal')]
    public function openModal(int $problemId): void
    {
        $this->problemId = $problemId;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'taskType' => 'required|string',
            'taskName' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'encounterSetting' => 'nullable|string',
            'problemId' => 'required|exists:problems,id',
        ]);

        $problem = Problem::findOrFail($this->problemId);

        try {
            app(PtrValidationService::class)->validateTaskCreation($problem);
        } catch (\InvalidArgumentException $e) {
            $this->addError('problemId', $e->getMessage());
            return;
        }

        Task::create([
            'problem_id' => $this->problemId,
            'name' => $this->taskName,
            'type' => $this->taskType,
            'code' => $this->code ?: null,
            'encounter_setting' => $this->encounterSetting ?: null,
            'state' => TaskState::Added,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        $this->dispatch('task-created');
        $this->showModal = false;
        $this->reset(['taskType', 'taskName', 'code', 'encounterSetting', 'problemId']);
    }

    public function render()
    {
        return view('livewire.care-management.add-task-modal');
    }
}
