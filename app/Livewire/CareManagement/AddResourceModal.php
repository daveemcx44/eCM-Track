<?php

namespace App\Livewire\CareManagement;

use App\Models\Resource;
use App\Models\Task;
use App\Services\CareManagement\PtrValidationService;
use Livewire\Attributes\On;
use Livewire\Component;

class AddResourceModal extends Component
{
    public bool $showModal = false;

    public ?int $taskId = null;

    public string $surveyName = '';

    public string $atHome = '';

    public string $atWork = '';

    public string $atPlay = '';

    #[On('open-add-resource-modal')]
    public function openModal(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'surveyName' => 'required|string|max:255',
            'atHome' => 'required|string',
            'atWork' => 'required|string',
            'atPlay' => 'required|string',
            'taskId' => 'required|exists:tasks,id',
        ]);

        $task = Task::findOrFail($this->taskId);

        try {
            app(PtrValidationService::class)->validateResourceCreation($task);
        } catch (\InvalidArgumentException $e) {
            $this->addError('taskId', $e->getMessage());
            return;
        }

        Resource::create([
            'task_id' => $this->taskId,
            'survey_name' => $this->surveyName,
            'at_home' => $this->atHome,
            'at_work' => $this->atWork,
            'at_play' => $this->atPlay,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        $this->dispatch('resource-created');
        $this->showModal = false;
        $this->reset(['surveyName', 'atHome', 'atWork', 'atPlay', 'taskId']);
    }

    public function render()
    {
        return view('livewire.care-management.add-resource-modal');
    }
}
