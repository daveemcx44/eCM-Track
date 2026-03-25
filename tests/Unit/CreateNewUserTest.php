<?php

namespace Tests\Unit;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    private CreateNewUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CreateNewUser();
    }

    public function test_it_creates_a_user_with_valid_data(): void
    {
        $user = $this->action->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_it_hashes_the_password(): void
    {
        $user = $this->action->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertNotEquals('Password123!', $user->password);
        $this->assertTrue(password_verify('Password123!', $user->password));
    }

    public function test_it_fails_without_name(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    }

    public function test_it_fails_without_email(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name' => 'John Doe',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    }

    public function test_it_fails_with_invalid_email(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    }

    public function test_it_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->expectException(ValidationException::class);

        $this->action->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    }

    public function test_it_fails_without_password_confirmation(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);
    }

    public function test_it_fails_with_mismatched_password_confirmation(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);
    }
}
