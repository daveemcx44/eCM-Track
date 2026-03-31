<?php

namespace App\Services\CareManagement;

use App\Enums\ProblemState;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Models\Problem;
use App\Models\Task;
use InvalidArgumentException;

class PtrValidationService
{
    /**
     * Validate that a Task can be created for the given Problem.
     * Problem must be in Confirmed state.
     */
    public function validateTaskCreation(Problem $problem): void
    {
        if ($problem->state !== ProblemState::Confirmed) {
            throw new InvalidArgumentException(
                'Tasks can only be added to confirmed problems. Current state: '.$problem->state->label()
            );
        }
    }

    /**
     * Validate that a Resource can be created for the given Task.
     * Task must be in Started state (or Completed - per BRD: resource can be added even if Task is completed).
     */
    public function validateResourceCreation(Task $task): void
    {
        if ($task->type === TaskType::Goal) {
            throw new InvalidArgumentException(
                'Resources cannot be added to Goals, only to non-Goal tasks.'
            );
        }

        if (! in_array($task->state, [TaskState::Started, TaskState::Completed])) {
            throw new InvalidArgumentException(
                'Resources can only be added to started or completed tasks. Current state: '.$task->state->label()
            );
        }
    }
}
