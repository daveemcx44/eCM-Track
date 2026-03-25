<?php

namespace Tests\Feature\Livewire;

use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Livewire\CareManagement\TaskDetail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(TaskDetail::class)
            ->assertStatus(200);
    }

    public function test_start_task_changes_state(): void
    {
        $task = Task::factory()->create([
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskDetail::class)
            ->dispatch('start-task', taskId: $task->id)
            ->assertDispatched('state-changed');

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertEquals($this->user->id, $task->started_by);
        $this->assertNotNull($task->started_at);
    }

    public function test_complete_task_changes_state(): void
    {
        $task = Task::factory()->started()->create([
            'submitted_by' => $this->user->id,
            'started_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskDetail::class)
            ->dispatch('complete-task', taskId: $task->id)
            ->assertDispatched('state-changed');

        $task->refresh();
        $this->assertEquals(TaskState::Completed, $task->state);
        $this->assertEquals(TaskCompletionType::Completed, $task->completion_type);
    }

    public function test_can_add_note_to_task(): void
    {
        $task = Task::factory()->create([
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskDetail::class)
            ->dispatch('open-task-detail', taskId: $task->id)
            ->set('newNote', 'Task note content')
            ->call('addNote')
            ->assertSet('newNote', '');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Task::class,
            'notable_id' => $task->id,
            'content' => 'Task note content',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_state_change_history_logged_for_task(): void
    {
        $task = Task::factory()->create([
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskDetail::class)
            ->dispatch('start-task', taskId: $task->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => TaskState::Added->value,
            'to_state' => TaskState::Started->value,
            'changed_by' => $this->user->id,
        ]);
    }
}
