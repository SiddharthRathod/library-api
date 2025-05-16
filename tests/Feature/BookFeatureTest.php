<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;


class BookFeatureTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_book_index_returns_paginated_list()
    {
        $this->authenticate();
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/books');
        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_book_show_returns_specific_book()
    {
        $this->authenticate();
        $book = Book::factory()->create();

        $response = $this->getJson("/api/books/{$book->id}");
        $response->assertStatus(201)->assertJsonFragment(['title' => $book->title]);
    }

    public function test_book_store_creates_a_book()
    {
        $this->authenticate();

        $data = [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'available',
            'description' => 'A test book',
        ];

        $response = $this->postJson('/api/books', $data);
        $response->assertStatus(201)->assertJsonFragment(['title' => 'Test Book']);
    }

    public function test_book_update_modifies_book()
    {
        $this->authenticate();
        $book = Book::factory()->create(['title' => 'Old Title']);

        $response = $this->putJson("/api/books/{$book->id}", ['title' => 'New Title']);
        $response->assertStatus(201)->assertJsonFragment(['title' => 'New Title']);
    }

    public function test_book_delete_successful()
    {
        $this->authenticate();
        $book = Book::factory()->create(['status' => 'available']);

        $response = $this->deleteJson("/api/books/{$book->id}");
        $response->assertStatus(201)->assertJsonFragment(['message' => 'Book deleted successfully']);
    }

    public function test_book_delete_fails_if_borrowed()
    {
        $this->authenticate();
        $book = Book::factory()->create(['status' => 'borrowed']);

        $response = $this->deleteJson("/api/books/{$book->id}");
        $response->assertStatus(422)->assertJsonFragment(['Can not delete a borrowed book.']);
    }
}
