<?php

namespace Tests\Feature;

use App\Enums\ProblemType;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsentAccessControlTest extends TestCase
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

    // ─── AC#1: Member Consent = No Consent blocks entire CM module ─────

    public function test_member_consent_no_consent_blocks_cm_module(): void
    {
        $blockedMember = Member::factory()->memberConsentBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Access Restricted')
            ->assertSee('Member Consent is set to No Consent')
            ->assertSee('No Care Management data is available due to consent restrictions');
    }

    public function test_member_consent_no_consent_prevents_save_problem(): void
    {
        $blockedMember = Member::factory()->memberConsentBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Should Not Save')
            ->call('saveProblem');

        $this->assertDatabaseMissing('problems', ['name' => 'Should Not Save']);
    }

    // ─── AC#2: JI Consent = No Consent blocks entire CM module ─────

    public function test_ji_consent_no_consent_blocks_cm_module(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Access Restricted')
            ->assertSee('JI Consent is set to No Consent')
            ->assertSee($blockedMember->name) // CM Main Page still accessible (AC#2)
            ->assertSee('No Care Management data is available due to consent restrictions');
    }

    // ─── AC#3: BH/SUD Consent blocks category sections only ─────

    public function test_bh_consent_blocks_behavioral_category(): void
    {
        $bhBlockedMember = Member::factory()->bhBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $bhBlockedMember])
            ->assertDontSee('Access Restricted') // Not full module block
            ->assertSee('BH Consent is set to No Consent');
    }

    public function test_bh_consent_prevents_adding_behavioral_problem(): void
    {
        $bhBlockedMember = Member::factory()->bhBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $bhBlockedMember])
            ->set('problemType', ProblemType::Behavioral->value)
            ->set('problemName', 'BH Problem Should Not Save')
            ->call('saveProblem');

        $this->assertDatabaseMissing('problems', ['name' => 'BH Problem Should Not Save']);
    }

    public function test_bh_consent_allows_other_categories(): void
    {
        $bhBlockedMember = Member::factory()->bhBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $bhBlockedMember])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Physical Problem Allowed')
            ->call('saveProblem');

        $this->assertDatabaseHas('problems', ['name' => 'Physical Problem Allowed']);
    }

    public function test_sud_consent_blocks_sud_category(): void
    {
        $sudBlockedMember = Member::factory()->sudBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $sudBlockedMember])
            ->assertSee('SUD Consent is set to No Consent');
    }

    public function test_sud_consent_prevents_adding_sud_problem(): void
    {
        $sudBlockedMember = Member::factory()->sudBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $sudBlockedMember])
            ->set('problemType', ProblemType::SUD->value)
            ->set('problemName', 'SUD Problem Should Not Save')
            ->call('saveProblem');

        $this->assertDatabaseMissing('problems', ['name' => 'SUD Problem Should Not Save']);
    }

    public function test_bh_problems_hidden_from_ptr_when_blocked(): void
    {
        $bhBlockedMember = Member::factory()->bhBlocked()->create();

        // Create a BH problem before consent was blocked
        Problem::factory()->create([
            'member_id' => $bhBlockedMember->id,
            'type' => ProblemType::Behavioral,
            'name' => 'Hidden BH Problem',
            'submitted_by' => $this->user->id,
        ]);

        Problem::factory()->create([
            'member_id' => $bhBlockedMember->id,
            'type' => ProblemType::Physical,
            'name' => 'Visible Physical Problem',
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $bhBlockedMember])
            ->assertDontSee('Hidden BH Problem')
            ->assertSee('Visible Physical Problem');
    }

    // ─── AC#4 / CM-ACC-002: Locked state message display ─────

    public function test_blocked_screen_shows_restriction_message_and_back_button(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Access Restricted')
            ->assertSee('All action buttons are disabled')
            ->assertSee('Back to Members')
            ->assertSee($blockedMember->name); // Member header still visible
    }

    public function test_cm_main_page_accessible_when_blocked(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee($blockedMember->name)
            ->assertSee($blockedMember->member_id)
            ->assertSee($blockedMember->organization)
            ->assertSee('Member Main'); // Member Main button is always available
    }

    public function test_no_cm_data_rendered_when_blocked(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        // Create problems that should NOT appear
        Problem::factory()->create([
            'member_id' => $blockedMember->id,
            'name' => 'Secret Problem Data',
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertDontSee('Secret Problem Data')
            ->assertSee('No Care Management data is available due to consent restrictions');
    }

    public function test_action_buttons_disabled_text_when_blocked(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSeeHtml('cursor-not-allowed')
            ->assertSeeHtml('Consent restricted');
    }

    // ─── AC#5: Consent change restores access immediately ─────

    public function test_consent_restored_allows_access_immediately(): void
    {
        $member = Member::factory()->jiBlocked()->create();

        // Initially blocked
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertSee('Access Restricted');

        // Consent granted (simulating change in Member Profile)
        $member->update(['ji_consent_status' => 'consented']);

        // Access restored immediately — no caching
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertDontSee('Access Restricted')
            ->assertSee($member->name);
    }

    public function test_member_consent_restored_allows_access(): void
    {
        $member = Member::factory()->memberConsentBlocked()->create();

        // Initially blocked
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertSee('Access Restricted');

        // Consent granted
        $member->update(['member_consent_status' => 'consented']);

        // Access restored
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertDontSee('Access Restricted');
    }

    public function test_bh_consent_restored_unblocks_behavioral_category(): void
    {
        $member = Member::factory()->bhBlocked()->create();

        // Initially blocked
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertSee('BH Consent is set to No Consent');

        // BH consent granted
        $member->update(['bh_consent_status' => 'consented']);

        // BH category restored
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $member])
            ->assertDontSee('BH Consent is set to No Consent');
    }

    // ─── AC#6: Blocked access attempt audited ─────

    public function test_blocked_access_creates_audit_event(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember]);

        $history = StateChangeHistory::where('trackable_type', Member::class)
            ->where('trackable_id', $blockedMember->id)
            ->where('metadata->event', 'CM_ACCESS_BLOCKED')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('ji_consent', $history->metadata['consent_type']);
        $this->assertEquals($this->user->id, $history->changed_by);
        $this->assertArrayHasKey('timestamp', $history->metadata);
    }

    public function test_member_consent_blocked_creates_audit_event(): void
    {
        $blockedMember = Member::factory()->memberConsentBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember]);

        $history = StateChangeHistory::where('trackable_type', Member::class)
            ->where('trackable_id', $blockedMember->id)
            ->where('metadata->event', 'CM_ACCESS_BLOCKED')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('member_consent', $history->metadata['consent_type']);
    }

    public function test_bh_consent_blocked_creates_audit_event(): void
    {
        $bhBlockedMember = Member::factory()->bhBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $bhBlockedMember]);

        $history = StateChangeHistory::where('trackable_type', Member::class)
            ->where('trackable_id', $bhBlockedMember->id)
            ->where('metadata->event', 'CM_ACCESS_BLOCKED')
            ->where('metadata->consent_type', 'bh_consent')
            ->first();

        $this->assertNotNull($history);
    }

    // ─── AC#7: Compliance Officer Override ─────

    public function test_compliance_officer_sees_override_button(): void
    {
        $compliance = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($compliance)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Override — Compliance Access');
    }

    public function test_non_compliance_user_does_not_see_override_button(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertDontSee('Override — Compliance Access');
    }

    public function test_compliance_officer_can_override_consent_block(): void
    {
        $compliance = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($compliance)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->assertSee('Access Restricted')
            ->assertSee('No Care Management data is available')
            ->call('activateConsentOverride')
            ->assertDontSee('Access Restricted')
            ->assertSee('Compliance Override Active');
    }

    public function test_compliance_override_creates_audit_event(): void
    {
        $compliance = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($compliance)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->call('activateConsentOverride');

        $history = StateChangeHistory::where('trackable_type', Member::class)
            ->where('trackable_id', $blockedMember->id)
            ->where('metadata->event', 'CM_ACCESS_OVERRIDE')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('ji_consent', $history->metadata['consent_type']);
        $this->assertEquals($compliance->id, $history->metadata['override_by']);
    }

    public function test_non_compliance_cannot_activate_override(): void
    {
        $blockedMember = Member::factory()->jiBlocked()->create();

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $blockedMember])
            ->call('activateConsentOverride');

        // Should still be blocked
        $this->assertDatabaseMissing('state_change_histories', [
            'trackable_type' => Member::class,
            'trackable_id' => $blockedMember->id,
            'to_state' => 'access_override',
        ]);
    }
}
