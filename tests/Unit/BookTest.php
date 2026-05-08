<?php

namespace Tests\Unit;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_date_is_cast_to_carbon_instance(): void
    {
        $book = Book::factory()->create([
            'publication_date' => '2020-06-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $book->publication_date);
        $this->assertSame('2020-06-15', $book->publication_date->toDateString());
    }

    public function test_word_count_is_cast_to_integer(): void
    {
        $book = Book::factory()->create(['word_count' => '12345']);

        $this->assertIsInt($book->fresh()->word_count);
    }

    public function test_price_usd_is_cast_to_decimal_string(): void
    {
        $book = Book::factory()->create(['price_usd' => 9.5]);

        $this->assertSame('9.50', $book->fresh()->price_usd);
    }

    public function test_only_fillable_attributes_are_mass_assignable(): void
    {
        $book = new Book;

        $this->assertSame([
            'title',
            'publisher',
            'author',
            'genre',
            'publication_date',
            'word_count',
            'price_usd',
        ], $book->getFillable());
    }
}
