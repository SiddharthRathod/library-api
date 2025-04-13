<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Book;
use App\Models\User;
use App\Models\Borrowing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Event;
use App\Events\BookBorrowed;
use App\Events\BookReturned;

class BorrowingFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_borrow_book_successfully()
    {
        Event::fake();
        $user = User::factory()->create();
        $book = Book::factory()->create(['status' => 'available']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/borrowings', ['book_id' => $book->id]);
        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('borrowings', ['user_id' => $user->id, 'book_id' => $book->id]);
        $this->assertEquals('borrowed', $book->fresh()->status);

        Event::assertDispatched(BookBorrowed::class);
    }

    public function test_borrow_fails_if_book_already_borrowed()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['status' => 'borrowed']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/borrowings', ['book_id' => $book->id]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['Book already borrowed.']);
    }

    public function test_return_book_successfully()
    {
        Event::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create(['status' => 'borrowed']);

        $borrowing = Borrowing::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'returned_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/borrowing-return/{$borrowing->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['Book returned successfully']);

        $this->assertNotNull($borrowing->fresh()->returned_at);
        $this->assertEquals('available', $book->fresh()->status);

        Event::assertDispatched(BookReturned::class);
    }

    public function test_return_fails_if_already_returned()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['status' => 'available']);

        $borrowing = Borrowing::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'returned_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/borrowing-return/{$borrowing->id}");

        $response->assertStatus(422)
                 ->assertJsonFragment(['Book already returned.']);
    }

    public function test_return_fails_if_not_found()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/borrowing-return/9999");

        $response->assertStatus(404)
                 ->assertJsonFragment(['Borrowing not found.']);
    }
}

