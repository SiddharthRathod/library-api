<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Book;
use App\Models\User;
use App\Models\Borrowing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

class BorrowingControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_borrow_requires_authentication()
    {
        $book = Book::factory()->create();
        $response = $this->postJson('/api/borrowings', ['book_id' => $book->id]);

        $response->assertStatus(401);
    }

    public function test_borrow_fails_if_book_does_not_exist()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/borrowings', ['book_id' => 999]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['Book not found.']);
    }
}

