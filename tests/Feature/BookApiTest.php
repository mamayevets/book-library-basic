<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_list_of_books(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/books');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'publisher',
                        'author',
                        'genre',
                        'publication_date',
                        'word_count',
                        'price_usd',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_returns_empty_array_when_no_books(): void
    {
        $response = $this->getJson('/api/books');

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_show_returns_a_single_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/books/{$book->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.author', $book->author);
    }

    public function test_show_returns_404_for_missing_book(): void
    {
        $this->getJson('/api/books/99999')->assertNotFound();
    }

    public function test_store_creates_a_new_book(): void
    {
        $payload = [
            'title' => 'Domain-Driven Design',
            'publisher' => 'Addison-Wesley',
            'author' => 'Eric Evans',
            'genre' => 'Programming',
            'publication_date' => '2003-08-22',
            'word_count' => 200000,
            'price_usd' => 49.99,
        ];

        $response = $this->postJson('/api/books', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Domain-Driven Design')
            ->assertJsonPath('data.price_usd', 49.99);

        $this->assertDatabaseHas('books', [
            'title' => 'Domain-Driven Design',
            'author' => 'Eric Evans',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/books', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'publisher',
                'author',
                'genre',
                'publication_date',
                'word_count',
                'price_usd',
            ]);
    }

    public function test_store_rejects_negative_word_count(): void
    {
        $response = $this->postJson('/api/books', $this->validPayload(['word_count' => -10]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_count']);
    }

    public function test_store_rejects_negative_price(): void
    {
        $response = $this->postJson('/api/books', $this->validPayload(['price_usd' => -1]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_usd']);
    }

    public function test_store_rejects_future_publication_date(): void
    {
        $response = $this->postJson('/api/books', $this->validPayload([
            'publication_date' => now()->addYear()->toDateString(),
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['publication_date']);
    }

    public function test_update_partially_updates_a_book(): void
    {
        $book = Book::factory()->create([
            'title' => 'Original Title',
            'price_usd' => 10.00,
        ]);

        $response = $this->patchJson("/api/books/{$book->id}", [
            'price_usd' => 19.99,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Original Title')
            ->assertJsonPath('data.price_usd', 19.99);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'price_usd' => 19.99,
        ]);
    }

    public function test_update_validates_provided_fields(): void
    {
        $book = Book::factory()->create();

        $response = $this->patchJson("/api/books/{$book->id}", [
            'word_count' => -5,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word_count']);
    }

    public function test_update_returns_404_for_missing_book(): void
    {
        $this->patchJson('/api/books/99999', ['title' => 'X'])->assertNotFound();
    }

    public function test_destroy_deletes_a_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_destroy_returns_404_for_missing_book(): void
    {
        $this->deleteJson('/api/books/99999')->assertNotFound();
    }

    public function test_index_paginates_results(): void
    {
        Book::factory()->count(20)->create();

        $response = $this->getJson('/api/books');

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Book',
            'publisher' => 'Test Publisher',
            'author' => 'Test Author',
            'genre' => 'Fiction',
            'publication_date' => '2020-01-01',
            'word_count' => 50000,
            'price_usd' => 19.99,
        ], $overrides);
    }
}
