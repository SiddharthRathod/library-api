<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Faker;

class AuthFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = \Faker\Factory::create();
    }

    public function test_register_successfully()
    {
        Role::firstOrCreate(['name' => 'user']);

        $name = $this->faker->name();
        $email = $this->faker->unique()->safeEmail();
        $password = $this->faker->password(8, 20);

        $response = $this->postJson('/api/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'user'
        ]);

        $response->assertStatus(201)
                 ->assertJson(['status' => 'success']);
    }

    public function test_register_validation_fails()
    {
        $response = $this->postJson('/api/register', [
            'email' => $this->faker->word() // Invalid email format
        ]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'failed']);
    }

    public function test_login_successfully()
    {
        $email = $this->faker->unique()->safeEmail();
        $password = 'password';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }

    public function test_login_fails_with_wrong_password()
    {
        $email = $this->faker->unique()->safeEmail();
        $correctPassword = $this->faker->password(8, 20);
        $wrongPassword = $this->faker->password(8, 20) . 'wrong';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt($correctPassword),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => $wrongPassword,
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

        $newName = $this->faker->name();
        
        $response = $this->putJson('api/user/update', [
            'name' => $newName
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