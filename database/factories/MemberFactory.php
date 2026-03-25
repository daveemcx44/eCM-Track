<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'dob' => fake()->date('Y-m-d', '-18 years'),
            'member_id' => fake()->unique()->numerify('######'),
            'organization' => fake()->randomElement(['Serene Health', 'Valley Care', 'Pacific Health Network', 'Golden State Medical']),
            'status' => 'active',
            'ji_consent_status' => null,
        ];
    }

    public function jiBlocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'ji_consent_status' => 'no_consent',
        ]);
    }
}
