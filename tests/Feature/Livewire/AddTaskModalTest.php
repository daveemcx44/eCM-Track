<?php

namespace Tests\Feature\Livewire;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Livewire\CareManagement\AddTaskModal;
use App\Models\Member;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddTaskModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Member $member;
    private Problem $confirmedProblem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->member = Member::factory()->create();
        $this->confirmedProblem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->assertStatus(200);
    }

    public function test_modal_opens_on_event_with_problem_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $this->confirmedProblem->id)
            ->assertSet('showModal', true)
            ->assertSet('problemId', $this->confirmedProblem->id);
    }

    public function test_can_create_task_for_confirmed_problem(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $this->confirmedProblem->id)
            ->set('taskType', TaskType::Referrals->value)
            ->set('taskName', 'Refer to specialist')
            ->set('code', 'REF-001')
            ->set('encounterSetting', EncounterSetting::Clinic->value)
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('task-created');

        $this->assertDatabaseHas('tasks', [
            'problem_id' => $this->confirmedProblem->id,
            'name' => 'Refer to specialist',
            'type' => TaskType::Referrals->value,
            'state' => TaskState::Added->value,
            'submitted_by' => $this->user->id,
        ]);
    }

    public function test_cannot_create_task_for_unconfirmed_problem(): void
    {
        $addedProblem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $addedProblem->id)
            ->set('taskType', TaskType::Procedure->value)
            ->set('taskName', 'Some procedure')
            ->call('save')
            ->assertHasErrors('problemId');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_type_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $this->confirmedProblem->id)
            ->set('taskName', 'Some task')
            ->call('save')
            ->assertHasErrors(['taskType' => 'required']);
    }

    public function test_task_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $this->confirmedProblem->id)
            ->set('taskType', TaskType::FollowUp->value)
            ->call('save')
            ->assertHasErrors(['taskName' => 'required']);
    }

    public function test_can_create_goal_task(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddTaskModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-task-modal', problemId: $this->confirmedProblem->id)
            ->set('taskType', TaskType::Goal->value)
            ->set('taskName', 'Reduce blood pressure')
            ->call('save')
            ->assertDispatched('task-created');

        $this->assertDatabaseHas('tasks', [
            'name' => 'Reduce blood pressure',
            'type' => TaskType::Goal->value,
        ]);
    }
}
