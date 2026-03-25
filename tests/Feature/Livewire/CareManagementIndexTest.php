<?php

namespace Tests\Feature\Livewire;

use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\Problem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CareManagementIndexTest extends TestCase
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

    public function test_page_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('care-management.index', $this->member))
            ->assertStatus(200);
    }

    public function test_page_redirects_unauthenticated_user(): void
    {
        $this->get(route('care-management.index', $this->member))
            ->assertRedirect('/login');
    }

    public function test_member_info_is_displayed(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee($this->member->name)
            ->assertSee($this->member->member_id)
            ->assertSee($this->member->organization);
    }

    public function test_problems_are_displayed(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Test Headache Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Test Headache Problem');
    }

    public function test_filter_by_problem_type(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Physical Problem',
            'type' => ProblemType::Physical,
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Behavioral Problem',
            'type' => ProblemType::Behavioral,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        // See both initially
        $component->assertSee('Physical Problem')
            ->assertSee('Behavioral Problem');

        // Filter to Physical only
        $component->call('setFilter', ProblemType::Physical->value)
            ->assertSee('Physical Problem')
            ->assertDontSee('Behavioral Problem');

        // Clear filter — see both
        $component->call('clearFilter')
            ->assertSee('Physical Problem')
            ->assertSee('Behavioral Problem');
    }

    public function test_tasks_displayed_under_problems(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'name' => 'Test Task Under Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Test Task Under Problem');
    }

    public function test_confirm_button_shown_for_added_problems(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Confirm');
    }

    public function test_resolve_button_shown_for_confirmed_problems(): void
    {
        Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Resolve');
    }

    public function test_add_problem_button_visible(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Add Problem');
    }

    public function test_category_filters_displayed(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Physical')
            ->assertSee('Behavioral')
            ->assertSee('SUD')
            ->assertSee('SDOH - Housing')
            ->assertSee('SDOH - Food')
            ->assertSee('SDOH - Transportation')
            ->assertSee('SDOH - Other')
            ->assertSee('All Categories');
    }

    public function test_save_problem_creates_and_refreshes(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', \App\Enums\ProblemType::Physical->value)
            ->set('problemName', 'New Problem Via Save')
            ->call('saveProblem')
            ->assertSee('New Problem Via Save');
    }
}
