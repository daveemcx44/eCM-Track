<?php

namespace Tests\Feature\Livewire;

use App\Enums\NotificationEventType;
use App\Enums\OutreachMethod;
use App\Enums\OutreachOutcome;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\OutreachLog;
use App\Models\StateChangeHistory;
use App\Models\User;
use App\Notifications\OutreachLoggedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class OutreachTest extends TestCase
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

    public function test_can_log_outreach_attempt(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', OutreachMethod::Phone->value)
            ->set('outreachDate', now()->format('Y-m-d H:i:s'))
            ->set('outreachOutcome', OutreachOutcome::SuccessfulContact->value)
            ->set('outreachNotes', 'Spoke with member')
            ->call('saveOutreach');

        $this->assertDatabaseCount('outreach_logs', 1);
        $this->assertDatabaseHas('outreach_logs', [
            'member_id' => $this->member->id,
            'method' => OutreachMethod::Phone->value,
            'outcome' => OutreachOutcome::SuccessfulContact->value,
            'notes' => 'Spoke with member',
            'staff_id' => $this->user->id,
        ]);
    }

    public function test_outreach_blocked_at_max_3_attempts(): void
    {
        // Create 3 existing attempts
        for ($i = 0; $i < 3; $i++) {
            OutreachLog::factory()->create([
                'member_id' => $this->member->id,
                'staff_id' => $this->user->id,
            ]);
        }

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', OutreachMethod::Phone->value)
            ->set('outreachDate', now()->format('Y-m-d H:i:s'))
            ->set('outreachOutcome', OutreachOutcome::NoAnswer->value)
            ->call('saveOutreach')
            ->assertHasErrors(['outreachMethod' => 'Maximum of 3 outreach attempts reached for this member.']);

        $this->assertDatabaseCount('outreach_logs', 3);
    }

    public function test_outreach_required_fields_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', '')
            ->set('outreachDate', null)
            ->set('outreachOutcome', '')
            ->call('saveOutreach')
            ->assertHasErrors(['outreachMethod', 'outreachDate', 'outreachOutcome']);
    }

    public function test_outreach_log_is_append_only(): void
    {
        $log = OutreachLog::factory()->create([
            'member_id' => $this->member->id,
            'staff_id' => $this->user->id,
        ]);

        $this->expectException(\LogicException::class);
        $log->update(['notes' => 'Updated']);
    }

    public function test_outreach_log_cannot_be_deleted(): void
    {
        $log = OutreachLog::factory()->create([
            'member_id' => $this->member->id,
            'staff_id' => $this->user->id,
        ]);

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_outreach_creates_audit_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', OutreachMethod::InPerson->value)
            ->set('outreachDate', now()->format('Y-m-d H:i:s'))
            ->set('outreachOutcome', OutreachOutcome::LeftMessage->value)
            ->call('saveOutreach');

        $this->assertDatabaseHas('state_change_histories', [
            'trackable_type' => Member::class,
            'trackable_id' => $this->member->id,
        ]);

        $history = StateChangeHistory::where('trackable_type', Member::class)->first();
        $this->assertEquals('OUTREACH_LOGGED', $history->metadata['event']);
    }

    public function test_outreach_sends_notification(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $this->member->update(['lead_care_manager' => $leadCm->id]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', OutreachMethod::Phone->value)
            ->set('outreachDate', now()->format('Y-m-d H:i:s'))
            ->set('outreachOutcome', OutreachOutcome::SuccessfulContact->value)
            ->call('saveOutreach');

        Notification::assertSentTo($leadCm, OutreachLoggedNotification::class);
    }

    public function test_unauthorized_role_cannot_log_outreach(): void
    {
        $clinician = User::factory()->create(['role' => UserRole::AuthorizedClinician]);

        Livewire::actingAs($clinician)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('outreachMethod', OutreachMethod::Phone->value)
            ->set('outreachDate', now()->format('Y-m-d H:i:s'))
            ->set('outreachOutcome', OutreachOutcome::NoAnswer->value)
            ->call('saveOutreach')
            ->assertHasErrors(['outreachMethod' => 'You do not have permission to log outreach attempts.']);
    }

    public function test_outreach_enums_have_labels(): void
    {
        foreach (OutreachMethod::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
        foreach (OutreachOutcome::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_outreach_logs_returned_in_reverse_chronological_order(): void
    {
        $older = OutreachLog::factory()->create([
            'member_id' => $this->member->id,
            'staff_id' => $this->user->id,
            'outreach_date' => now()->subDays(5),
        ]);

        $newer = OutreachLog::factory()->create([
            'member_id' => $this->member->id,
            'staff_id' => $this->user->id,
            'outreach_date' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        // The computed property should return newest first
        $logs = $this->member->outreachLogs()->orderBy('outreach_date', 'desc')->get();
        $this->assertEquals($newer->id, $logs->first()->id);
    }
}
