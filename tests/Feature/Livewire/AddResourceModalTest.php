<?php

namespace Tests\Feature\Livewire;

use App\Enums\ResourceRating;
use App\Enums\TaskState;
use App\Livewire\CareManagement\AddResourceModal;
use App\Models\Problem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddResourceModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $startedTask;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->startedTask = Task::factory()->started()->create([
            'submitted_by' => $this->user->id,
            'started_by' => $this->user->id,
        ]);
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->assertStatus(200);
    }

    public function test_modal_opens_on_event_with_task_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->dispatch('open-add-resource-modal', taskId: $this->startedTask->id)
            ->assertSet('showModal', true)
            ->assertSet('taskId', $this->startedTask->id);
    }

    public function test_can_create_resource_for_started_task(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->dispatch('open-add-resource-modal', taskId: $this->startedTask->id)
            ->set('surveyName', 'Survey 1')
            ->set('atHome', ResourceRating::Better->value)
            ->set('atWork', ResourceRating::Same->value)
            ->set('atPlay', ResourceRating::Worse->value)
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('resource-created');

        $this->assertDatabaseHas('resources', [
            'task_id' => $this->startedTask->id,
            'survey_name' => 'Survey 1',
            'at_home' => ResourceRating::Better->value,
            'at_work' => ResourceRating::Same->value,
            'at_play' => ResourceRating::Worse->value,
            'submitted_by' => $this->user->id,
        ]);
    }

    public function test_cannot_create_resource_for_unstarted_task(): void
    {
        $addedTask = Task::factory()->create([
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->dispatch('open-add-resource-modal', taskId: $addedTask->id)
            ->set('surveyName', 'Survey 1')
            ->set('atHome', ResourceRating::Better->value)
            ->set('atWork', ResourceRating::Same->value)
            ->set('atPlay', ResourceRating::Worse->value)
            ->call('save')
            ->assertHasErrors('taskId');

        $this->assertDatabaseCount('resources', 0);
    }

    public function test_survey_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->dispatch('open-add-resource-modal', taskId: $this->startedTask->id)
            ->set('atHome', ResourceRating::Better->value)
            ->set('atWork', ResourceRating::Same->value)
            ->set('atPlay', ResourceRating::Worse->value)
            ->call('save')
            ->assertHasErrors(['surveyName' => 'required']);
    }

    public function test_ratings_are_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddResourceModal::class)
            ->dispatch('open-add-resource-modal', taskId: $this->startedTask->id)
            ->set('surveyName', 'Survey 1')
            ->call('save')
            ->assertHasErrors(['atHome', 'atWork', 'atPlay']);
    }
}
