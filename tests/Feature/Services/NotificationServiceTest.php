<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationEventType;
use App\Enums\ProblemState;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\NotificationSetting;
use App\Models\Problem;
use App\Models\User;
use App\Notifications\ProblemAddedNotification;
use App\Services\CareManagement\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Member $member;
    private User $leadCm;
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->leadCm = User::factory()->create();
        $this->member = Member::factory()->create([
            'lead_care_manager' => $this->leadCm->id,
        ]);
        $this->service = new NotificationService();
    }

    public function test_notification_sent_when_event_enabled(): void
    {
        Notification::fake();

        NotificationSetting::updateOrCreate(['event_type' => 'problem_added'], ['enabled' => true]);

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        $this->service->notifyLeadCareManager(
            $this->member,
            NotificationEventType::ProblemAdded,
            new ProblemAddedNotification($problem),
        );

        Notification::assertSentTo($this->leadCm, ProblemAddedNotification::class);
    }

    public function test_notification_not_sent_when_event_disabled(): void
    {
        Notification::fake();

        NotificationSetting::updateOrCreate(['event_type' => 'problem_added'], ['enabled' => false]);

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        $this->service->notifyLeadCareManager(
            $this->member,
            NotificationEventType::ProblemAdded,
            new ProblemAddedNotification($problem),
        );

        Notification::assertNotSentTo($this->leadCm, ProblemAddedNotification::class);
    }

    public function test_notification_defaults_to_enabled_when_no_setting(): void
    {
        Notification::fake();

        // No setting exists — should default to enabled
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        $this->service->notifyLeadCareManager(
            $this->member,
            NotificationEventType::ProblemAdded,
            new ProblemAddedNotification($problem),
        );

        Notification::assertSentTo($this->leadCm, ProblemAddedNotification::class);
    }

    public function test_notification_not_sent_when_no_lead_care_manager(): void
    {
        Notification::fake();

        $memberNoLead = Member::factory()->create(['lead_care_manager' => null]);
        $problem = Problem::factory()->create([
            'member_id' => $memberNoLead->id,
            'submitted_by' => $this->user->id,
        ]);

        $this->service->notifyLeadCareManager(
            $memberNoLead,
            NotificationEventType::ProblemAdded,
            new ProblemAddedNotification($problem),
        );

        Notification::assertNothingSent();
    }

    public function test_notification_setting_model_is_enabled_check(): void
    {
        NotificationSetting::updateOrCreate(['event_type' => 'task_added'], ['enabled' => false]);

        $this->assertFalse(NotificationSetting::isEnabled(NotificationEventType::TaskAdded));
    }

    public function test_notification_setting_model_defaults_true(): void
    {
        $this->assertTrue(NotificationSetting::isEnabled(NotificationEventType::TaskStarted));
    }

    public function test_existing_notification_flow_uses_service(): void
    {
        Notification::fake();

        NotificationSetting::updateOrCreate(['event_type' => 'problem_added'], ['enabled' => false]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('problemName', 'Test Problem')
            ->set('problemType', 'physical')
            ->call('saveProblem');

        // Notification should NOT be sent because we disabled it
        Notification::assertNotSentTo($this->leadCm, ProblemAddedNotification::class);
    }

    public function test_all_event_types_have_labels(): void
    {
        foreach (NotificationEventType::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }
}
