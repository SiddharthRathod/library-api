<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
     public function user_can_register_with_valid_data(): void
     {
         $response = $this->postJson('/api/register', [
             'name' => 'John Doe',
             'email' => 'john@example.com',
             'password' => 'password123',
             'password_confirmation' => 'password123',
             'role' => 'user',
         ]);
 
         $response->assertCreated()
             ->assertJson([
                 'status' => 'success',
                 'error' => false,
                 'message' => 'User registered successfully.'
             ]);
 
         $this->assertDatabaseHas('users', [
             'email' => 'john@example.com',
         ]);
     }
 
     #[Test]
     public function registration_fails_with_missing_fields(): void
     {
         $response = $this->postJson('/api/register', []);
 
         $response->assertUnprocessable()
             ->assertJson([
                 'status' => 'failed',
                 'error' => true,
             ]);
     }
 
     #[Test]
     public function user_can_login_with_correct_credentials(): void
     {
         $user = User::factory()->create([
             'email' => 'jane@example.com',
             'password' => bcrypt('password123')
         ]);
 
         $response = $this->postJson('/api/login', [
             'email' => 'jane@example.com',
             'password' => 'password123',
         ]);
 
         $response->assertOk()
             ->assertJson([
                 'status' => 'success',
                 'message' => 'Login successful.',
             ]);
     }
 
     #[Test]
     public function login_fails_with_invalid_password(): void
     {
         $user = User::factory()->create([
             'email' => 'jane@example.com',
             'password' => bcrypt('password123'),
         ]);
 
         $response = $this->postJson('/api/login', [
             'email' => 'jane@example.com',
             'password' => 'wrongpassword',
         ]);
 
         $response->assertStatus(422);
     }
 
     #[Test]
     public function authenticated_user_can_view_profile(): void
     {
         $user = User::factory()->create();
         Sanctum::actingAs($user);
 
         $response = $this->getJson('/api/user/show');
 
         $response->assertOk()
             ->assertJson([
                 'status' => 'success',
                 'message' => 'User details retrieved successfully.'
             ]);
     }
 
     #[Test]
     public function unauthenticated_user_cannot_view_profile(): void
     {
         $response = $this->getJson('/api/user/show');
 
         $response->assertUnauthorized();
     }
 
     #[Test]
     public function user_can_update_profile_information(): void
     {
         $user = User::factory()->create();
         Sanctum::actingAs($user);
 
         $response = $this->putJson('/api/user/update', [
             'name' => 'New Name',
             'email' => 'newemail@example.com',
             'password' => 'newpassword123',
         ]);
 
         $response->assertCreated()
             ->assertJson([
                 'status' => 'success',
                 'message' => 'User updated successfully.',
             ]);
 
         $this->assertDatabaseHas('users', [
             'email' => 'newemail@example.com',
         ]);
     }
 
     #[Test]
     public function user_can_logout_successfully(): void
     {
         $user = User::factory()->create();
         Sanctum::actingAs($user);
 
         $response = $this->postJson('/api/user/logout');
 
         $response->assertOk()
             ->assertJson([
                 'status' => 'success',
                 'message' => 'Logged out successfully.'
             ]);
     }
 
     #[Test]
     public function logout_fails_if_not_authenticated(): void
     {
         $response = $this->postJson('/api/user/logout');
 
         $response->assertStatus(401);
     }
}

