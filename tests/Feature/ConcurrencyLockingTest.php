<?php

namespace Tests\Feature;

use App\Enums\ProblemState;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\OrganizationSetting;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConcurrencyLockingTest extends TestCase
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

    // ─── CM-CON-001: Lock Acquisition ─────

    public function test_can_acquire_lock(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('acquireLock', $problem->id);

        $problem->refresh();
        $this->assertEquals($this->user->id, $problem->locked_by);
        $this->assertNotNull($problem->locked_at);
        $this->assertNotNull($problem->lock_expires_at);
        $this->assertNotNull($problem->lock_session_id);
    }

    public function test_locked_by_another_user_blocks_lock(): void
    {
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('acquireLock', $problem->id);

        // Lock should still belong to the other user
        $problem->refresh();
        $this->assertEquals($otherUser->id, $problem->locked_by);
    }

    public function test_can_release_own_lock(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $this->user->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('releaseLock', $problem->id);

        $problem->refresh();
        $this->assertNull($problem->locked_by);
        $this->assertNull($problem->locked_at);
    }

    // ─── CM-CON-002: Lock Timeout ─────

    public function test_expired_lock_is_treated_as_unlocked(): void
    {
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now()->subMinutes(20),
            'lock_expires_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($problem->isLockedByAnother($this->user->id));
    }

    public function test_non_expired_lock_blocks_other_users(): void
    {
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($problem->isLockedByAnother($this->user->id));
    }

    public function test_lock_uses_configured_timeout(): void
    {
        OrganizationSetting::set('lock_timeout_minutes', '30');

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('acquireLock', $problem->id);

        $problem->refresh();
        // Lock should expire roughly 30 minutes from now
        $this->assertNotNull($problem->lock_expires_at);
        $this->assertTrue($problem->lock_expires_at->isFuture());
        $diffMinutes = abs(now()->diffInMinutes($problem->lock_expires_at));
        $this->assertTrue($diffMinutes >= 28 && $diffMinutes <= 32, "Expected ~30 minutes, got {$diffMinutes}");
    }

    public function test_expired_lock_auto_released_on_acquire(): void
    {
        $otherUser = User::factory()->create();
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now()->subMinutes(20),
            'lock_expires_at' => now()->subMinutes(5), // Expired
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('acquireLock', $problem->id);

        $problem->refresh();
        // New user should have the lock now
        $this->assertEquals($this->user->id, $problem->locked_by);
    }

    // ─── CM-CON-003: Admin Override ─────

    public function test_admin_can_release_any_lock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        $problem->refresh();
        $this->assertNull($problem->locked_by);
    }

    public function test_non_admin_cannot_release_others_lock(): void
    {
        $otherUser = User::factory()->create();

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        // Lock should still belong to the other user
        $problem->refresh();
        $this->assertEquals($otherUser->id, $problem->locked_by);
    }

    public function test_admin_lock_release_creates_audit_event(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        $history = StateChangeHistory::where('trackable_type', Problem::class)
            ->where('trackable_id', $problem->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('LOCK_ADMIN_RELEASED', $history->metadata['event']);
        $this->assertEquals($otherUser->id, $history->metadata['original_lock_holder']);
    }

    // ─── Organization Settings ─────

    public function test_organization_setting_get(): void
    {
        OrganizationSetting::create(['key' => 'test_key', 'value' => '42']);

        $this->assertEquals('42', OrganizationSetting::get('test_key'));
    }

    public function test_organization_setting_get_default(): void
    {
        $this->assertEquals('default', OrganizationSetting::get('nonexistent', 'default'));
    }

    public function test_organization_setting_set(): void
    {
        OrganizationSetting::set('my_key', 'my_value');

        $this->assertEquals('my_value', OrganizationSetting::get('my_key'));
    }
}
