<?php

namespace Tests\Unit\Enums;

use App\Enums\ProblemState;
use PHPUnit\Framework\TestCase;

class ProblemStateTest extends TestCase
{
    public function test_added_can_transition_to_confirmed(): void
    {
        $this->assertTrue(ProblemState::Added->canTransitionTo(ProblemState::Confirmed));
    }

    public function test_added_cannot_transition_to_resolved(): void
    {
        $this->assertFalse(ProblemState::Added->canTransitionTo(ProblemState::Resolved));
    }

    public function test_confirmed_can_transition_to_resolved(): void
    {
        $this->assertTrue(ProblemState::Confirmed->canTransitionTo(ProblemState::Resolved));
    }

    public function test_confirmed_can_transition_to_added_unconfirm(): void
    {
        $this->assertTrue(ProblemState::Confirmed->canTransitionTo(ProblemState::Added));
    }

    public function test_resolved_can_transition_to_confirmed_unresolve(): void
    {
        $this->assertTrue(ProblemState::Resolved->canTransitionTo(ProblemState::Confirmed));
    }

    public function test_resolved_cannot_transition_to_added(): void
    {
        $this->assertFalse(ProblemState::Resolved->canTransitionTo(ProblemState::Added));
    }
}
