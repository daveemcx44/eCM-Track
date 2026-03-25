<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_can_be_created_with_factory(): void
    {
        $note = Note::factory()->create();

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'content' => $note->content,
        ]);
    }

    public function test_note_has_notable_morph_to_relationship(): void
    {
        $problem = Problem::factory()->create();
        $note = Note::factory()->create([
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
        ]);

        $this->assertInstanceOf(Problem::class, $note->notable);
        $this->assertEquals($problem->id, $note->notable->id);
    }

    public function test_note_has_creator_relationship(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $note->creator);
        $this->assertEquals($user->id, $note->creator->id);
    }

    public function test_note_update_throws_logic_exception(): void
    {
        $note = Note::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Notes are append-only and cannot be updated.');

        $note->update(['content' => 'Updated content']);
    }

    public function test_note_delete_throws_logic_exception(): void
    {
        $note = Note::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Notes are append-only and cannot be deleted.');

        $note->delete();
    }

    public function test_updated_at_is_null_constant(): void
    {
        $this->assertNull(Note::UPDATED_AT);
    }
}
