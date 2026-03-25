<?php

namespace App\Livewire\CareManagement;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Enums\ResourceRating;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Models\Member;
use App\Models\Problem;
use App\Models\Resource;
use App\Models\Task;
use App\Services\CareManagement\PtrValidationService;
use App\Services\CareManagement\StateMachineService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class CareManagementIndex extends Component
{
    use WithPagination;

    public Member $member;
    public ?string $activeFilter = null;

    // ─── Add Problem Modal ───────────────────────────────────
    public string $problemType = '';
    public string $problemName = '';
    public string $problemCode = '';
    public string $problemEncounterSetting = '';

    // ─── Add Task Modal ──────────────────────────────────────
    public ?int $taskProblemId = null;
    public string $taskType = '';
    public string $taskName = '';
    public string $taskCode = '';
    public string $taskEncounterSetting = '';

    // ─── Add Resource Modal ──────────────────────────────────
    public ?int $resourceTaskId = null;
    public string $surveyName = '';
    public string $atHome = '';
    public string $atWork = '';
    public string $atPlay = '';

    public function mount(Member $member): void
    {
        $this->member = $member;
    }

    // ─── Filters ─────────────────────────────────────────────

    public function setFilter(string $type): void
    {
        $this->activeFilter = $type;
        $this->resetPage();
    }

    public function clearFilter(): void
    {
        $this->activeFilter = null;
        $this->resetPage();
    }

    #[Computed]
    public function problems()
    {
        $query = $this->member->problems()->with('tasks.resources');

        if ($this->activeFilter) {
            $query->where('type', $this->activeFilter);
        }

        return $query->paginate(10);
    }

    // ─── Problem Actions ─────────────────────────────────────

    public function saveProblem(): void
    {
        $this->validate([
            'problemType' => 'required|string',
            'problemName' => 'required|string|max:255',
            'problemCode' => 'nullable|string|max:50',
            'problemEncounterSetting' => 'nullable|string',
        ]);

        Problem::create([
            'member_id' => $this->member->id,
            'type' => $this->problemType,
            'name' => $this->problemName,
            'code' => $this->problemCode ?: null,
            'encounter_setting' => $this->problemEncounterSetting ?: null,
            'state' => ProblemState::Added,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'lock_version' => 0,
        ]);

        $this->reset(['problemType', 'problemName', 'problemCode', 'problemEncounterSetting']);
        unset($this->problems);
    }

    public function confirmProblem(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);
        app(StateMachineService::class)->confirmProblem($problem, auth()->user());
        unset($this->problems);
    }

    public function resolveProblem(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);
        app(StateMachineService::class)->resolveProblem($problem, auth()->user());
        unset($this->problems);
    }

    // ─── Task Actions ────────────────────────────────────────

    public function openAddTaskModal(int $problemId): void
    {
        $this->taskProblemId = $problemId;
        $this->reset(['taskType', 'taskName', 'taskCode', 'taskEncounterSetting']);
    }

    public function saveTask(): void
    {
        $this->validate([
            'taskType' => 'required|string',
            'taskName' => 'required|string|max:255',
            'taskCode' => 'nullable|string|max:50',
            'taskEncounterSetting' => 'nullable|string',
            'taskProblemId' => 'required|exists:problems,id',
        ]);

        $problem = Problem::findOrFail($this->taskProblemId);

        try {
            app(PtrValidationService::class)->validateTaskCreation($problem);
        } catch (\InvalidArgumentException $e) {
            $this->addError('taskProblemId', $e->getMessage());
            return;
        }

        Task::create([
            'problem_id' => $this->taskProblemId,
            'name' => $this->taskName,
            'type' => $this->taskType,
            'code' => $this->taskCode ?: null,
            'encounter_setting' => $this->taskEncounterSetting ?: null,
            'state' => TaskState::Added,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        $this->taskProblemId = null;
        $this->reset(['taskType', 'taskName', 'taskCode', 'taskEncounterSetting']);
        unset($this->problems);
    }

    public function startTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        app(StateMachineService::class)->startTask($task, auth()->user());
        unset($this->problems);
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        app(StateMachineService::class)->completeTask($task, auth()->user(), TaskCompletionType::Completed);
        unset($this->problems);
    }

    // ─── Resource Actions ────────────────────────────────────

    public function openAddResourceModal(int $taskId): void
    {
        $this->resourceTaskId = $taskId;
        $task = Task::findOrFail($taskId);
        $this->surveyName = 'Resource ' . ($task->resources()->count() + 1);
        $this->reset(['atHome', 'atWork', 'atPlay']);
    }

    public function saveResource(): void
    {
        $this->validate([
            'surveyName' => 'required|string|max:255',
            'atHome' => 'required|string',
            'atWork' => 'required|string',
            'atPlay' => 'required|string',
            'resourceTaskId' => 'required|exists:tasks,id',
        ]);

        $task = Task::findOrFail($this->resourceTaskId);

        try {
            app(PtrValidationService::class)->validateResourceCreation($task);
        } catch (\InvalidArgumentException $e) {
            $this->addError('resourceTaskId', $e->getMessage());
            return;
        }

        Resource::create([
            'task_id' => $this->resourceTaskId,
            'survey_name' => $this->surveyName,
            'at_home' => $this->atHome,
            'at_work' => $this->atWork,
            'at_play' => $this->atPlay,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        $this->resourceTaskId = null;
        $this->reset(['surveyName', 'atHome', 'atWork', 'atPlay']);
        unset($this->problems);
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function getTaskProblemName(): string
    {
        if ($this->taskProblemId) {
            return Problem::find($this->taskProblemId)?->name ?? '';
        }
        return '';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.care-management.index');
    }
}
