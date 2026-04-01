<?php

namespace Database\Factories;

use App\Enums\ResourceRating;
use App\Models\Resource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory()->started(),
            'survey_name' => 'Survey '.fake()->numberBetween(1, 10),
            'at_home' => fake()->randomElement(ResourceRating::cases()),
            'at_work' => fake()->randomElement(ResourceRating::cases()),
            'at_play' => fake()->randomElement(ResourceRating::cases()),
            'details' => fake()->optional()->paragraph(),
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
            'tenant_id' => 1,
        ];
    }
}
