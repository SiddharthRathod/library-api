<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

class BookControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_book_store_creates_a_book()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Sanctum::actingAs($user, ['*']); // Authenticate the user

        $data = [
            'title' => 'Test New Book',
            'author' => 'Test Author',
            'isbn' => '12345678907788998',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'available',
            'description' => 'A test book',
        ];
        
        $response = $this->postJson('/api/books', $data);
        $response->assertStatus(201)->assertJsonFragment(['title' => 'Test New Book']);
    }

}
