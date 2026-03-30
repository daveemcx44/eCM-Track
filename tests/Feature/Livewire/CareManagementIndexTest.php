<?php

namespace Tests\Feature\Livewire;

use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Notifications\TaskAddedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskUncompletedNotification;
use App\Notifications\TaskStartedNotification;
use App\Models\Note;
use App\Notifications\ProblemAddedNotification;
use App\Notifications\ProblemConfirmedNotification;
use App\Notifications\ProblemResolvedNotification;
use App\Notifications\ProblemUnconfirmedNotification;
use App\Notifications\ProblemUnresolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    // ─── CM-PROB-001: Add a Problem ─────────────────────────────

    public function test_ji_consent_blocked_shows_restriction_message(): void
    {
        $blockedMember = Member::factory()->create([
            'ji_consent_status' => 'no_consent',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Access Restricted')
            ->assertDontSee('Add Problem');
    }

    public function test_ji_consent_blocked_prevents_save_problem(): void
    {
        $blockedMember = Member::factory()->create([
            'ji_consent_status' => 'no_consent',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Should Not Save')
            ->call('saveProblem');

        $this->assertDatabaseMissing('problems', ['name' => 'Should Not Save']);
    }

    public function test_save_problem_creates_audit_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Audit Test Problem')
            ->call('saveProblem');

        $problem = Problem::where('name', 'Audit Test Problem')->first();
        $this->assertNotNull($problem);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => null,
            'to_state' => 'added',
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_save_problem_requires_problem_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', '')
            ->set('problemName', 'Missing Type')
            ->call('saveProblem')
            ->assertHasErrors(['problemType' => 'required']);

        $this->assertDatabaseMissing('problems', ['name' => 'Missing Type']);
    }

    public function test_save_problem_requires_problem_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', '')
            ->call('saveProblem')
            ->assertHasErrors(['problemName' => 'required']);
    }

    public function test_problem_is_immutable_no_delete(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        // Component has no deleteProblem method
        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $this->assertFalse(method_exists($component->instance(), 'deleteProblem'));
    }

    public function test_save_problem_sets_submitted_by_and_at(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', ProblemType::Behavioral->value)
            ->set('problemName', 'Submitted By Test')
            ->call('saveProblem');

        $problem = Problem::where('name', 'Submitted By Test')->first();
        $this->assertNotNull($problem);
        $this->assertEquals($this->user->id, $problem->submitted_by);
        $this->assertNotNull($problem->submitted_at);
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_save_problem_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create([
            'lead_care_manager' => $leadCm->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Notify Test Problem')
            ->call('saveProblem');

        Notification::assertSentTo($leadCm, ProblemAddedNotification::class);
    }

    public function test_save_problem_no_notification_without_lead_cm(): void
    {
        Notification::fake();

        $memberNoLead = Member::factory()->create([
            'lead_care_manager' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $memberNoLead])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'No Notify Problem')
            ->call('saveProblem');

        Notification::assertNothingSent();
    }

    public function test_save_problem_with_optional_fields_empty(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemType', ProblemType::SDOHHousing->value)
            ->set('problemName', 'Optional Fields Test')
            ->set('problemCode', '')
            ->set('problemEncounterSetting', '')
            ->call('saveProblem');

        $problem = Problem::where('name', 'Optional Fields Test')->first();
        $this->assertNotNull($problem);
        $this->assertNull($problem->code);
        $this->assertNull($problem->encounter_setting);
    }

    public function test_add_problem_button_visible_for_normal_consent(): void
    {
        $normalMember = Member::factory()->create([
            'ji_consent_status' => 'consent',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $normalMember])
            ->assertSee('Add Problem');
    }

    // ─── CM-PROB-002: Confirm a Problem ─────────────────────────

    public function test_authorized_user_can_confirm_added_problem(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals($careManager->id, $problem->confirmed_by);
        $this->assertNotNull($problem->confirmed_at);
    }

    public function test_chw_cannot_confirm_problem(): void
    {
        $chw = User::factory()->create(['role' => UserRole::CommunityHealthWorker]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($chw)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_double_confirm_rejected(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        // Should not change state — already confirmed, policy returns false
        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_confirm_blocked_when_locked_by_another_user(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_confirm_creates_audit_event(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => 'added',
            'to_state' => 'confirmed',
            'changed_by' => $careManager->id,
        ]);
    }

    public function test_confirm_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create([
            'lead_care_manager' => $leadCm->id,
        ]);
        $problem = Problem::factory()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->call('confirmProblem', $problem->id);

        Notification::assertSentTo($leadCm, ProblemConfirmedNotification::class);
    }

    public function test_supervisor_can_confirm_problem(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_authorized_clinician_can_confirm_problem(): void
    {
        $clinician = User::factory()->create(['role' => UserRole::AuthorizedClinician]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($clinician)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    // ─── CM-PROB-003: Unconfirm a Problem ────────────────────────

    public function test_supervisor_can_unconfirm_confirmed_problem(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Confirmed in error — wrong member')
            ->call('unconfirmProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
        $this->assertNull($problem->confirmed_by);
        $this->assertNull($problem->confirmed_at);
    }

    public function test_authorized_clinician_can_unconfirm_problem(): void
    {
        $clinician = User::factory()->create(['role' => UserRole::AuthorizedClinician]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($clinician)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Need more information')
            ->call('unconfirmProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_care_manager_cannot_unconfirm_problem(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Should not work')
            ->call('unconfirmProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_chw_cannot_unconfirm_problem(): void
    {
        $chw = User::factory()->create(['role' => UserRole::CommunityHealthWorker]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($chw)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Should not work')
            ->call('unconfirmProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_unconfirm_requires_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', '')
            ->call('unconfirmProblem')
            ->assertHasErrors(['unconfirmNote']);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_unconfirm_cascades_to_incomplete_tasks(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $addedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
        ]);

        $startedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        // Already completed task should remain unchanged
        $completedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::Completed,
            'completed_by' => $this->user->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Unconfirming due to error')
            ->call('unconfirmProblem');

        $addedTask->refresh();
        $startedTask->refresh();
        $completedTask->refresh();

        // Incomplete tasks should be auto-completed
        $this->assertEquals(TaskState::Completed, $addedTask->state);
        $this->assertEquals(TaskCompletionType::ProblemUnconfirmed, $addedTask->completion_type);

        $this->assertEquals(TaskState::Completed, $startedTask->state);
        $this->assertEquals(TaskCompletionType::ProblemUnconfirmed, $startedTask->completion_type);

        // Already-completed task should be unchanged
        $this->assertEquals(TaskCompletionType::Completed, $completedTask->completion_type);
    }

    public function test_unconfirm_creates_audit_events(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Audit test note')
            ->call('unconfirmProblem');

        // PROBLEM_UNCONFIRMED audit event
        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => 'confirmed',
            'to_state' => 'added',
            'changed_by' => $supervisor->id,
        ]);

        // TASK_AUTO_COMPLETED audit event
        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'started',
            'to_state' => 'completed',
            'changed_by' => $supervisor->id,
        ]);
    }

    public function test_unconfirm_creates_mandatory_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'This was confirmed incorrectly')
            ->call('unconfirmProblem');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => 'This was confirmed incorrectly',
            'created_by' => $supervisor->id,
        ]);
    }

    public function test_unconfirm_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create([
            'lead_care_manager' => $leadCm->id,
        ]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Notification test')
            ->call('unconfirmProblem');

        Notification::assertSentTo($leadCm, ProblemUnconfirmedNotification::class, function ($notification) {
            return $notification->note === 'Notification test';
        });
    }

    public function test_unconfirm_blocked_when_locked_by_another(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Should be blocked')
            ->call('unconfirmProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_reconfirm_shows_reactivation_dialog_for_cascaded_tasks(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        // Create a task that was auto-completed via unconfirm
        Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemUnconfirmed,
            'completed_by' => $supervisor->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id)
            ->assertDispatched('show-reactivation-dialog');

        // Problem should NOT be confirmed yet (waiting for dialog response)
        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_reconfirm_with_reactivation_restores_tasks(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        $cascadedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemUnconfirmed,
            'completed_by' => $supervisor->id,
            'completed_at' => now(),
            'started_by' => $this->user->id,
            'started_at' => now()->subHour(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id, true);

        $problem->refresh();
        $cascadedTask->refresh();

        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals(TaskState::Started, $cascadedTask->state);
        $this->assertNull($cascadedTask->completion_type);
        $this->assertNull($cascadedTask->completed_by);
    }

    public function test_reconfirm_without_reactivation_leaves_tasks_completed(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        $cascadedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemUnconfirmed,
            'completed_by' => $supervisor->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id, false);

        $problem->refresh();
        $cascadedTask->refresh();

        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals(TaskState::Completed, $cascadedTask->state);
        $this->assertEquals(TaskCompletionType::ProblemUnconfirmed, $cascadedTask->completion_type);
    }

    public function test_cascaded_tasks_show_unconfirmed_status(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unconfirmProblemId', $problem->id)
            ->set('unconfirmNote', 'Test cascade display')
            ->call('unconfirmProblem')
            ->assertSee('Complete – Problem Unconfirmed');
    }

    // ─── CM-PROB-004: Resolve a Problem ──────────────────────────

    public function test_authorized_user_can_resolve_confirmed_problem(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Resolved, $problem->state);
        $this->assertEquals($careManager->id, $problem->resolved_by);
        $this->assertNotNull($problem->resolved_at);
    }

    public function test_chw_cannot_resolve_problem(): void
    {
        $chw = User::factory()->create(['role' => UserRole::CommunityHealthWorker]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($chw)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_resolve_cascades_incomplete_tasks(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $startedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        $completedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::Completed,
            'completed_by' => $this->user->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        $startedTask->refresh();
        $completedTask->refresh();

        $this->assertEquals(TaskState::Completed, $startedTask->state);
        $this->assertEquals(TaskCompletionType::ProblemResolved, $startedTask->completion_type);

        // Already-completed task unchanged
        $this->assertEquals(TaskCompletionType::Completed, $completedTask->completion_type);
    }

    public function test_resource_can_be_added_after_problem_resolved(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemResolved,
            'completed_by' => $careManager->id,
            'completed_at' => now(),
            'started_by' => $this->user->id,
            'started_at' => now()->subHour(),
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('resourceTaskId', $task->id)
            ->set('surveyName', 'Post-Resolve Resource')
            ->set('atHome', 'same')
            ->set('atWork', 'better')
            ->set('atPlay', 'worse')
            ->call('saveResource');

        $this->assertDatabaseHas('resources', [
            'task_id' => $task->id,
            'survey_name' => 'Post-Resolve Resource',
        ]);
    }

    public function test_resolve_creates_audit_events(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => 'confirmed',
            'to_state' => 'resolved',
        ]);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'started',
            'to_state' => 'completed',
        ]);
    }

    public function test_resolve_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->call('resolveProblem', $problem->id);

        Notification::assertSentTo($leadCm, ProblemResolvedNotification::class);
    }

    public function test_resolve_blocked_when_locked(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    // ─── Unresolve ───

    public function test_supervisor_can_unresolve_with_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', 'Problem was not actually resolved')
            ->call('unresolveProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertNull($problem->resolved_by);
        $this->assertNull($problem->resolved_at);
    }

    public function test_unresolve_requires_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', '')
            ->call('unresolveProblem')
            ->assertHasErrors(['unresolveNote']);

        $problem->refresh();
        $this->assertEquals(ProblemState::Resolved, $problem->state);
    }

    public function test_care_manager_cannot_unresolve(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', 'Should not work')
            ->call('unresolveProblem');

        $problem->refresh();
        $this->assertEquals(ProblemState::Resolved, $problem->state);
    }

    public function test_unresolve_creates_note_and_audit(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', 'Unresolve audit test')
            ->call('unresolveProblem');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => 'Unresolve audit test',
            'created_by' => $supervisor->id,
        ]);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => 'resolved',
            'to_state' => 'confirmed',
        ]);
    }

    public function test_unresolve_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', 'Notification test')
            ->call('unresolveProblem');

        Notification::assertSentTo($leadCm, ProblemUnresolvedNotification::class, function ($notification) {
            return $notification->note === 'Notification test';
        });
    }

    public function test_unresolve_offers_reactivation_for_cascaded_tasks(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemResolved,
            'completed_by' => $supervisor->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('unresolveProblemId', $problem->id)
            ->set('unresolveNote', 'Reactivation test')
            ->call('unresolveProblem')
            ->assertDispatched('show-resolve-reactivation-dialog');
    }

    public function test_reactivate_resolved_tasks_restores_state(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $cascadedTask = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Completed,
            'completion_type' => TaskCompletionType::ProblemResolved,
            'completed_by' => $supervisor->id,
            'completed_at' => now(),
            'started_by' => $this->user->id,
            'started_at' => now()->subHour(),
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('reactivateResolvedTasks', $problem->id);

        $cascadedTask->refresh();
        $this->assertEquals(TaskState::Started, $cascadedTask->state);
        $this->assertNull($cascadedTask->completion_type);
        $this->assertNull($cascadedTask->completed_by);
    }

    // ─── CM-PROB-005: Search and Filter ─────────────────────────

    public function test_search_filters_by_problem_name(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Headache Issue',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Ankle Pain',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('search', 'Head')
            ->assertSee('Headache Issue')
            ->assertDontSee('Ankle Pain');
    }

    public function test_search_filters_by_problem_code(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Problem A',
            'code' => 'ICD-Z99',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Problem B',
            'code' => 'ICD-J45',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('search', 'Z99')
            ->assertSee('Problem A')
            ->assertDontSee('Problem B');
    }

    public function test_status_filter_shows_only_matching_state(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Added Problem',
            'state' => ProblemState::Added,
        ]);

        Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
            'name' => 'Confirmed Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('statusFilter', 'added')
            ->assertSee('Added Problem')
            ->assertDontSee('Confirmed Problem');
    }

    public function test_search_and_type_filter_combine(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Physical Headache',
            'type' => ProblemType::Physical,
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Behavioral Headache',
            'type' => ProblemType::Behavioral,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('search', 'Headache')
            ->call('setFilter', ProblemType::Physical->value)
            ->assertSee('Physical Headache')
            ->assertDontSee('Behavioral Headache');
    }

    public function test_no_results_shows_empty_state_with_clear_option(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Existing Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('search', 'NonExistentXYZ')
            ->assertSee('No problems found matching your search criteria')
            ->assertSee('Clear filters');
    }

    public function test_clear_all_filters_restores_full_list(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Problem Alpha',
        ]);

        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Problem Beta',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        // Filter to hide one
        $component->set('search', 'Alpha')
            ->assertSee('Problem Alpha')
            ->assertDontSee('Problem Beta');

        // Clear all → both visible
        $component->call('clearAllFilters')
            ->assertSee('Problem Alpha')
            ->assertSee('Problem Beta');
    }

    public function test_search_is_case_insensitive(): void
    {
        Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'name' => 'Headache Problem',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('search', 'headache')
            ->assertSee('Headache Problem');
    }

    public function test_add_task_disabled_after_resolve(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->resolved()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        // The + button should not wire:click for resolved problems
        // (Only enabled when state === Confirmed)
        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee($problem->name);

        // Attempting to save a task for the resolved problem should fail validation
        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'referrals')
            ->set('taskName', 'Should not save')
            ->call('saveTask');

        $this->assertDatabaseMissing('tasks', ['name' => 'Should not save']);
    }

    // ─── CM-TASK-001: Add a Task to a Confirmed Problem ─────────

    public function test_task_created_under_confirmed_problem(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Referrals->value)
            ->set('taskName', 'Refer to specialist')
            ->set('taskCode', 'REF-001')
            ->set('taskEncounterSetting', 'clinic')
            ->set('taskProvider', 'Dr. Smith')
            ->set('taskDate', '2026-03-27')
            ->set('taskDueDate', '2026-04-15')
            ->call('saveTask');

        $task = Task::where('name', 'Refer to specialist')->first();
        $this->assertNotNull($task);
        $this->assertEquals(TaskState::Added, $task->state);
        $this->assertEquals($problem->id, $task->problem_id);
        $this->assertEquals('REF-001', $task->code);
        $this->assertEquals('Dr. Smith', $task->provider);
        $this->assertEquals($careManager->id, $task->submitted_by);
        $this->assertNotNull($task->submitted_at);
    }

    public function test_task_blocked_when_problem_not_confirmed(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Procedure->value)
            ->set('taskName', 'Should not work')
            ->call('saveTask')
            ->assertHasErrors(['taskProblemId']);

        $this->assertDatabaseMissing('tasks', ['name' => 'Should not work']);
    }

    public function test_task_requires_name_and_type(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', '')
            ->set('taskName', '')
            ->call('saveTask')
            ->assertHasErrors(['taskType', 'taskName']);
    }

    public function test_goal_task_skips_approval(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Goal->value)
            ->set('taskName', 'Reduce blood pressure')
            ->call('saveTask');

        $task = Task::where('name', 'Reduce blood pressure')->first();
        $this->assertNotNull($task);
        $this->assertEquals(TaskType::Goal, $task->type);
        // Goal type does not require approval — Start should be available directly
        $this->assertFalse($task->type->requiresApproval());
    }

    public function test_non_goal_task_requires_approval(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Referrals->value)
            ->set('taskName', 'Referral to lab')
            ->call('saveTask');

        $task = Task::where('name', 'Referral to lab')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->type->requiresApproval());
    }

    public function test_task_added_creates_audit_event(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Medication->value)
            ->set('taskName', 'Audit task test')
            ->call('saveTask');

        $task = Task::where('name', 'Audit task test')->first();

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => null,
            'to_state' => 'added',
            'changed_by' => $careManager->id,
        ]);
    }

    public function test_task_added_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::FollowUp->value)
            ->set('taskName', 'Follow up call')
            ->call('saveTask');

        Notification::assertSentTo($leadCm, TaskAddedNotification::class);
    }

    public function test_task_is_immutable_no_delete(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $this->assertFalse(method_exists($component->instance(), 'deleteTask'));
    }

    public function test_approve_task_transitions_to_approved(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Approved, $task->state);
        $this->assertEquals($careManager->id, $task->approved_by);
        $this->assertNotNull($task->approved_at);
    }

    public function test_all_11_task_types_available_in_dropdown(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        foreach (TaskType::cases() as $type) {
            $component->assertSee($type->label());
        }
    }

    // ─── CM-TASK-002: Approve a Task ───────────────────────────

    public function test_supervisor_can_approve_task(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Approved, $task->state);
        $this->assertEquals($supervisor->id, $task->approved_by);
        $this->assertNotNull($task->approved_at);
    }

    public function test_authorized_clinician_can_approve_task(): void
    {
        $clinician = User::factory()->create(['role' => UserRole::AuthorizedClinician]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Procedure,
        ]);

        Livewire::actingAs($clinician)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Approved, $task->state);
    }

    public function test_care_manager_cannot_approve_task(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Added, $task->state);
    }

    public function test_chw_cannot_approve_task(): void
    {
        $chw = User::factory()->create(['role' => UserRole::CommunityHealthWorker]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Medication,
        ]);

        Livewire::actingAs($chw)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Added, $task->state);
    }

    public function test_goal_task_never_requires_approval(): void
    {
        $this->assertFalse(TaskType::Goal->requiresApproval());

        // Goal tasks should show Start directly, not Approve
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        // Policy should deny approve on Goal tasks
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $this->assertFalse($supervisor->can('approve', $task));
    }

    public function test_non_goal_tasks_require_approval(): void
    {
        $nonGoalTypes = [
            TaskType::Referrals, TaskType::CommunitySupportsReferral,
            TaskType::Procedure, TaskType::DiagnosticStudy,
            TaskType::Medication, TaskType::FollowUp,
            TaskType::Evaluation, TaskType::Admission,
            TaskType::Discharge, TaskType::Action,
        ];

        foreach ($nonGoalTypes as $type) {
            $this->assertTrue($type->requiresApproval(), "{$type->label()} should require approval");
        }
    }

    public function test_start_available_after_approval(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
        ]);

        // Approve first
        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Approved, $task->state);

        // Now start should work
        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
    }

    public function test_approve_creates_audit_event(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('approveTask', $task->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'added',
            'to_state' => 'approved',
            'changed_by' => $supervisor->id,
        ]);
    }

    public function test_awaiting_approval_shown_for_unauthorized_user(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals,
            'name' => 'Pending Approval Task',
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Awaiting Approval');
    }

    // ─── CM-TASK-003: Start a Task ─────────────────────────────

    public function test_start_added_goal_task(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertEquals($this->user->id, $task->started_by);
        $this->assertNotNull($task->started_at);
    }

    public function test_start_approved_task(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Approved,
            'type' => TaskType::Referrals,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertEquals($this->user->id, $task->started_by);
    }

    public function test_start_blocked_when_approval_pending(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Referrals, // requires approval
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Added, $task->state); // unchanged
    }

    public function test_start_is_immutable_no_unstart(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $this->assertFalse(method_exists($component->instance(), 'unstartTask'));
    }

    public function test_concurrent_starts_under_same_problem(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task1 = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
            'name' => 'Task One',
        ]);

        $task2 = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
            'name' => 'Task Two',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $component->call('startTask', $task1->id);
        $component->call('startTask', $task2->id);

        $task1->refresh();
        $task2->refresh();
        $this->assertEquals(TaskState::Started, $task1->state);
        $this->assertEquals(TaskState::Started, $task2->state);
    }

    public function test_start_creates_audit_event(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'added',
            'to_state' => 'started',
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_start_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->call('startTask', $task->id);

        Notification::assertSentTo($leadCm, TaskStartedNotification::class);
    }

    public function test_add_resource_available_after_start(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Added,
            'type' => TaskType::Goal,
        ]);

        // Start the task
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);

        // Now add resource should work
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('resourceTaskId', $task->id)
            ->set('surveyName', 'Post-Start Resource')
            ->set('atHome', 'same')
            ->set('atWork', 'better')
            ->set('atPlay', 'worse')
            ->call('saveResource');

        $this->assertDatabaseHas('resources', [
            'task_id' => $task->id,
            'survey_name' => 'Post-Start Resource',
        ]);
    }

    // ─── CM-TASK-004: Complete a Task ───────────────────────────

    public function test_complete_task_with_completed_reason(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', 'completed')
            ->call('completeTask');

        $task->refresh();
        $this->assertEquals(TaskState::Completed, $task->state);
        $this->assertEquals(TaskCompletionType::Completed, $task->completion_type);
        $this->assertEquals($this->user->id, $task->completed_by);
        $this->assertNotNull($task->completed_at);
    }

    public function test_complete_task_with_cancelled_reason(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', 'cancelled')
            ->call('completeTask');

        $task->refresh();
        $this->assertEquals(TaskCompletionType::Cancelled, $task->completion_type);
    }

    public function test_complete_task_with_terminated_reason(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', 'terminated')
            ->call('completeTask');

        $task->refresh();
        $this->assertEquals(TaskCompletionType::Terminated, $task->completion_type);
    }

    public function test_complete_requires_reason(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', '')
            ->call('completeTask')
            ->assertHasErrors(['completionReason']);

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
    }

    public function test_resource_addable_after_task_completed(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('resourceTaskId', $task->id)
            ->set('surveyName', 'Post-Complete Resource')
            ->set('atHome', 'same')
            ->set('atWork', 'better')
            ->set('atPlay', 'worse')
            ->call('saveResource');

        $this->assertDatabaseHas('resources', [
            'task_id' => $task->id,
            'survey_name' => 'Post-Complete Resource',
        ]);
    }

    public function test_complete_creates_audit_event(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', 'completed')
            ->call('completeTask');

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'started',
            'to_state' => 'completed',
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_complete_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'state' => TaskState::Started,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('completeTaskId', $task->id)
            ->set('completionReason', 'completed')
            ->call('completeTask');

        Notification::assertSentTo($leadCm, TaskCompletedNotification::class);
    }

    public function test_completed_task_shows_reason_badge(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Cancelled,
            'name' => 'Cancelled Task',
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->assertSee('Complete – Task cancelled');
    }

    // ─── CM-TASK-005: Uncomplete a Task ─────────────────────────

    public function test_supervisor_can_uncomplete_task_with_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', 'Task was completed in error')
            ->call('uncompleteTask');

        $task->refresh();
        $this->assertEquals(TaskState::Started, $task->state);
        $this->assertNull($task->completion_type);
        $this->assertNull($task->completed_by);
        $this->assertNull($task->completed_at);
    }

    public function test_care_manager_cannot_uncomplete_task(): void
    {
        $careManager = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($careManager)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', 'Should not work')
            ->call('uncompleteTask');

        $task->refresh();
        $this->assertEquals(TaskState::Completed, $task->state);
    }

    public function test_uncomplete_requires_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', '')
            ->call('uncompleteTask')
            ->assertHasErrors(['uncompleteTaskNote']);

        $task->refresh();
        $this->assertEquals(TaskState::Completed, $task->state);
    }

    public function test_cascade_completed_task_cannot_be_uncompleted(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::ProblemUnconfirmed,
        ]);

        // Policy should deny — cascade-completed tasks use reactivation instead
        $this->assertFalse($supervisor->can('uncomplete', $task));
    }

    public function test_cascade_resolved_task_cannot_be_uncompleted(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::ProblemResolved,
        ]);

        $this->assertFalse($supervisor->can('uncomplete', $task));
    }

    public function test_uncomplete_creates_audit_event(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', 'Audit test note')
            ->call('uncompleteTask');

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Task::class,
            'trackable_id' => $task->id,
            'from_state' => 'completed',
            'to_state' => 'started',
            'changed_by' => $supervisor->id,
        ]);
    }

    public function test_uncomplete_creates_mandatory_note(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', 'Error in completion')
            ->call('uncompleteTask');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Task::class,
            'notable_id' => $task->id,
            'content' => 'Error in completion',
            'created_by' => $supervisor->id,
        ]);
    }

    public function test_uncomplete_notifies_lead_care_manager(): void
    {
        Notification::fake();

        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $leadCm = User::factory()->create();
        $memberWithLead = Member::factory()->create(['lead_care_manager' => $leadCm->id]);
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $memberWithLead->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        $task = Task::factory()->completed()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
            'completion_type' => TaskCompletionType::Completed,
        ]);

        Livewire::actingAs($supervisor)
            ->test(CareManagementIndex::class, ['member' => $memberWithLead])
            ->set('uncompleteTaskId', $task->id)
            ->set('uncompleteTaskNote', 'Notification test')
            ->call('uncompleteTask');

        Notification::assertSentTo($leadCm, TaskUncompletedNotification::class, function ($notification) {
            return $notification->note === 'Notification test';
        });
    }

    public function test_task_with_optional_fields_empty(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'confirmed_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', TaskType::Action->value)
            ->set('taskName', 'Minimal task')
            ->call('saveTask');

        $task = Task::where('name', 'Minimal task')->first();
        $this->assertNotNull($task);
        $this->assertNull($task->code);
        $this->assertNull($task->provider);
        $this->assertNull($task->task_date);
        $this->assertNull($task->due_date);
    }

    // ═══════════════════════════════════════════════════════════════
    // CM-GOAL-001: Add a Goal
    // ═══════════════════════════════════════════════════════════════

    public function test_care_manager_can_create_goal(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Reduce Blood Pressure')
            ->call('saveTask');

        $task = Task::where('name', 'Reduce Blood Pressure')->first();
        $this->assertNotNull($task);
        $this->assertEquals(TaskType::Goal, $task->type);
        $this->assertEquals(TaskState::Added, $task->state);
    }

    public function test_goal_skips_approval_step(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);
        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        // Goal should be startable directly from Added (no approval needed)
        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('startTask', $goal->id);

        $goal->refresh();
        $this->assertEquals(TaskState::Started, $goal->state);
    }

    public function test_non_cm_staff_cannot_create_goal(): void
    {
        $user = User::factory()->create(['role' => UserRole::CommunityHealthWorker]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'My Goal')
            ->call('saveTask')
            ->assertHasErrors('taskType');

        $this->assertNull(Task::where('name', 'My Goal')->first());
    }

    public function test_authorized_clinician_cannot_create_goal(): void
    {
        $user = User::factory()->create(['role' => UserRole::AuthorizedClinician]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Clinician Goal')
            ->call('saveTask')
            ->assertHasErrors('taskType');
    }

    public function test_supervisor_can_create_goal(): void
    {
        $user = User::factory()->create(['role' => UserRole::Supervisor]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Supervisor Goal')
            ->call('saveTask');

        $this->assertNotNull(Task::where('name', 'Supervisor Goal')->first());
    }

    public function test_goal_is_immutable_no_delete(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);
        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        // Goals cannot be deleted — soft delete should not be triggered
        $this->assertNull($goal->deleted_at);
        $this->assertTrue(Task::where('id', $goal->id)->exists());
    }

    public function test_goal_creation_writes_audit_event(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Audit Goal')
            ->call('saveTask');

        $task = Task::where('name', 'Audit Goal')->first();
        $audit = StateChangeHistory::where('trackable_type', Task::class)
            ->where('trackable_id', $task->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('TASK_ADDED', $audit->metadata['event']);
    }

    public function test_goal_appears_in_goal_view(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);
        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('switchView', 'goal')
            ->assertSee($goal->name);
    }

    public function test_goal_task_association(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        // Create a goal
        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);

        // Create a task associated with the goal
        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'referrals')
            ->set('taskName', 'Associated Referral')
            ->set('selectedGoals', [$goal->id])
            ->call('saveTask');

        $task = Task::where('name', 'Associated Referral')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->goals->contains($goal->id));
    }

    public function test_goal_view_is_read_only(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);
        Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);

        // Goal view should show the goal but in read-only mode
        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('switchView', 'goal');

        $component->assertSet('viewMode', 'goal');
    }

    public function test_associate_task_with_goal_method(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);
        $task = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('associateTaskWithGoal', $task->id, $goal->id);

        $this->assertTrue($task->fresh()->goals->contains($goal->id));
        $this->assertTrue($goal->fresh()->associatedTasks->contains($task->id));
    }

    public function test_task_can_be_linked_to_multiple_goals(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal1 = Task::factory()->for($problem)->goal()->create([
            'name' => 'Goal A',
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);
        $goal2 = Task::factory()->for($problem)->goal()->create([
            'name' => 'Goal B',
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);
        $task = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member]);

        $component->call('associateTaskWithGoal', $task->id, $goal1->id);
        $component->call('associateTaskWithGoal', $task->id, $goal2->id);

        $task->refresh();
        $this->assertCount(2, $task->goals);
        $this->assertTrue($task->goals->contains($goal1->id));
        $this->assertTrue($task->goals->contains($goal2->id));
    }

    public function test_goal_association_writes_audit_event(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
        ]);
        $task = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('associateTaskWithGoal', $task->id, $goal->id);

        $audit = StateChangeHistory::where('trackable_type', Task::class)
            ->where('trackable_id', $task->id)
            ->where('metadata->event', 'TASK_GOAL_ASSOCIATED')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($goal->id, $audit->metadata['goal_id']);
    }

    // ═══════════════════════════════════════════════════════════════
    // CM-GOAL-004: Retroactive Goal Association
    // ═══════════════════════════════════════════════════════════════

    public function test_retroactive_dialog_triggers_after_goal_creation_with_existing_tasks(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        // Create an existing task first
        Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        // Create a Goal — should trigger retroactive dialog
        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'New Goal')
            ->call('saveTask');

        // Goal should be created
        $goal = Task::where('name', 'New Goal')->where('type', 'goal')->first();
        $this->assertNotNull($goal);

        // newGoalId should be set for the retroactive dialog
        $component->assertSet('newGoalId', $goal->id);
    }

    public function test_retroactive_association_links_selected_tasks(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $existingTask = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'name' => 'Existing Referral',
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        // Create Goal
        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Retroactive Goal')
            ->call('saveTask');

        $goal = Task::where('name', 'Retroactive Goal')->first();

        // Simulate selecting tasks and confirming
        $component->set('retroactiveTaskIds', [$existingTask->id])
            ->call('saveRetroactiveAssociations');

        $this->assertTrue($existingTask->fresh()->goals->contains($goal->id));
        $component->assertSet('newGoalId', null);
    }

    public function test_skip_retroactive_saves_goal_with_no_associations(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Added,
            'submitted_by' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Skipped Goal')
            ->call('saveTask');

        $goal = Task::where('name', 'Skipped Goal')->first();
        $this->assertNotNull($goal);

        // Skip the dialog
        $component->call('skipRetroactiveAssociations');

        $this->assertCount(0, $goal->fresh()->associatedTasks);
        $component->assertSet('newGoalId', null);
    }

    public function test_no_retroactive_dialog_when_no_existing_tasks(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        // No existing tasks — create Goal directly
        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->set('taskProblemId', $problem->id)
            ->set('taskType', 'goal')
            ->set('taskName', 'Solo Goal')
            ->call('saveTask');

        $goal = Task::where('name', 'Solo Goal')->first();
        $this->assertNotNull($goal);

        // No dialog — newGoalId should remain null
        $component->assertSet('newGoalId', null);
    }

    // ═══════════════════════════════════════════════════════════════
    // CM-GOAL-005: Complete a Goal
    // ═══════════════════════════════════════════════════════════════

    public function test_goal_completes_directly_when_all_tasks_complete(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'started_by' => $user->id,
            'started_at' => now(),
            'submitted_by' => $user->id,
        ]);

        // Associate a completed task
        $task = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Completed,
            'submitted_by' => $user->id,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);
        $task->goals()->attach($goal->id);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('openCompleteGoalModal', $goal->id);

        // Should complete directly without dialog
        $goal->refresh();
        $this->assertEquals(TaskState::Completed, $goal->state);
        $this->assertEquals($user->id, $goal->completed_by);
    }

    public function test_goal_shows_confirmation_when_incomplete_tasks(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'started_by' => $user->id,
            'started_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $incompleteTask = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'name' => 'Incomplete Referral',
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
            'started_by' => $user->id,
            'started_at' => now(),
        ]);
        $incompleteTask->goals()->attach($goal->id);

        $component = Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('openCompleteGoalModal', $goal->id);

        // Should show dialog, not complete yet
        $component->assertSet('completeGoalId', $goal->id);
        $this->assertNotEmpty($component->get('incompleteGoalTasks'));
        $goal->refresh();
        $this->assertEquals(TaskState::Started, $goal->state);
    }

    public function test_goal_completes_despite_incomplete_tasks_on_confirm(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'started_by' => $user->id,
            'started_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $incompleteTask = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
            'started_by' => $user->id,
            'started_at' => now(),
        ]);
        $incompleteTask->goals()->attach($goal->id);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('openCompleteGoalModal', $goal->id)
            ->call('confirmCompleteGoal');

        // Goal completed, incomplete task unchanged
        $goal->refresh();
        $incompleteTask->refresh();
        $this->assertEquals(TaskState::Completed, $goal->state);
        $this->assertEquals(TaskState::Started, $incompleteTask->state);
    }

    public function test_goal_completion_writes_audit_event(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'started_by' => $user->id,
            'started_at' => now(),
            'submitted_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('openCompleteGoalModal', $goal->id);

        $audit = StateChangeHistory::where('trackable_type', Task::class)
            ->where('trackable_id', $goal->id)
            ->where('to_state', TaskState::Completed->value)
            ->first();

        $this->assertNotNull($audit);
    }

    public function test_cancel_complete_goal_does_not_change_state(): void
    {
        $user = User::factory()->create(['role' => UserRole::CareManager]);
        $member = Member::factory()->create();
        $problem = Problem::factory()->for($member)->create(['state' => ProblemState::Confirmed]);

        $goal = Task::factory()->for($problem)->goal()->create([
            'state' => TaskState::Started,
            'started_by' => $user->id,
            'started_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $incompleteTask = Task::factory()->for($problem)->create([
            'type' => TaskType::Referrals,
            'state' => TaskState::Started,
            'submitted_by' => $user->id,
            'started_by' => $user->id,
            'started_at' => now(),
        ]);
        $incompleteTask->goals()->attach($goal->id);

        Livewire::actingAs($user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->call('openCompleteGoalModal', $goal->id)
            ->call('cancelCompleteGoal')
            ->assertSet('completeGoalId', null);

        $goal->refresh();
        $this->assertEquals(TaskState::Started, $goal->state);
    }
}
