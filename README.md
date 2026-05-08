# Book Library API

[![Tests](https://github.com/mamayevets/book-library-basic/actions/workflows/tests.yml/badge.svg)](https://github.com/mamayevets/book-library-basic/actions/workflows/tests.yml)

REST API for tracking books in a library. Built with Laravel 13, MySQL 8.4, and PHP 8.5, fully containerized via Laravel Sail.

## Stack

- **PHP 8.5** (latest)
- **Laravel 13.7** (latest)
- **MySQL 8.4** (in Docker)
- **PHPUnit 11** for testing
- **L5-Swagger** for OpenAPI documentation
- **Laravel Sail** as docker-compose wrapper

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) running
- Git
- Free ports: `80`, `3306`, `5173`

## Quick start

```bash
git clone <repo-url> book-library-basic
cd book-library-basic
cp .env.example .env

# Build and start containers (first run is slow, ~5-10 min)
./vendor/bin/sail up -d

# Generate app key, run migrations, seed 25 fake books
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed

# Generate Swagger documentation
./vendor/bin/sail artisan l5-swagger:generate
```

The API is now running at **http://localhost**.

## Endpoints

| Method | URL | Description | Status codes |
|---|---|---|---|
| `GET` | `/api/books` | List all books (paginated, 15 per page) | 200 |
| `GET` | `/api/books/{id}` | Show a single book | 200, 404 |
| `POST` | `/api/books` | Create a new book | 201, 422 |
| `PATCH` | `/api/books/{id}` | Partially update a book | 200, 404, 422 |
| `DELETE` | `/api/books/{id}` | Delete a book | 204, 404 |

### Book schema

| Field | Type | Notes |
|---|---|---|
| `title` | string | required, max 255 |
| `publisher` | string | required, max 255 |
| `author` | string | required, max 255 |
| `genre` | string | required, max 100 |
| `publication_date` | date (YYYY-MM-DD) | required, must be in the past or today |
| `word_count` | integer | required, min 1 |
| `price_usd` | decimal(10, 2) | required, min 0, max 999999.99 |

### Example: create a book

```bash
curl -X POST http://localhost/api/books \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Clean Code",
    "publisher": "Prentice Hall",
    "author": "Robert C. Martin",
    "genre": "Programming",
    "publication_date": "2008-08-01",
    "word_count": 120000,
    "price_usd": 35.99
  }'
```

## Swagger UI

Interactive API documentation is available at:

**http://localhost/api/documentation**

Raw OpenAPI 3.0 JSON spec at: `http://localhost/docs`.

To regenerate after editing annotations:

```bash
./vendor/bin/sail artisan l5-swagger:generate
```

## Running tests

```bash
./vendor/bin/sail test
```

Tests run on **SQLite in-memory** (configured in `phpunit.xml`) — no MySQL required, ~500ms full suite. Each test refreshes the schema via `RefreshDatabase` trait, so they are fully isolated.

### CI

A GitHub Actions workflow (`.github/workflows/tests.yml`) runs the full PHPUnit suite on every push and pull request to `main`. The build status is shown in the badge at the top of this README.

Test layout:

- `tests/Feature/BookApiTest.php` — HTTP-level tests for every endpoint, validation, edge cases (15 tests)
- `tests/Unit/BookTest.php` — model casting and fillable assertions (4 tests)

Total: **19 tests, 86 assertions**.

## Useful Sail commands

```bash
./vendor/bin/sail up -d              # start containers in background
./vendor/bin/sail down               # stop and remove containers
./vendor/bin/sail logs -f            # tail container logs
./vendor/bin/sail artisan migrate    # run new migrations
./vendor/bin/sail artisan db:seed    # re-seed the database
./vendor/bin/sail artisan tinker     # interactive REPL
./vendor/bin/sail mysql              # open MySQL CLI
./vendor/bin/sail shell              # bash inside the app container
```

## Project structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/BookController.php   ← CRUD logic + OpenAPI annotations
│   │   ├── Requests/StoreBookRequest.php    ← validation for POST
│   │   ├── Requests/UpdateBookRequest.php   ← validation for PATCH
│   │   └── Resources/BookResource.php       ← JSON response formatter
│   └── Models/Book.php                      ← Eloquent model
├── database/
│   ├── migrations/..._create_books_table.php
│   ├── factories/BookFactory.php            ← faker-based generator
│   └── seeders/BookSeeder.php               ← seeds 25 books
├── routes/api.php                           ← apiResource('books', BookController::class)
├── tests/
│   ├── Feature/BookApiTest.php
│   └── Unit/BookTest.php
├── compose.yaml                             ← Docker services (PHP + MySQL)
└── README.md
```

## Notes

- `price_usd` is stored as `decimal(10, 2)` for simplicity. In production-grade code, prices should be stored as integers (cents) to avoid floating-point arithmetic issues.
- Sanctum was installed as a side effect of `artisan install:api`. It is not used by the books endpoints (no auth required by the spec).
- API responses are wrapped in a `data` key — this is Laravel's `JsonResource` convention and stays consistent across endpoints.
