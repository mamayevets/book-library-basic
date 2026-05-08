<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Book Library API',
    description: 'REST API for tracking books in a library.',
    contact: new OA\Contact(name: 'API Support', email: 'support@example.com')
)]
#[OA\Server(url: 'http://localhost', description: 'Local Sail environment')]
#[OA\Tag(name: 'Books', description: 'Book CRUD operations')]
#[OA\Schema(
    schema: 'Book',
    title: 'Book',
    description: 'A book record.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Clean Code'),
        new OA\Property(property: 'publisher', type: 'string', example: 'Prentice Hall'),
        new OA\Property(property: 'author', type: 'string', example: 'Robert C. Martin'),
        new OA\Property(property: 'genre', type: 'string', example: 'Programming'),
        new OA\Property(property: 'publication_date', type: 'string', format: 'date', example: '2008-08-01'),
        new OA\Property(property: 'word_count', type: 'integer', example: 120000),
        new OA\Property(property: 'price_usd', type: 'number', format: 'float', example: 35.99),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'BookInput',
    title: 'BookInput',
    description: 'Payload for creating or updating a book.',
    required: ['title', 'publisher', 'author', 'genre', 'publication_date', 'word_count', 'price_usd'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Clean Code'),
        new OA\Property(property: 'publisher', type: 'string', maxLength: 255, example: 'Prentice Hall'),
        new OA\Property(property: 'author', type: 'string', maxLength: 255, example: 'Robert C. Martin'),
        new OA\Property(property: 'genre', type: 'string', maxLength: 100, example: 'Programming'),
        new OA\Property(property: 'publication_date', type: 'string', format: 'date', example: '2008-08-01'),
        new OA\Property(property: 'word_count', type: 'integer', minimum: 1, example: 120000),
        new OA\Property(property: 'price_usd', type: 'number', format: 'float', minimum: 0, example: 35.99),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    title: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
    ]
)]
abstract class Controller
{
    //
}
