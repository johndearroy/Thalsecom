<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@thalsecom.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'vendor'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'authorization' => [
                        'access_token',
                        'token_type',
                        'expires_in'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@thalsecom.com'
        ]);
    }

    public function test_user_cannot_register_with_existing_email()
    {
        $customerRole = Role::query()->where('name', 'customer')->first();

        User::query()->create([
            'name' => 'Existing User',
            'email' => 'existing@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $customerRole->id,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@thalsecom.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $customerRole = Role::query()->where('name', 'customer')->first();

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $customerRole->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@thalsecom.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user'
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@thalsecom.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $customerRole = Role::query()->where('name', 'customer')->first();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testcustomer@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $customerRole->id,
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'status_code',
                'data' => [
                    ['name', 'email',]
                ]
            ]);
    }

    public function test_user_can_logout()
    {
        $customerRole = Role::query()->where('name', 'customer')->first();

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $customerRole->id,
        ]);

        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }
}
