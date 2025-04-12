<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Database\Eloquent\Factories\Factory;

class BorrowingFactory extends Factory
{
    protected $model = Borrowing::class;

    public function definition(): array
    {
        $borrowedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $returnedAt = $this->faker->boolean(50) ? $this->faker->dateTimeBetween($borrowedAt, 'now') : null;

        // Create a book and mark it as 'borrowed'
        $book = Book::factory()->create([
            'status' => $returnedAt ? 'available' : 'borrowed',
        ]);

        return [
            'user_id'       => User::factory(),
            'book_id'       => $book->id,
            'borrowed_at'   => $borrowedAt,
            'returned_at'   => $returnedAt,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
