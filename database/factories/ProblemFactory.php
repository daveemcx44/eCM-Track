<?php

namespace Database\Factories;

use App\Enums\EncounterSetting;
use App\Enums\ProblemState;
use App\Enums\ProblemType;
use App\Models\Member;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProblemFactory extends Factory
{
    protected $model = Problem::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(ProblemType::cases()),
            'code' => fake()->optional()->bothify('???-####'),
            'encounter_setting' => fake()->randomElement(EncounterSetting::cases()),
            'state' => ProblemState::Added,
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => ProblemState::Confirmed,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => ProblemState::Resolved,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
