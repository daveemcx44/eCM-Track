<?php

namespace Database\Factories;

use App\Enums\EncounterSetting;
use App\Enums\TaskState;
use App\Enums\TaskType;
use App\Models\Problem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'problem_id' => Problem::factory()->confirmed(),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(TaskType::cases()),
            'code' => fake()->optional()->bothify('???-####'),
            'encounter_setting' => fake()->randomElement(EncounterSetting::cases()),
            'state' => TaskState::Added,
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
        ];
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => TaskState::Started,
            'started_by' => User::factory(),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => TaskState::Completed,
            'started_by' => User::factory(),
            'started_at' => now(),
            'completed_by' => User::factory(),
            'completed_at' => now(),
        ]);
    }

    public function goal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TaskType::Goal,
        ]);
    }
}
