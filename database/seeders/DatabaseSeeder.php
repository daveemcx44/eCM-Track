<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Note;
use App\Models\Problem;
use App\Models\Resource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $careManagers = User::factory(4)->create();

        // Create members with assigned lead care managers
        $members = Member::factory(10)
            ->sequence(fn ($sequence) => [
                'lead_care_manager' => $careManagers->random()->id,
            ])
            ->create();

        // One JI-blocked member
        Member::factory()->jiBlocked()->create([
            'lead_care_manager' => $careManagers->first()->id,
        ]);

        foreach ($members as $member) {
            // Each member gets 1-3 problems in various states
            $addedProblems = Problem::factory(rand(1, 2))
                ->for($member)
                ->create(['submitted_by' => $testUser->id]);

            $confirmedProblems = Problem::factory(rand(1, 2))
                ->confirmed()
                ->for($member)
                ->create([
                    'submitted_by' => $testUser->id,
                    'confirmed_by' => $careManagers->random()->id,
                ]);

            // Some members have resolved problems
            if (rand(0, 1)) {
                Problem::factory()
                    ->resolved()
                    ->for($member)
                    ->create([
                        'submitted_by' => $testUser->id,
                        'confirmed_by' => $careManagers->random()->id,
                        'resolved_by' => $careManagers->random()->id,
                    ]);
            }

            // Add tasks to confirmed problems
            foreach ($confirmedProblems as $problem) {
                Task::factory(rand(1, 3))
                    ->for($problem)
                    ->create(['submitted_by' => $testUser->id]);

                // Some started tasks with resources
                $startedTasks = Task::factory(rand(0, 2))
                    ->started()
                    ->for($problem)
                    ->create([
                        'submitted_by' => $testUser->id,
                        'started_by' => $careManagers->random()->id,
                    ]);

                foreach ($startedTasks as $task) {
                    Resource::factory(rand(1, 2))->create([
                        'task_id' => $task->id,
                        'submitted_by' => $careManagers->random()->id,
                    ]);
                }

                // Some completed tasks
                if (rand(0, 1)) {
                    Task::factory()
                        ->completed()
                        ->for($problem)
                        ->create([
                            'submitted_by' => $testUser->id,
                            'started_by' => $careManagers->random()->id,
                            'completed_by' => $careManagers->random()->id,
                        ]);
                }

                // A goal task per problem
                Task::factory()
                    ->goal()
                    ->for($problem)
                    ->create(['submitted_by' => $testUser->id]);

                // Add notes to problems
                Note::factory(rand(1, 3))->create([
                    'notable_type' => Problem::class,
                    'notable_id' => $problem->id,
                    'created_by' => $careManagers->random()->id,
                ]);
            }
        }
    }
}
