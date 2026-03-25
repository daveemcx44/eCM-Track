<?php

namespace Tests\Feature\Livewire;

use App\Enums\ProblemState;
use App\Livewire\CareManagement\ProblemDetail;
use App\Models\Member;
use App\Models\Note;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProblemDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->member = Member::factory()->create();
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->assertStatus(200);
    }

    public function test_opens_detail_on_event(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Detail Test Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->dispatch('open-problem-detail', problemId: $problem->id)
            ->assertSet('showModal', true)
            ->assertSet('problemId', $problem->id);
    }

    public function test_confirm_problem_changes_state(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->dispatch('confirm-problem', problemId: $problem->id)
            ->assertDispatched('state-changed');

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals($this->user->id, $problem->confirmed_by);
        $this->assertNotNull($problem->confirmed_at);
    }

    public function test_resolve_problem_changes_state(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->dispatch('resolve-problem', problemId: $problem->id)
            ->assertDispatched('state-changed');

        $problem->refresh();
        $this->assertEquals(ProblemState::Resolved, $problem->state);
    }

    public function test_can_add_note_to_problem(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->dispatch('open-problem-detail', problemId: $problem->id)
            ->set('newNote', 'This is a test note')
            ->call('addNote')
            ->assertSet('newNote', '');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => 'This is a test note',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_state_change_history_is_logged(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProblemDetail::class, ['memberId' => $this->member->id])
            ->dispatch('confirm-problem', problemId: $problem->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => ProblemState::Added->value,
            'to_state' => ProblemState::Confirmed->value,
            'changed_by' => $this->user->id,
        ]);
    }
}
