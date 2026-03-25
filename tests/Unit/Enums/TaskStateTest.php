<?php

namespace Tests\Unit\Enums;

use App\Enums\TaskState;
use PHPUnit\Framework\TestCase;

class TaskStateTest extends TestCase
{
    public function test_added_can_transition_to_approved_not_goal(): void
    {
        $this->assertTrue(TaskState::Added->canTransitionTo(TaskState::Approved, isGoal: false));
    }

    public function test_added_can_transition_to_started_not_goal(): void
    {
        $this->assertTrue(TaskState::Added->canTransitionTo(TaskState::Started, isGoal: false));
    }

    public function test_added_can_transition_to_started_goal(): void
    {
        $this->assertTrue(TaskState::Added->canTransitionTo(TaskState::Started, isGoal: true));
    }

    public function test_added_cannot_transition_to_approved_goal(): void
    {
        $this->assertFalse(TaskState::Added->canTransitionTo(TaskState::Approved, isGoal: true));
    }

    public function test_approved_can_transition_to_started(): void
    {
        $this->assertTrue(TaskState::Approved->canTransitionTo(TaskState::Started));
    }

    public function test_started_can_transition_to_completed(): void
    {
        $this->assertTrue(TaskState::Started->canTransitionTo(TaskState::Completed));
    }

    public function test_completed_can_transition_to_started_uncomplete(): void
    {
        $this->assertTrue(TaskState::Completed->canTransitionTo(TaskState::Started));
    }

    public function test_started_cannot_transition_to_added(): void
    {
        $this->assertFalse(TaskState::Started->canTransitionTo(TaskState::Added));
    }
}
