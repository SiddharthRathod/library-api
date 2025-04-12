<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'author' => $this->faker->name(),
            'isbn' => $this->faker->isbn13(),
            'published_at' => $this->faker->date(),
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement(['available', 'borrowed']),
        ];
    }
}
