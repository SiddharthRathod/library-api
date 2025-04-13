<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class AuthFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_successfully()
    {
        Role::firstOrCreate(['name' => 'user']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user'
        ]);

        $response->assertStatus(201)
                 ->assertJson(['status' => 'success']);
    }

    public function test_register_validation_fails()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'not-an-email'
        ]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'failed']);
    }

    public function test_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }

    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_show_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user/show');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }

    public function test_update_user_info()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('api/user/update', [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'User updated successfully.']);
    }

    public function test_logout_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully.']);
    }
}