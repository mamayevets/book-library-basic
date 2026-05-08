<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: '/api/books',
        tags: ['Books'],
        summary: 'List all books (paginated)',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of books',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Book')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $books = Book::query()
            ->orderByDesc('id')
            ->paginate(perPage: 15);

        return BookResource::collection($books);
    }

    #[OA\Post(
        path: '/api/books',
        tags: ['Books'],
        summary: 'Create a new book',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Book created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')])
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());

        return BookResource::make($book)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/books/{id}',
        tags: ['Books'],
        summary: 'Show a single book',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book found',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')])
            ),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function show(Book $book): BookResource
    {
        return BookResource::make($book);
    }

    #[OA\Patch(
        path: '/api/books/{id}',
        tags: ['Books'],
        summary: 'Partially update a book',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Book')])
            ),
            new OA\Response(response: 404, description: 'Book not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->validated());

        return BookResource::make($book);
    }

    #[OA\Delete(
        path: '/api/books/{id}',
        tags: ['Books'],
        summary: 'Delete a book',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Book deleted'),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function destroy(Book $book): Response
    {
        $book->delete();

        return response()->noContent();
    }
}
