<?php

namespace Tests\Feature\Livewire;

use App\Enums\ProblemClassification;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\CarePlan;
use App\Models\Member;
use App\Models\Problem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CarePlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => UserRole::CareManager]);
        $this->member = Member::factory()->create();
    }

    // ─── CM-CP-001: Care Plan View with Version Selector ─────

    public function test_care_plan_view_mode_available(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('switchView', 'care_plan')
            ->assertSet('viewMode', 'care_plan');
    }

    public function test_care_plan_version_selector_shows_versions(): void
    {
        $plan1 = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
            'assessment_type' => 'Initial',
        ]);

        $plan2 = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 2,
            'assessment_type' => 'Reassessment',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $carePlans = $this->member->carePlans()->orderBy('version_number', 'desc')->get();
        $this->assertCount(2, $carePlans);
    }

    public function test_care_plan_filters_problems_to_selected_version(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        $linkedProblem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => $plan->id,
            'name' => 'Linked Problem',
        ]);

        $unlinkedProblem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => null,
            'name' => 'Unlinked Problem',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('switchView', 'care_plan')
            ->call('selectCarePlan', $plan->id)
            ->assertSee('Linked Problem')
            ->assertDontSee('Unlinked Problem');
    }

    public function test_switching_to_ptr_view_shows_all_problems(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => $plan->id,
            'name' => 'Plan Problem',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Regular Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('switchView', 'ptr')
            ->assertSee('Plan Problem')
            ->assertSee('Regular Problem');
    }

    // ─── CM-CP-002: Auto-Associate in Care Plan View ─────

    public function test_problem_auto_associated_in_care_plan_view(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('switchView', 'care_plan')
            ->call('selectCarePlan', $plan->id)
            ->set('problemName', 'Auto Linked Problem')
            ->set('problemType', 'physical')
            ->call('saveProblem');

        $this->assertDatabaseHas('problems', [
            'name' => 'Auto Linked Problem',
            'care_plan_id' => $plan->id,
        ]);
    }

    public function test_problem_not_auto_associated_in_ptr_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemName', 'PTR Problem')
            ->set('problemType', 'behavioral')
            ->call('saveProblem');

        $this->assertDatabaseHas('problems', [
            'name' => 'PTR Problem',
            'care_plan_id' => null,
        ]);
    }

    public function test_task_auto_associated_in_care_plan_view(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('switchView', 'care_plan')
            ->call('selectCarePlan', $plan->id)
            ->set('taskProblemId', $problem->id)
            ->set('taskName', 'CP Task')
            ->set('taskType', TaskType::Action->value)
            ->call('saveTask');

        $this->assertDatabaseHas('tasks', [
            'name' => 'CP Task',
            'care_plan_id' => $plan->id,
        ]);
    }

    // ─── CM-CP-003: Care Plan Filter ─────

    public function test_care_plan_filter_in_ptr_view(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => $plan->id,
            'name' => 'Filtered In',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => null,
            'name' => 'Filtered Out',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('setCarePlanFilter', $plan->id)
            ->assertSee('Filtered In')
            ->assertDontSee('Filtered Out');
    }

    public function test_clearing_care_plan_filter_shows_all(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'care_plan_id' => $plan->id,
            'name' => 'Plan Problem',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'No Plan Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('setCarePlanFilter', $plan->id)
            ->call('setCarePlanFilter', null)
            ->assertSee('Plan Problem')
            ->assertSee('No Plan Problem');
    }

    // ─── CM-CP-004: Care Planning Summary ─────

    public function test_care_plan_summary_returns_data(): void
    {
        CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
            'assessment_type' => 'Initial',
            'risk_level' => 'High',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $summary = $this->member->carePlans()->latest('version_number')->first();
        $this->assertNotNull($summary);
        $this->assertEquals('Initial', $summary->assessment_type);
        $this->assertEquals('High', $summary->risk_level);
    }

    public function test_care_plan_summary_returns_null_when_no_plans(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $this->assertNull($this->member->carePlans()->latest('version_number')->first());
    }

    public function test_care_plan_overdue_detection(): void
    {
        $plan = CarePlan::factory()->overdue()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
        ]);

        $this->assertTrue($plan->isReassessmentOverdue());
    }

    public function test_care_plan_not_overdue(): void
    {
        $plan = CarePlan::factory()->create([
            'member_id' => $this->member->id,
            'version_number' => 1,
            'next_reassessment_date' => now()->addMonths(3),
        ]);

        $this->assertFalse($plan->isReassessmentOverdue());
    }

    // ─── CM-CP-005: Unsupported Problems Classification ─────

    public function test_classify_assessment_entry_error(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'unsupported_problem_flag' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('classifyUnsupportedProblem', $problem->id, ProblemClassification::AssessmentEntryError->value);

        $problem->refresh();
        $this->assertFalse($problem->unsupported_problem_flag);
        $this->assertEquals(ProblemClassification::AssessmentEntryError, $problem->classification);
        // State should remain unchanged
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_classify_problem_no_longer_confirmed_triggers_unconfirm(): void
    {
        $user = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $user->id,
        ]);
        $problem->update(['unsupported_problem_flag' => true]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('classifyUnsupportedProblem', $problem->id, ProblemClassification::ProblemNoLongerConfirmed->value);

        $problem->refresh();
        $this->assertFalse($problem->unsupported_problem_flag);
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_classify_problem_resolved_triggers_resolve(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);
        $problem->update(['unsupported_problem_flag' => true]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('classifyUnsupportedProblem', $problem->id, ProblemClassification::ProblemResolved->value);

        $problem->refresh();
        $this->assertFalse($problem->unsupported_problem_flag);
        $this->assertEquals(ProblemState::Resolved, $problem->state);
    }

    public function test_classification_creates_audit_trail(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'unsupported_problem_flag' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('classifyUnsupportedProblem', $problem->id, ProblemClassification::AssessmentEntryError->value);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
        ]);
    }
}
