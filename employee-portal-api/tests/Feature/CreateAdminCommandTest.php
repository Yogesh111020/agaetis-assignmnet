<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_administrator_with_a_hashed_password(): void
    {
        $this->artisan('portal:create-admin')
            ->expectsQuestion('Administrator name', 'Test Admin')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Password (at least 12 characters, letters and numbers)', 'ExamplePassword123')
            ->expectsOutput('Administrator created.')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('ExamplePassword123', $user->password));
    }

    public function test_command_rejects_a_weak_password_without_creating_a_user(): void
    {
        $this->artisan('portal:create-admin')
            ->expectsQuestion('Administrator name', 'Test Admin')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Password (at least 12 characters, letters and numbers)', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
