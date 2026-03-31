<?php

namespace App\Services\CareManagement;

use App\Enums\ProblemState;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\StaleModelException;
use App\Models\Note;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StateMachineService
{
    // ─── Problem Transitions ─────────────────────────────────

    public function confirmProblem(Problem $problem, User $user): void
    {
        $this->transitionProblem($problem, ProblemState::Confirmed, $user, null, [
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
        ]);
    }

    public function resolveProblem(Problem $problem, User $user): void
    {
        DB::transaction(function () use ($problem, $user) {
            $this->transitionProblem($problem, ProblemState::Resolved, $user, null, [
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            // Cascade: auto-complete all incomplete child tasks
            $this->cascadeCompleteChildTasks($problem, $user, TaskCompletionType::ProblemResolved);
        });
    }

    public function unconfirmProblem(Problem $problem, User $user, string $noteContent): void
    {
        DB::transaction(function () use ($problem, $user, $noteContent) {
            $this->transitionProblem($problem, ProblemState::Added, $user, $noteContent, [
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            // Cascade: auto-complete all incomplete child tasks
            $this->cascadeCompleteChildTasks($problem, $user, TaskCompletionType::ProblemUnconfirmed);

            // Create mandatory note
            $this->createNote($problem, $user, $noteContent);
        });
    }

    public function unresolveProblem(Problem $problem, User $user, string $noteContent): void
    {
        DB::transaction(function () use ($problem, $user, $noteContent) {
            $this->transitionProblem($problem, ProblemState::Confirmed, $user, $noteContent, [
                'resolved_by' => null,
                'resolved_at' => null,
            ]);

            // Create mandatory note
            $this->createNote($problem, $user, $noteContent);
        });
    }

    // ─── Task Transitions ────────────────────────────────────

    public function approveTask(Task $task, User $user): void
    {
        $this->transitionTask($task, TaskState::Approved, $user);

        $task->approved_by = $user->id;
        $task->approved_at = now();
        $task->save();
    }

    public function startTask(Task $task, User $user): void
    {
        $this->transitionTask($task, TaskState::Started, $user);

        $task->started_by = $user->id;
        $task->started_at = now();
        $task->save();
    }

    public function completeTask(Task $task, User $user, TaskCompletionType $completionType): void
    {
        $this->transitionTask($task, TaskState::Completed, $user);

        $task->completed_by = $user->id;
        $task->completed_at = now();
        $task->completion_type = $completionType;
        $task->save();
    }

    public function uncompleteTask(Task $task, User $user, string $noteContent): void
    {
        DB::transaction(function () use ($task, $user, $noteContent) {
            $this->transitionTask($task, TaskState::Started, $user, $noteContent);

            $task->completed_by = null;
            $task->completed_at = null;
            $task->completion_type = null;
            $task->save();

            // Create mandatory note
            $this->createNote($task, $user, $noteContent);
        });
    }

    // ─── Private Helpers ─────────────────────────────────────

    private function transitionProblem(Problem $problem, ProblemState $targetState, User $user, ?string $note = null, array $extraFields = []): void
    {
        $fromState = $problem->state;

        if (! $fromState->canTransitionTo($targetState)) {
            throw new InvalidStateTransitionException(
                $fromState->value,
                $targetState->value,
                'problem'
            );
        }

        // Ensure lock_version is fresh from DB
        $currentLockVersion = (int) Problem::where('id', $problem->id)->value('lock_version');

        // Optimistic locking — single atomic update with all fields
        $updateData = array_merge([
            'state' => $targetState->value,
            'lock_version' => $currentLockVersion + 1,
        ], $extraFields);

        $affected = Problem::where('id', $problem->id)
            ->where('lock_version', $currentLockVersion)
            ->update($updateData);

        if ($affected === 0) {
            throw new StaleModelException;
        }

        // Sync the in-memory model
        $problem->state = $targetState;
        $problem->lock_version = $currentLockVersion + 1;
        foreach ($extraFields as $key => $value) {
            $problem->$key = $value;
        }

        // Log state change
        StateChangeHistory::create([
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => $fromState->value,
            'to_state' => $targetState->value,
            'changed_by' => $user->id,
            'note' => $note,
        ]);
    }

    private function transitionTask(Task $task, TaskState $targetState, User $user, ?string $note = null): void
    {
        $fromState = $task->state;
        $isGoal = $task->isGoal();

        if (! $fromState->canTransitionTo($targetState, $isGoal)) {
            throw new InvalidStateTransitionException(
                $fromState->value,
                $targetState->value,
                'task'
            );
        }

        $task->state = $targetState;
        $task->save();

        // Log state change with task_type metadata
        StateChangeHistory::create([
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => $fromState->value,
            'to_state' => $targetState->value,
            'changed_by' => $user->id,
            'note' => $note,
            'metadata' => ['task_type' => $task->type->value],
        ]);
    }

    private function cascadeCompleteChildTasks(Problem $problem, User $user, TaskCompletionType $completionType): void
    {
        $incompleteTasks = $problem->tasks()
            ->whereNot('state', TaskState::Completed->value)
            ->get();

        foreach ($incompleteTasks as $task) {
            $fromState = $task->state;

            $task->update([
                'state' => TaskState::Completed,
                'completion_type' => $completionType,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

            StateChangeHistory::create([
                'trackable_type' => Task::class,
                'trackable_id' => $task->id,
                'from_state' => $fromState->value,
                'to_state' => TaskState::Completed->value,
                'changed_by' => $user->id,
                'note' => "Auto-completed: {$completionType->label()}",
                'metadata' => ['cascade' => true, 'trigger' => 'problem_state_change'],
            ]);
        }
    }

    private function createNote($model, User $user, string $content): void
    {
        Note::create([
            'notable_type' => get_class($model),
            'notable_id' => $model->id,
            'content' => $content,
            'created_by' => $user->id,
        ]);
    }
}
