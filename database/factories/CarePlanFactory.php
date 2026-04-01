<?php

namespace Database\Factories;

use App\Models\CarePlan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePlanFactory extends Factory
{
    protected $model = CarePlan::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'version_number' => 1,
            'assessment_type' => $this->faker->randomElement(['Initial', 'Reassessment', 'Annual']),
            'assessment_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'risk_level' => $this->faker->randomElement(['Low', 'Medium', 'High']),
            'next_reassessment_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'tenant_id' => 1,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_reassessment_date' => now()->subDays(30),
        ]);
    }
}
