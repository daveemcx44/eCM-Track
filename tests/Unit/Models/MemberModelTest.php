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
        $member = new Member();

        $this->assertEquals(
            ['name', 'dob', 'member_id', 'organization', 'status', 'lead_care_manager', 'ji_consent_status'],
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
}
