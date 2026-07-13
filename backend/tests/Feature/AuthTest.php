<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->for($this->company)->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->for($this->company)->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->for($this->company)->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->for($this->company)->create();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
        $this->getJson('/api/companies')->assertUnauthorized();
    }
}
