<?php

namespace Tests\Unit\Models;

use App\Enums\EncounterSetting;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Models\Note;
use App\Models\Problem;
use App\Models\Resource;
use App\Models\StateChangeHistory;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_created_with_factory(): void
    {
        $task = Task::factory()->create();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => $task->name,
        ]);
    }

    public function test_type_is_cast_to_task_type_enum(): void
    {
        $task = Task::factory()->create(['type' => TaskType::Goal]);

        $task->refresh();

        $this->assertInstanceOf(TaskType::class, $task->type);
        $this->assertEquals(TaskType::Goal, $task->type);
    }

    public function test_state_is_cast_to_task_state_enum(): void
    {
        $task = Task::factory()->create(['state' => TaskState::Added]);

        $task->refresh();

        $this->assertInstanceOf(TaskState::class, $task->state);
        $this->assertEquals(TaskState::Added, $task->state);
    }

    public function test_completion_type_is_cast_to_enum(): void
    {
        $task = Task::factory()->completed()->create([
            'completion_type' => TaskCompletionType::Completed,
        ]);

        $task->refresh();

        $this->assertInstanceOf(TaskCompletionType::class, $task->completion_type);
        $this->assertEquals(TaskCompletionType::Completed, $task->completion_type);
    }

    public function test_encounter_setting_is_cast_to_enum(): void
    {
        $task = Task::factory()->create(['encounter_setting' => EncounterSetting::Clinic]);

        $task->refresh();

        $this->assertInstanceOf(EncounterSetting::class, $task->encounter_setting);
        $this->assertEquals(EncounterSetting::Clinic, $task->encounter_setting);
    }

    public function test_task_belongs_to_problem(): void
    {
        $problem = Problem::factory()->confirmed()->create();
        $task = Task::factory()->create(['problem_id' => $problem->id]);

        $this->assertInstanceOf(Problem::class, $task->problem);
        $this->assertEquals($problem->id, $task->problem->id);
    }

    public function test_task_has_many_resources(): void
    {
        $task = Task::factory()->started()->create();
        $resource = Resource::factory()->create(['task_id' => $task->id]);

        $this->assertTrue($task->resources->contains($resource));
        $this->assertInstanceOf(Resource::class, $task->resources->first());
    }

    public function test_task_has_morph_many_notes(): void
    {
        $task = Task::factory()->create();
        $note = Note::factory()->create([
            'notable_type' => Task::class,
            'notable_id' => $task->id,
        ]);

        $this->assertTrue($task->notes->contains($note));
        $this->assertInstanceOf(Note::class, $task->notes->first());
    }

    public function test_task_has_morph_many_state_history(): void
    {
        $task = Task::factory()->create();

        StateChangeHistory::create([
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => TaskState::Added->value,
            'to_state' => TaskState::Started->value,
            'changed_by' => $task->submitted_by,
        ]);

        $this->assertCount(1, $task->stateHistory);
        $this->assertInstanceOf(StateChangeHistory::class, $task->stateHistory->first());
    }

    public function test_is_goal_returns_true_for_goal_type(): void
    {
        $task = Task::factory()->goal()->create();

        $this->assertTrue($task->isGoal());
    }

    public function test_is_goal_returns_false_for_non_goal_type(): void
    {
        $task = Task::factory()->create(['type' => TaskType::Referrals]);

        $this->assertFalse($task->isGoal());
    }

    public function test_is_started_returns_true_for_started_task(): void
    {
        $task = Task::factory()->started()->create();

        $this->assertTrue($task->isStarted());
    }

    public function test_is_started_returns_false_for_added_task(): void
    {
        $task = Task::factory()->create();

        $this->assertFalse($task->isStarted());
    }

    public function test_is_completed_returns_true_for_completed_task(): void
    {
        $task = Task::factory()->completed()->create();

        $this->assertTrue($task->isCompleted());
    }

    public function test_is_completed_returns_false_for_started_task(): void
    {
        $task = Task::factory()->started()->create();

        $this->assertFalse($task->isCompleted());
    }
}
