<?php

namespace Database\Factories;

use App\Enums\OutreachMethod;
use App\Enums\OutreachOutcome;
use App\Models\Member;
use App\Models\OutreachLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutreachLogFactory extends Factory
{
    protected $model = OutreachLog::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'method' => $this->faker->randomElement(OutreachMethod::cases()),
            'outreach_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'outcome' => $this->faker->randomElement(OutreachOutcome::cases()),
            'notes' => $this->faker->optional()->sentence(),
            'staff_id' => User::factory(),
            'logged_at' => now(),
            'tenant_id' => 1,
        ];
    }
}
