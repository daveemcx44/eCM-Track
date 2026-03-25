<?php

namespace Tests\Unit\Models;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Models\Member;
use App\Models\Note;
use App\Models\Problem;
use App\Models\StateChangeHistory;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProblemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_problem_can_be_created_with_factory(): void
    {
        $problem = Problem::factory()->create();

        $this->assertDatabaseHas('problems', [
            'id' => $problem->id,
            'name' => $problem->name,
        ]);
    }

    public function test_type_is_cast_to_problem_type_enum(): void
    {
        $problem = Problem::factory()->create(['type' => ProblemType::Physical]);

        $problem->refresh();

        $this->assertInstanceOf(ProblemType::class, $problem->type);
        $this->assertEquals(ProblemType::Physical, $problem->type);
    }

    public function test_state_is_cast_to_problem_state_enum(): void
    {
        $problem = Problem::factory()->create(['state' => ProblemState::Added]);

        $problem->refresh();

        $this->assertInstanceOf(ProblemState::class, $problem->state);
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_encounter_setting_is_cast_to_enum(): void
    {
        $problem = Problem::factory()->create(['encounter_setting' => EncounterSetting::Telehealth]);

        $problem->refresh();

        $this->assertInstanceOf(EncounterSetting::class, $problem->encounter_setting);
        $this->assertEquals(EncounterSetting::Telehealth, $problem->encounter_setting);
    }

    public function test_problem_belongs_to_member(): void
    {
        $member = Member::factory()->create();
        $problem = Problem::factory()->create(['member_id' => $member->id]);

        $this->assertInstanceOf(Member::class, $problem->member);
        $this->assertEquals($member->id, $problem->member->id);
    }

    public function test_problem_has_many_tasks(): void
    {
        $problem = Problem::factory()->confirmed()->create();
        $task = Task::factory()->create(['problem_id' => $problem->id]);

        $this->assertTrue($problem->tasks->contains($task));
        $this->assertInstanceOf(Task::class, $problem->tasks->first());
    }

    public function test_problem_has_morph_many_notes(): void
    {
        $problem = Problem::factory()->create();
        $note = Note::factory()->create([
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
        ]);

        $this->assertTrue($problem->notes->contains($note));
        $this->assertInstanceOf(Note::class, $problem->notes->first());
    }

    public function test_problem_has_morph_many_state_history(): void
    {
        $problem = Problem::factory()->create();

        StateChangeHistory::create([
            'trackable_type' => Problem::class,
            'trackable_id' => $problem->id,
            'from_state' => ProblemState::Added->value,
            'to_state' => ProblemState::Confirmed->value,
            'changed_by' => $problem->submitted_by,
        ]);

        $this->assertCount(1, $problem->stateHistory);
        $this->assertInstanceOf(StateChangeHistory::class, $problem->stateHistory->first());
    }

    public function test_scope_by_type_filters_problems(): void
    {
        Problem::factory()->create(['type' => ProblemType::Physical]);
        Problem::factory()->create(['type' => ProblemType::Behavioral]);
        Problem::factory()->create(['type' => ProblemType::Physical]);

        $physicalProblems = Problem::byType(ProblemType::Physical)->get();

        $this->assertCount(2, $physicalProblems);
        $physicalProblems->each(fn ($p) => $this->assertEquals(ProblemType::Physical, $p->type));
    }

    public function test_is_confirmed_returns_true_for_confirmed_problem(): void
    {
        $problem = Problem::factory()->confirmed()->create();

        $this->assertTrue($problem->isConfirmed());
    }

    public function test_is_confirmed_returns_false_for_added_problem(): void
    {
        $problem = Problem::factory()->create();

        $this->assertFalse($problem->isConfirmed());
    }

    public function test_is_resolved_returns_true_for_resolved_problem(): void
    {
        $problem = Problem::factory()->resolved()->create();

        $this->assertTrue($problem->isResolved());
    }

    public function test_is_resolved_returns_false_for_confirmed_problem(): void
    {
        $problem = Problem::factory()->confirmed()->create();

        $this->assertFalse($problem->isResolved());
    }
}
