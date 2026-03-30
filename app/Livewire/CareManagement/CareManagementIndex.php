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
use App\Models\StateChangeHistory;
use App\Models\User;
use App\Notifications\ProblemAddedNotification;
use App\Notifications\ProblemConfirmedNotification;
use App\Notifications\ProblemResolvedNotification;
use App\Notifications\ProblemUnconfirmedNotification;
use App\Notifications\ProblemUnresolvedNotification;
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
    public string $taskProvider = '';
    public ?string $taskDate = null;
    public ?string $taskDueDate = null;

    // ─── Add Resource Modal ──────────────────────────────────
    public ?int $resourceTaskId = null;
    public string $surveyName = '';
    public string $atHome = '';
    public string $atWork = '';
    public string $atPlay = '';

    // ─── Unconfirm Modal ──────────────────────────────────────
    public ?int $unconfirmProblemId = null;
    public string $unconfirmNote = '';

    // ─── Unresolve Modal ──────────────────────────────────────
    public ?int $unresolveProblemId = null;
    public string $unresolveNote = '';

    // ─── Complete Task Modal ──────────────────────────────────
    public ?int $completeTaskId = null;
    public string $completionReason = '';

    // ─── Uncomplete Task Modal ────────────────────────────────
    public ?int $uncompleteTaskId = null;
    public string $uncompleteTaskNote = '';

    // ─── Goal Completion ─────────────────────────────────────
    public ?int $completeGoalId = null;
    public array $incompleteGoalTasks = [];

    // ─── Goal Associations ─────────────────────────────────────
    public array $selectedGoals = [];
    public ?int $newGoalId = null;
    public array $retroactiveTaskIds = [];

    // ─── View Mode ──────────────────────────────────────────────
    public string $viewMode = 'ptr'; // 'ptr' or 'goal'

    // ─── Filters & Search ─────────────────────────────────────
    public string $search = '';
    public string $statusFilter = '';

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

    public function clearAllFilters(): void
    {
        $this->activeFilter = null;
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function switchView(string $mode): void
    {
        $this->viewMode = $mode;
    }

    #[Computed]
    public function goals()
    {
        return Task::whereHas('problem', fn ($q) => $q->where('member_id', $this->member->id))
            ->where('type', TaskType::Goal)
            ->with(['associatedTasks', 'problem'])
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return (bool) ($this->activeFilter || $this->search || $this->statusFilter);
    }

    #[Computed]
    public function problems()
    {
        $query = $this->member->problems()->with('tasks.resources');

        if ($this->activeFilter) {
            $query->where('type', $this->activeFilter);
        }

        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('state', $this->statusFilter);
        }

        return $query->paginate(10);
    }

    // ─── Problem Actions ─────────────────────────────────────

    public function saveProblem(): void
    {
        if ($this->jiConsentBlocked) {
            return;
        }

        $this->validate([
            'problemType' => 'required|string',
            'problemName' => 'required|string|max:255',
            'problemCode' => 'nullable|string|max:50',
            'problemEncounterSetting' => 'nullable|string',
        ]);

        $problem = Problem::create([
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

        // Audit event: PROBLEM_ADDED
        StateChangeHistory::create([
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => null,
            'to_state' => ProblemState::Added->value,
            'changed_by' => auth()->id(),
            'metadata' => ['event' => 'PROBLEM_ADDED'],
        ]);

        // Notify lead care manager if assigned
        if ($this->member->lead_care_manager) {
            $leadCm = User::find($this->member->lead_care_manager);
            $leadCm?->notify(new ProblemAddedNotification($problem));
        }

        $this->reset(['problemType', 'problemName', 'problemCode', 'problemEncounterSetting']);
        unset($this->problems);
    }

    public function confirmProblem(int $problemId, ?bool $reactivateTasks = null): void
    {
        $problem = Problem::findOrFail($problemId);

        // Check role-based permission
        if (!auth()->user()->can('confirm', $problem)) {
            session()->flash('error', 'You do not have permission to confirm problems.');
            return;
        }

        // Check pessimistic lock
        if ($problem->isLockedByAnother(auth()->id())) {
            $lockedBy = $problem->lockedByUser?->name ?? 'another user';
            session()->flash('error', "This problem is currently locked by {$lockedBy}.");
            return;
        }

        // Check if there are cascaded tasks that could be reactivated (first call only)
        if ($reactivateTasks === null) {
            $cascadedCount = $problem->tasks()
                ->where('state', TaskState::Completed)
                ->where('completion_type', TaskCompletionType::ProblemUnconfirmed)
                ->count();

            if ($cascadedCount > 0) {
                $this->dispatch('show-reactivation-dialog', problemId: $problemId, taskCount: $cascadedCount);
                return;
            }
        }

        app(StateMachineService::class)->confirmProblem($problem, auth()->user());

        // Reactivate cascaded tasks if requested
        if ($reactivateTasks === true) {
            $this->reactivateCascadedTasks($problemId);
        }

        // Notify lead care manager
        $member = $problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new ProblemConfirmedNotification($problem->fresh()));
        }

        unset($this->problems);
    }

    public function openUnconfirmModal(int $problemId): void
    {
        $this->unconfirmProblemId = $problemId;
        $this->unconfirmNote = '';
    }

    public function unconfirmProblem(): void
    {
        $this->validate([
            'unconfirmNote' => 'required|string|min:1',
            'unconfirmProblemId' => 'required|exists:problems,id',
        ], [
            'unconfirmNote.required' => 'An explanatory note is required to unconfirm a problem.',
        ]);

        $problem = Problem::findOrFail($this->unconfirmProblemId);

        // Check role-based permission
        if (!auth()->user()->can('unconfirm', $problem)) {
            session()->flash('error', 'You do not have permission to unconfirm problems.');
            return;
        }

        // Check pessimistic lock
        if ($problem->isLockedByAnother(auth()->id())) {
            $lockedBy = $problem->lockedByUser?->name ?? 'another user';
            session()->flash('error', "This problem is currently locked by {$lockedBy}.");
            return;
        }

        $note = $this->unconfirmNote;
        app(StateMachineService::class)->unconfirmProblem($problem, auth()->user(), $note);

        // Notify lead care manager
        $member = $problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new ProblemUnconfirmedNotification($problem->fresh(), $note));
        }

        $this->reset(['unconfirmProblemId', 'unconfirmNote']);
        unset($this->problems);
    }

    public function reactivateCascadedTasks(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);

        // Find tasks that were auto-completed due to unconfirm
        $cascadedTasks = $problem->tasks()
            ->where('state', TaskState::Completed)
            ->where('completion_type', TaskCompletionType::ProblemUnconfirmed)
            ->get();

        foreach ($cascadedTasks as $task) {
            $fromState = $task->state;
            // Revert to the state before completion (Started if it was started, Added otherwise)
            $targetState = $task->started_at ? TaskState::Started : TaskState::Added;

            $task->update([
                'state' => $targetState,
                'completion_type' => null,
                'completed_by' => null,
                'completed_at' => null,
            ]);

            StateChangeHistory::create([
                'trackable_type' => Task::class,
                'trackable_id' => $task->id,
                'from_state' => $fromState->value,
                'to_state' => $targetState->value,
                'changed_by' => auth()->id(),
                'note' => 'Reactivated after problem re-confirmed',
                'metadata' => ['reactivation' => true],
            ]);
        }

        unset($this->problems);
    }

    public function resolveProblem(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);

        if (!auth()->user()->can('resolve', $problem)) {
            session()->flash('error', 'You do not have permission to resolve problems.');
            return;
        }

        if ($problem->isLockedByAnother(auth()->id())) {
            $lockedBy = $problem->lockedByUser?->name ?? 'another user';
            session()->flash('error', "This problem is currently locked by {$lockedBy}.");
            return;
        }

        app(StateMachineService::class)->resolveProblem($problem, auth()->user());

        // Notify lead care manager
        $member = $problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new ProblemResolvedNotification($problem->fresh()));
        }

        unset($this->problems);
    }

    public function openUnresolveModal(int $problemId): void
    {
        $this->unresolveProblemId = $problemId;
        $this->unresolveNote = '';
    }

    public function unresolveProblem(): void
    {
        $this->validate([
            'unresolveNote' => 'required|string|min:1',
            'unresolveProblemId' => 'required|exists:problems,id',
        ], [
            'unresolveNote.required' => 'An explanatory note is required to unresolve a problem.',
        ]);

        $problem = Problem::findOrFail($this->unresolveProblemId);

        if (!auth()->user()->can('unresolve', $problem)) {
            session()->flash('error', 'You do not have permission to unresolve problems.');
            return;
        }

        if ($problem->isLockedByAnother(auth()->id())) {
            $lockedBy = $problem->lockedByUser?->name ?? 'another user';
            session()->flash('error', "This problem is currently locked by {$lockedBy}.");
            return;
        }

        $note = $this->unresolveNote;
        app(StateMachineService::class)->unresolveProblem($problem, auth()->user(), $note);

        // Notify lead care manager
        $member = $problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new ProblemUnresolvedNotification($problem->fresh(), $note));
        }

        // Check for cascaded tasks that can be reactivated
        $cascadedCount = $problem->tasks()
            ->where('state', TaskState::Completed)
            ->where('completion_type', TaskCompletionType::ProblemResolved)
            ->count();

        if ($cascadedCount > 0) {
            $this->dispatch('show-resolve-reactivation-dialog', problemId: $problem->id, taskCount: $cascadedCount);
        }

        $this->reset(['unresolveProblemId', 'unresolveNote']);
        unset($this->problems);
    }

    public function reactivateResolvedTasks(int $problemId): void
    {
        $problem = Problem::findOrFail($problemId);

        $cascadedTasks = $problem->tasks()
            ->where('state', TaskState::Completed)
            ->where('completion_type', TaskCompletionType::ProblemResolved)
            ->get();

        foreach ($cascadedTasks as $task) {
            $fromState = $task->state;
            $targetState = $task->started_at ? TaskState::Started : TaskState::Added;

            $task->update([
                'state' => $targetState,
                'completion_type' => null,
                'completed_by' => null,
                'completed_at' => null,
            ]);

            StateChangeHistory::create([
                'trackable_type' => Task::class,
                'trackable_id' => $task->id,
                'from_state' => $fromState->value,
                'to_state' => $targetState->value,
                'changed_by' => auth()->id(),
                'note' => 'Reactivated after problem unresolved',
                'metadata' => ['reactivation' => true],
            ]);
        }

        unset($this->problems);
    }

    // ─── Task Actions ────────────────────────────────────────

    public function openAddTaskModal(int $problemId): void
    {
        $this->taskProblemId = $problemId;
        $this->reset(['taskType', 'taskName', 'taskCode', 'taskEncounterSetting', 'taskProvider', 'taskDate', 'taskDueDate']);
    }

    public function saveTask(): void
    {
        $this->validate([
            'taskType' => 'required|string',
            'taskName' => 'required|string|max:255',
            'taskCode' => 'nullable|string|max:50',
            'taskEncounterSetting' => 'nullable|string',
            'taskProvider' => 'nullable|string|max:255',
            'taskDate' => 'nullable|date',
            'taskDueDate' => 'nullable|date',
            'taskProblemId' => 'required|exists:problems,id',
        ]);

        $problem = Problem::findOrFail($this->taskProblemId);

        try {
            app(PtrValidationService::class)->validateTaskCreation($problem);
        } catch (\InvalidArgumentException $e) {
            $this->addError('taskProblemId', $e->getMessage());
            return;
        }

        // CM staff restriction for Goal creation
        if ($this->taskType === 'goal') {
            $userRole = auth()->user()->role;
            if (!$userRole || !$userRole->canCreateGoal()) {
                $this->addError('taskType', 'Only Care Managers, Supervisors, or Admins can create Goals.');
                return;
            }
        }

        $task = Task::create([
            'problem_id' => $this->taskProblemId,
            'name' => $this->taskName,
            'type' => $this->taskType,
            'code' => $this->taskCode ?: null,
            'encounter_setting' => $this->taskEncounterSetting ?: null,
            'provider' => $this->taskProvider ?: null,
            'task_date' => $this->taskDate ?: null,
            'due_date' => $this->taskDueDate ?: null,
            'state' => TaskState::Added,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        // Audit event: TASK_ADDED
        StateChangeHistory::create([
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => null,
            'to_state' => TaskState::Added->value,
            'changed_by' => auth()->id(),
            'metadata' => [
                'event' => 'TASK_ADDED',
                'problem_id' => $this->taskProblemId,
                'member_id' => $problem->member_id,
            ],
        ]);

        // Associate task with selected goals
        if (!empty($this->selectedGoals) && $this->taskType !== 'goal') {
            $task->goals()->sync($this->selectedGoals);
        }

        // Notify lead care manager
        $member = $problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new \App\Notifications\TaskAddedNotification($task));
        }

        // If a Goal was created, trigger retroactive association dialog
        if ($this->taskType === 'goal') {
            $existingTasks = Task::where('problem_id', $this->taskProblemId)
                ->where('id', '!=', $task->id)
                ->where('type', '!=', TaskType::Goal->value)
                ->pluck('name', 'id')
                ->toArray();

            if (!empty($existingTasks)) {
                $this->newGoalId = $task->id;
                $this->retroactiveTaskIds = [];
                $this->taskProblemId = null;
                $this->selectedGoals = [];
                $this->reset(['taskType', 'taskName', 'taskCode', 'taskEncounterSetting', 'taskProvider', 'taskDate', 'taskDueDate']);
                unset($this->problems);
                return;
            }
        }

        $this->taskProblemId = null;
        $this->selectedGoals = [];
        $this->reset(['taskType', 'taskName', 'taskCode', 'taskEncounterSetting', 'taskProvider', 'taskDate', 'taskDueDate']);
        unset($this->problems);
    }

    public function approveTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        if (!auth()->user()->can('approve', $task)) {
            session()->flash('error', 'You do not have permission to approve this task.');
            return;
        }

        app(StateMachineService::class)->approveTask($task, auth()->user());
        unset($this->problems);
    }

    public function startTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        // Block start if task requires approval and hasn't been approved
        if ($task->state === TaskState::Added && $task->type->requiresApproval()) {
            session()->flash('error', 'This task requires approval before it can be started.');
            return;
        }

        app(StateMachineService::class)->startTask($task, auth()->user());

        // Notify lead care manager
        $member = $task->problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new \App\Notifications\TaskStartedNotification($task->fresh()));
        }

        unset($this->problems);
    }

    public function openCompleteTaskModal(int $taskId): void
    {
        $this->completeTaskId = $taskId;
        $this->completionReason = '';
    }

    public function completeTask(): void
    {
        $this->validate([
            'completionReason' => 'required|string|in:completed,cancelled,terminated',
            'completeTaskId' => 'required|exists:tasks,id',
        ]);

        $task = Task::findOrFail($this->completeTaskId);
        $completionType = TaskCompletionType::from($this->completionReason);

        app(StateMachineService::class)->completeTask($task, auth()->user(), $completionType);

        // Notify lead care manager
        $member = $task->problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new \App\Notifications\TaskCompletedNotification($task->fresh()));
        }

        $this->reset(['completeTaskId', 'completionReason']);
        unset($this->problems);
    }

    public function openUncompleteTaskModal(int $taskId): void
    {
        $this->uncompleteTaskId = $taskId;
        $this->uncompleteTaskNote = '';
    }

    public function uncompleteTask(): void
    {
        $this->validate([
            'uncompleteTaskNote' => 'required|string|min:1',
            'uncompleteTaskId' => 'required|exists:tasks,id',
        ], [
            'uncompleteTaskNote.required' => 'An explanatory note is required to uncomplete a task.',
        ]);

        $task = Task::findOrFail($this->uncompleteTaskId);

        if (!auth()->user()->can('uncomplete', $task)) {
            session()->flash('error', 'You do not have permission to uncomplete this task.');
            return;
        }

        $note = $this->uncompleteTaskNote;
        app(StateMachineService::class)->uncompleteTask($task, auth()->user(), $note);

        // Notify lead care manager
        $member = $task->problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new \App\Notifications\TaskUncompletedNotification($task->fresh(), $note));
        }

        $this->reset(['uncompleteTaskId', 'uncompleteTaskNote']);
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

    // ─── Goal Methods ────────────────────────────────────────

    public function openCompleteGoalModal(int $goalId): void
    {
        $goal = Task::where('type', TaskType::Goal)->findOrFail($goalId);
        $incompleteTasks = $goal->associatedTasks()
            ->where('state', '!=', TaskState::Completed->value)
            ->get();

        if ($incompleteTasks->isEmpty()) {
            // All tasks complete — complete the goal directly
            $this->completeGoalDirectly($goalId);
            return;
        }

        // Show confirmation dialog with incomplete tasks
        $this->completeGoalId = $goalId;
        $this->incompleteGoalTasks = $incompleteTasks->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'state' => $t->state->value])->toArray();
    }

    public function confirmCompleteGoal(): void
    {
        if ($this->completeGoalId) {
            $this->completeGoalDirectly($this->completeGoalId);
            $this->completeGoalId = null;
            $this->incompleteGoalTasks = [];
        }
    }

    public function cancelCompleteGoal(): void
    {
        $this->completeGoalId = null;
        $this->incompleteGoalTasks = [];
    }

    private function completeGoalDirectly(int $goalId): void
    {
        $goal = Task::findOrFail($goalId);
        app(StateMachineService::class)->completeTask($goal, auth()->user(), TaskCompletionType::Completed);

        $member = $goal->problem->member;
        if ($member->lead_care_manager) {
            $leadCm = User::find($member->lead_care_manager);
            $leadCm?->notify(new \App\Notifications\TaskCompletedNotification($goal->fresh()));
        }

        unset($this->problems);
        unset($this->goals);
    }

    public function getRetroactiveTasks(): array
    {
        if (!$this->newGoalId) {
            return [];
        }

        $goal = Task::find($this->newGoalId);
        if (!$goal) {
            return [];
        }

        return Task::where('problem_id', $goal->problem_id)
            ->where('id', '!=', $this->newGoalId)
            ->where('type', '!=', TaskType::Goal->value)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function saveRetroactiveAssociations(): void
    {
        if ($this->newGoalId && !empty($this->retroactiveTaskIds)) {
            $goal = Task::where('type', TaskType::Goal)->findOrFail($this->newGoalId);

            foreach ($this->retroactiveTaskIds as $taskId) {
                $task = Task::findOrFail($taskId);
                $task->goals()->syncWithoutDetaching([$this->newGoalId]);

                StateChangeHistory::create([
                    'trackable_type' => Task::class,
                    'trackable_id' => $task->id,
                    'from_state' => $task->state->value,
                    'to_state' => $task->state->value,
                    'changed_by' => auth()->id(),
                    'metadata' => [
                        'event' => 'TASK_GOAL_ASSOCIATED',
                        'goal_id' => $this->newGoalId,
                        'goal_name' => $goal->name,
                    ],
                ]);
            }
        }

        $this->newGoalId = null;
        $this->retroactiveTaskIds = [];
        unset($this->goals);
        unset($this->problems);
    }

    public function skipRetroactiveAssociations(): void
    {
        $this->newGoalId = null;
        $this->retroactiveTaskIds = [];
    }

    public function associateTaskWithGoal(int $taskId, int $goalId): void
    {
        $task = Task::findOrFail($taskId);
        $goal = Task::where('type', TaskType::Goal)->findOrFail($goalId);
        $task->goals()->syncWithoutDetaching([$goalId]);

        StateChangeHistory::create([
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => $task->state->value,
            'to_state' => $task->state->value,
            'changed_by' => auth()->id(),
            'metadata' => [
                'event' => 'TASK_GOAL_ASSOCIATED',
                'goal_id' => $goalId,
                'goal_name' => $goal->name,
            ],
        ]);

        unset($this->goals);
    }

    public function getAvailableGoals(): array
    {
        if (!$this->taskProblemId) {
            return [];
        }

        return Task::whereHas('problem', fn ($q) => $q->where('member_id', $this->member->id))
            ->where('type', TaskType::Goal)
            ->pluck('name', 'id')
            ->toArray();
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function getTaskProblemName(): string
    {
        if ($this->taskProblemId) {
            return Problem::find($this->taskProblemId)?->name ?? '';
        }
        return '';
    }

    public bool $jiConsentBlocked = false;

    public function mount(Member $member): void
    {
        $this->member = $member;
        $this->jiConsentBlocked = $this->member->isJiConsentBlocked();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.care-management.index');
    }
}
