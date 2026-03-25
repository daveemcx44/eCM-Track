<?php

namespace Tests\Unit;

use App\Actions\Jetstream\DeleteUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    private DeleteUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new DeleteUser();
    }

    public function test_it_deletes_a_user(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $this->action->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_it_deletes_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->action->delete($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}
