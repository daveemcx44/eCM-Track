<?php

namespace Tests\Unit;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResetUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    private ResetUserPassword $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ResetUserPassword();
    }

    public function test_it_resets_user_password(): void
    {
        $user = User::factory()->create();

        $this->action->reset($user, [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $user->refresh();
        $this->assertTrue(password_verify('NewPassword123!', $user->password));
    }

    public function test_it_fails_without_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        $this->action->reset($user, [
            'password' => 'NewPassword123!',
        ]);
    }

    public function test_it_persists_new_password_to_database(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $this->action->reset($user, [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $user->refresh();
        $this->assertNotEquals($oldPassword, $user->password);
    }
}
