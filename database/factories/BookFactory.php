<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'publisher' => fake()->company(),
            'author' => fake()->name(),
            'genre' => fake()->randomElement([
                'Fiction',
                'Non-Fiction',
                'Science Fiction',
                'Fantasy',
                'Mystery',
                'Biography',
                'History',
                'Romance',
                'Thriller',
                'Self-Help',
            ]),
            'publication_date' => fake()->dateTimeBetween('-50 years', 'now')->format('Y-m-d'),
            'word_count' => fake()->numberBetween(10_000, 250_000),
            'price_usd' => fake()->randomFloat(2, 1, 200),
        ];
    }
}
