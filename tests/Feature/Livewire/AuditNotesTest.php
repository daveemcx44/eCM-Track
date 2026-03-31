<?php

namespace Tests\Feature\Livewire;

use App\Enums\ProblemState;
use App\Enums\UserRole;
use App\Livewire\CareManagement\CareManagementIndex;
use App\Models\Member;
use App\Models\Note;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\Task;
use App\Models\User;
use App\Notifications\NoteAddedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AuditNotesTest extends TestCase
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

    // ─── CM-AUD-001: Add Note ─────

    public function test_can_add_note_to_problem(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'problem', $problem->id)
            ->set('noteContent', 'This is a test note')
            ->call('saveNote');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
            'content' => 'This is a test note',
            'created_by' => $this->user->id,
            'notify' => false,
        ]);
    }

    public function test_can_add_note_to_task(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'problem_id' => $problem->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'task', $task->id)
            ->set('noteContent', 'Task note')
            ->call('saveNote');

        $this->assertDatabaseHas('notes', [
            'notable_type' => Task::class,
            'notable_id' => $task->id,
            'content' => 'Task note',
        ]);
    }

    public function test_note_with_notify_sends_notification(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $this->member->update(['lead_care_manager' => $leadCm->id]);

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'problem', $problem->id)
            ->set('noteContent', 'Notify note')
            ->set('noteNotify', true)
            ->call('saveNote');

        Notification::assertSentTo($leadCm, NoteAddedNotification::class);
    }

    public function test_note_without_notify_does_not_send_notification(): void
    {
        Notification::fake();

        $leadCm = User::factory()->create();
        $this->member->update(['lead_care_manager' => $leadCm->id]);

        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'problem', $problem->id)
            ->set('noteContent', 'No notify note')
            ->set('noteNotify', false)
            ->call('saveNote');

        Notification::assertNotSentTo($leadCm, NoteAddedNotification::class);
    }

    public function test_note_requires_content(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'problem', $problem->id)
            ->set('noteContent', '')
            ->call('saveNote')
            ->assertHasErrors(['noteContent']);
    }

    public function test_notes_are_append_only(): void
    {
        $note = Note::factory()->create();

        $this->expectException(\LogicException::class);
        $note->update(['content' => 'Updated']);
    }

    public function test_notes_cannot_be_deleted(): void
    {
        $note = Note::factory()->create();

        $this->expectException(\LogicException::class);
        $note->delete();
    }

    public function test_note_creates_audit_event(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('openAddNoteModal', 'problem', $problem->id)
            ->set('noteContent', 'Audit note')
            ->call('saveNote');

        $history = StateChangeHistory::where('trackable_type', Problem::class)
            ->where('trackable_id', $problem->id)
            ->latest()
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('NOTE_ADDED', $history->metadata['event']);
    }

    // ─── CM-AUD-002: State Change History ─────

    public function test_can_view_state_history(): void
    {
        $problem = Problem::factory()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        StateChangeHistory::create([
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => null,
            'to_state' => ProblemState::Added->value,
            'changed_by' => $this->user->id,
            'metadata' => ['event' => 'PROBLEM_ADDED'],
        ]);

        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('showStateHistory', 'problem', $problem->id)
            ->assertSet('showHistoryModal', true)
            ->assertSet('historyEntityName', $problem->name);
    }

    public function test_state_history_shows_reversal_notes(): void
    {
        $problem = Problem::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'submitted_by' => $this->user->id,
        ]);

        StateChangeHistory::create([
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => ProblemState::Confirmed->value,
            'to_state' => ProblemState::Added->value,
            'changed_by' => $this->user->id,
            'note' => 'Reversal reason explanation',
            'metadata' => ['event' => 'PROBLEM_UNCONFIRMED'],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->call('showStateHistory', 'problem', $problem->id);

        $records = $component->get('stateHistoryRecords');
        $lastRecord = end($records);
        $this->assertEquals('Reversal reason explanation', $lastRecord['note']);
    }

    public function test_can_close_history_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(CareManagementIndex::class, ['member' => $this->member])
            ->set('showHistoryModal', true)
            ->call('closeHistoryModal')
            ->assertSet('showHistoryModal', false)
            ->assertSet('stateHistoryRecords', []);
    }
}
