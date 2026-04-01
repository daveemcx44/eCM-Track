<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'notable_type' => Problem::class,
            'notable_id' => Problem::factory(),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
            'tenant_id' => 1,
        ];
    }

    public function forProblem(Problem $problem): static
    {
        return $this->state(fn (array $attributes) => [
            'notable_type' => Problem::class,
            'notable_id' => $problem->id,
        ]);
    }
}
