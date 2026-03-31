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
        $this->assertArrayHasKey('released_at', $history->metadata);
        $this->assertFalse($history->metadata['notified']);
    }

    public function test_original_lock_holder_blocked_after_admin_release(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $this->user->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        // Admin releases the lock
        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        $problem->refresh();
        $this->assertNull($problem->locked_by);

        // Original holder tries to confirm — should be blocked with notification
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        // Problem should NOT have been confirmed (first attempt after admin release)
        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);

        // The notified flag should now be true
        $history = StateChangeHistory::where('trackable_type', Problem::class)
            ->where('trackable_id', $problem->id)
            ->where('metadata->event', 'LOCK_ADMIN_RELEASED')
            ->first();
        $this->assertTrue($history->metadata['notified']);
    }

    public function test_original_holder_can_proceed_after_admin_release_notification(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $this->user->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        // Admin releases the lock
        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        // First attempt — blocked with notification
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        // Second attempt — should succeed (notified flag is now true, won't block again)
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_problem_editable_after_admin_release(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $otherUser->id,
            'locked_at' => now(),
            'lock_expires_at' => now()->addMinutes(15),
        ]);

        // Admin releases the lock
        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('adminReleaseLock', $problem->id);

        // A different user (not the original holder) can now edit
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    // ─── CM-CON-002: Lock Expiry Notification on Save ─────

    public function test_expired_lock_blocks_confirm_with_notification(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $this->user->id,
            'locked_at' => now()->subMinutes(20),
            'lock_expires_at' => now()->subMinutes(5), // Expired
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        // Problem should NOT have been confirmed (lock expired blocks action)
        $problem->refresh();
        $this->assertEquals(ProblemState::Added, $problem->state);
        // Lock should have been released
        $this->assertNull($problem->locked_by);
    }

    public function test_expired_lock_blocks_resolve_with_notification(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Confirmed,
            'confirmed_by' => $this->user->id,
            'confirmed_at' => now(),
            'locked_by' => $this->user->id,
            'locked_at' => now()->subMinutes(20),
            'lock_expires_at' => now()->subMinutes(5), // Expired
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('resolveProblem', $problem->id);

        // Problem should NOT have been resolved (lock expired blocks action)
        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
    }

    public function test_another_user_can_edit_after_lock_expires(): void
    {
        $otherUser = User::factory()->create(['role' => UserRole::CareManager]);
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
            'state' => ProblemState::Added,
            'locked_by' => $otherUser->id,
            'locked_at' => now()->subMinutes(20),
            'lock_expires_at' => now()->subMinutes(5), // Expired
        ]);

        // Current user should be able to confirm (lock auto-releases for expired)
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('confirmProblem', $problem->id);

        $problem->refresh();
        $this->assertEquals(ProblemState::Confirmed, $problem->state);
        $this->assertEquals($this->user->id, $problem->confirmed_by);
    }

    // ─── CM-CON-002: Admin Timeout Configuration ─────

    public function test_admin_can_update_lock_timeout(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('lockTimeoutMinutes', '45')
            ->call('updateLockTimeout');

        $this->assertEquals('45', OrganizationSetting::get('lock_timeout_minutes'));
    }

    public function test_non_admin_cannot_update_lock_timeout(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('lockTimeoutMinutes', '45')
            ->call('updateLockTimeout');

        // Should not have been saved (non-admin blocked)
        $this->assertNotEquals('45', OrganizationSetting::get('lock_timeout_minutes'));
    }

    public function test_lock_timeout_validates_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('lockTimeoutMinutes', '0')
            ->call('updateLockTimeout')
            ->assertHasErrors('lockTimeoutMinutes');
    }

    public function test_updated_timeout_applies_to_new_locks(): void
    {
        OrganizationSetting::set('lock_timeout_minutes', '45');

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('acquireLock', $problem->id);

        $problem->refresh();
        $diffMinutes = abs(now()->diffInMinutes($problem->lock_expires_at));
        $this->assertTrue($diffMinutes >= 43 && $diffMinutes <= 47, "Expected ~45 minutes, got {$diffMinutes}");
    }

    public function test_mount_initializes_lock_timeout_from_settings(): void
    {
        OrganizationSetting::set('lock_timeout_minutes', '30');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(CareManagementIndex::class, ['member' => $this->member]);

        $component->assertSet('lockTimeoutMinutes', '30');
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
