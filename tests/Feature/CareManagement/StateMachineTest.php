<?php

namespace Tests\Feature\CareManagement;

use App\Enums\ProblemState;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Problem;
use App\Models\Task;
use App\Models\User;
use App\Services\CareManagement\PtrValidationService;
use App\Services\CareManagement\StateMachineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StateMachineTest extends TestCase
{
    use RefreshDatabase;

    private StateMachineService $stateMachine;
    private PtrValidationService $ptrValidation;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new StateMachineService();
        $this->ptrValidation = new PtrValidationService();
        $this->user = User::factory()->create();
    }

    public function test_confirm_problem(): void
    {
        $problem = Problem::factory()->create(['state' => ProblemState::Added]);

        $this->stateMachine->confirmProblem($problem, $this->user);

        $problem->refresh();

        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals($this->user->id, $problem->confirmed_by);
        $this->assertNotNull($problem->confirmed_at);
        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => ProblemState::Added->value,
            'to_state' => ProblemState::Confirmed->value,
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_resolve_problem(): void
    {
        $problem = Problem::factory()->confirmed()->create();

        $this->stateMachine->resolveProblem($problem, $this->user);

        $problem->refresh();

        $this->assertEquals(ProblemState::Resolved, $problem->state);
        $this->assertEquals($this->user->id, $problem->resolved_by);
        $this->assertNotNull($problem->resolved_at);
    }

    public function test_cannot_resolve_added_problem(): void
    {
        $problem = Problem::factory()->create(['state' => ProblemState::Added]);

        $this->expectException(InvalidStateTransitionException::class);

        $this->stateMachine->resolveProblem($problem, $this->user);
    }

    public function test_unconfirm_problem_requires_note(): void
    {
        $problem = Problem::factory()->confirmed()->create();
        $noteContent = 'Reason for unconfirming this problem.';

        $this->stateMachine->unconfirmProblem($problem, $this->user, $noteContent);

        $problem->refresh();

        $this->assertEquals(ProblemState::Added, $problem->state);
        $this->assertNull($problem->confirmed_by);
        $this->assertNull($problem->confirmed_at);
        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => $noteContent,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_unresolve_problem(): void
    {
        $problem = Problem::factory()->resolved()->create();
        $noteContent = 'Reason for unresolving this problem.';

        $this->stateMachine->unresolveProblem($problem, $this->user, $noteContent);

        $problem->refresh();

        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertNull($problem->resolved_by);
        $this->assertNull($problem->resolved_at);
        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => $noteContent,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_cascade_complete_tasks_on_resolve(): void
    {
        $problem = Problem::factory()->confirmed()->create();
        $task1 = Task::factory()->started()->create(['problem_id' => $problem->id]);
        $task2 = Task::factory()->started()->create(['problem_id' => $problem->id]);

        $this->stateMachine->resolveProblem($problem, $this->user);

        $task1->refresh();
        $task2->refresh();

        $this->assertEquals(TaskState::Completed, $task1->state);
        $this->assertEquals(TaskState::Completed, $task2->state);
        $this->assertEquals(TaskCompletionType::ProblemResolved, $task1->completion_type);
        $this->assertEquals(TaskCompletionType::ProblemResolved, $task2->completion_type);
    }

    public function test_start_task(): void
    {
        $task = Task::factory()->create(['state' => TaskState::Added]);

        $this->stateMachine->startTask($task, $this->user);

        $task->refresh();

        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertEquals($this->user->id, $task->started_by);
        $this->assertNotNull($task->started_at);
    }

    public function test_complete_task(): void
    {
        $task = Task::factory()->started()->create();

        $this->stateMachine->completeTask($task, $this->user, TaskCompletionType::Completed);

        $task->refresh();

        $this->assertEquals(TaskState::Completed, $task->state);
        $this->assertEquals(TaskCompletionType::Completed, $task->completion_type);
        $this->assertEquals($this->user->id, $task->completed_by);
        $this->assertNotNull($task->completed_at);
    }

    public function test_goal_skips_approval(): void
    {
        $task = Task::factory()->goal()->create([
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        $this->stateMachine->startTask($task, $this->user);

        $task->refresh();

        $this->assertEquals(TaskState::Started, $task->state);
    }

    public function test_uncomplete_task_requires_note(): void
    {
        $task = Task::factory()->completed()->create([
            'completion_type' => TaskCompletionType::Completed,
        ]);
        $noteContent = 'Reason for uncompleting this task.';

        $this->stateMachine->uncompleteTask($task, $this->user, $noteContent);

        $task->refresh();

        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertNull($task->completed_by);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->completion_type);
        $this->assertDatabaseHas('notes', [
            'notable_type' => Task::class,
            'notable_id' => $task->id,
            'content' => $noteContent,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_cannot_create_task_without_confirmed_problem(): void
    {
        $problem = Problem::factory()->create(['state' => ProblemState::Added]);

        $this->expectException(InvalidArgumentException::class);

        $this->ptrValidation->validateTaskCreation($problem);
    }

    public function test_cannot_create_resource_without_started_task(): void
    {
        $task = Task::factory()->create(['state' => TaskState::Added]);

        $this->expectException(InvalidArgumentException::class);

        $this->ptrValidation->validateResourceCreation($task);
    }
}
