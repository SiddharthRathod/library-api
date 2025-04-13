<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_validation_fails()
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_login_with_wrong_credentials_fails()
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password')
        ]);

        $this->assertFalse(Hash::check('wrong-password', $user->password));
    }

    public function test_user_can_update_fields_correctly()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $newData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $user->update($newData);

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
    }
}

