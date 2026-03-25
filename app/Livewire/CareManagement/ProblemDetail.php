<?php

namespace App\Livewire\CareManagement;

use App\Models\Note;
use App\Models\Problem;
use App\Services\CareManagement\StateMachineService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ProblemDetail extends Component
{
    public bool $showModal = false;

    public ?int $problemId = null;

    public int $memberId;

    public string $newNote = '';

    #[On('open-problem-detail')]
    public function openDetail(int $problemId): void
    {
        $this->problemId = $problemId;
        $this->showModal = true;
    }

    #[Computed]
    public function problem()
    {
        if (! $this->problemId) {
            return null;
        }

        return Problem::with(['notes.creator', 'stateHistory.changedByUser', 'submittedByUser', 'confirmedByUser', 'resolvedByUser'])
            ->find($this->problemId);
    }

    #[On('confirm-problem')]
    public function confirmProblem(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);
        app(StateMachineService::class)->confirmProblem($problem, auth()->user());
        $this->dispatch('state-changed');
    }

    #[On('resolve-problem')]
    public function resolveProblem(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);
        app(StateMachineService::class)->resolveProblem($problem, auth()->user());
        $this->dispatch('state-changed');
    }

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|string|max:2000',
        ]);

        Note::create([
            'notable_type' => Problem::class,
            'notable_id' => $this->problemId,
            'content' => $this->newNote,
            'created_by' => auth()->id(),
        ]);

        $this->newNote = '';
        unset($this->problem);
    }

    public function render()
    {
        return view('livewire.care-management.problem-detail');
    }
}
