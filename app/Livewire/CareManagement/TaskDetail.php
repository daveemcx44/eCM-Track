<?php

namespace App\Livewire\CareManagement;

use App\Enums\TaskCompletionType;
use App\Models\Note;
use App\Models\Task;
use App\Services\CareManagement\StateMachineService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskDetail extends Component
{
    public bool $showModal = false;

    public ?int $taskId = null;

    public string $newNote = '';

    #[On('open-task-detail')]
    public function openDetail(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->showModal = true;
    }

    #[Computed]
    public function task()
    {
        if (! $this->taskId) {
            return null;
        }

        return Task::with(['notes.creator', 'stateHistory.changedByUser', 'submittedByUser', 'startedByUser', 'completedByUser', 'problem'])
            ->find($this->taskId);
    }

    #[On('start-task')]
    public function startTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        app(StateMachineService::class)->startTask($task, auth()->user());
        $this->dispatch('state-changed');
    }

    #[On('complete-task')]
    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        app(StateMachineService::class)->completeTask($task, auth()->user(), TaskCompletionType::Completed);
        $this->dispatch('state-changed');
    }

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|string|max:2000',
        ]);

        Note::create([
            'notable_type' => Task::class,
            'notable_id' => $this->taskId,
            'content' => $this->newNote,
            'created_by' => auth()->id(),
        ]);

        $this->newNote = '';
        unset($this->task);
    }

    public function render()
    {
        return view('livewire.care-management.task-detail');
    }
}
