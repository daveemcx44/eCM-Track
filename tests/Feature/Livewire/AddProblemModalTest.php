<?php

namespace Tests\Feature\Livewire;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Livewire\CareManagement\AddProblemModal;
use App\Models\Member;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddProblemModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->member = Member::factory()->create();
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->assertStatus(200)
            ->assertSet('showModal', false);
    }

    public function test_modal_opens_on_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->dispatch('open-add-problem-modal')
            ->assertSet('showModal', true);
    }

    public function test_can_create_problem_with_all_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Headache')
            ->set('code', 'PHY-001')
            ->set('encounterSetting', EncounterSetting::Clinic->value)
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('problem-created');

        $this->assertDatabaseHas('problems', [
            'member_id' => $this->member->id,
            'name' => 'Headache',
            'type' => ProblemType::Physical->value,
            'code' => 'PHY-001',
            'encounter_setting' => EncounterSetting::Clinic->value,
            'state' => ProblemState::Added->value,
            'submitted_by' => $this->user->id,
        ]);
    }

    public function test_can_create_problem_without_optional_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::SUD->value)
            ->set('problemName', 'Substance issue')
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('problem-created');

        $this->assertDatabaseHas('problems', [
            'member_id' => $this->member->id,
            'name' => 'Substance issue',
            'type' => ProblemType::SUD->value,
            'code' => null,
            'encounter_setting' => null,
        ]);
    }

    public function test_problem_type_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemName', 'Headache')
            ->call('save')
            ->assertHasErrors(['problemType' => 'required']);

        $this->assertDatabaseCount('problems', 0);
    }

    public function test_problem_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::Physical->value)
            ->call('save')
            ->assertHasErrors(['problemName' => 'required']);

        $this->assertDatabaseCount('problems', 0);
    }

    public function test_form_resets_after_save(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Headache')
            ->set('code', 'PHY-001')
            ->set('encounterSetting', EncounterSetting::Clinic->value)
            ->call('save')
            ->assertSet('problemType', '')
            ->assertSet('problemName', '')
            ->assertSet('code', '')
            ->assertSet('encounterSetting', '');
    }

    public function test_problem_state_defaults_to_added(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::Behavioral->value)
            ->set('problemName', 'Anxiety')
            ->call('save');

        $problem = Problem::first();
        $this->assertEquals(ProblemState::Added, $problem->state);
    }

    public function test_submitted_by_is_set_to_current_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddProblemModal::class, ['memberId' => $this->member->id])
            ->set('problemType', ProblemType::Physical->value)
            ->set('problemName', 'Pain')
            ->call('save');

        $problem = Problem::first();
        $this->assertEquals($this->user->id, $problem->submitted_by);
        $this->assertNotNull($problem->submitted_at);
    }
}
