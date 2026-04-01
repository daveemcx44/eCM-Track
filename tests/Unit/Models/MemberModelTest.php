<?php

namespace Tests\Unit\Models;

use App\Models\Member;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_be_created_with_factory(): void
    {
        $member = Member::factory()->create();

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => $member->name,
        ]);
    }

    public function test_member_has_fillable_attributes(): void
    {
        $member = new Member;

        $this->assertEquals(
            ['name', 'dob', 'member_id', 'organization', 'status', 'lead_care_manager', 'ji_consent_status', 'member_consent_status', 'bh_consent_status', 'sud_consent_status', 'tenant_id'],
            $member->getFillable()
        );
    }

    public function test_member_has_problems_relationship(): void
    {
        $member = Member::factory()->create();
        $problem = Problem::factory()->create(['member_id' => $member->id]);

        $this->assertTrue($member->problems->contains($problem));
        $this->assertInstanceOf(Problem::class, $member->problems->first());
    }

    public function test_member_has_lead_care_manager_relationship(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['lead_care_manager' => $user->id]);

        $this->assertInstanceOf(User::class, $member->leadCareManager);
        $this->assertEquals($user->id, $member->leadCareManager->id);
    }

    public function test_scope_active_filters_active_members(): void
    {
        Member::factory()->create(['status' => 'active']);
        Member::factory()->create(['status' => 'inactive']);
        Member::factory()->create(['status' => 'active']);

        $activeMembers = Member::active()->get();

        $this->assertCount(2, $activeMembers);
        $activeMembers->each(fn ($m) => $this->assertEquals('active', $m->status));
    }

    public function test_is_ji_consent_blocked_returns_true_when_no_consent(): void
    {
        $member = Member::factory()->jiBlocked()->create();

        $this->assertTrue($member->isJiConsentBlocked());
    }

    public function test_is_ji_consent_blocked_returns_false_when_consent_given(): void
    {
        $member = Member::factory()->create(['ji_consent_status' => 'consented']);

        $this->assertFalse($member->isJiConsentBlocked());
    }

    public function test_is_ji_consent_blocked_returns_false_when_null(): void
    {
        $member = Member::factory()->create(['ji_consent_status' => null]);

        $this->assertFalse($member->isJiConsentBlocked());
    }

    // ─── Member Consent ─────

    public function test_is_member_consent_blocked(): void
    {
        $member = Member::factory()->memberConsentBlocked()->create();
        $this->assertTrue($member->isMemberConsentBlocked());
        $this->assertTrue($member->isCmModuleBlocked());
    }

    public function test_member_consent_not_blocked_when_null(): void
    {
        $member = Member::factory()->create(['member_consent_status' => null]);
        $this->assertFalse($member->isMemberConsentBlocked());
    }

    // ─── BH/SUD Consent ─────

    public function test_is_bh_consent_blocked(): void
    {
        $member = Member::factory()->bhBlocked()->create();
        $this->assertTrue($member->isBhConsentBlocked());
        $this->assertFalse($member->isCmModuleBlocked());
    }

    public function test_is_sud_consent_blocked(): void
    {
        $member = Member::factory()->sudBlocked()->create();
        $this->assertTrue($member->isSudConsentBlocked());
        $this->assertFalse($member->isCmModuleBlocked());
    }

    // ─── CM Module Block ─────

    public function test_cm_module_blocked_by_member_consent(): void
    {
        $member = Member::factory()->memberConsentBlocked()->create();
        $this->assertTrue($member->isCmModuleBlocked());
        $this->assertStringContainsString('Member Consent', $member->getCmBlockReason());
        $this->assertEquals('member_consent', $member->getCmBlockConsentType());
    }

    public function test_cm_module_blocked_by_ji_consent(): void
    {
        $member = Member::factory()->jiBlocked()->create();
        $this->assertTrue($member->isCmModuleBlocked());
        $this->assertStringContainsString('JI Consent', $member->getCmBlockReason());
        $this->assertEquals('ji_consent', $member->getCmBlockConsentType());
    }

    public function test_cm_module_not_blocked_when_consent_given(): void
    {
        $member = Member::factory()->create([
            'ji_consent_status' => 'consented',
            'member_consent_status' => 'consented',
        ]);
        $this->assertFalse($member->isCmModuleBlocked());
        $this->assertNull($member->getCmBlockReason());
    }
}
