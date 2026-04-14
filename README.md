# Translation Management Service

A RESTful API for managing multi-locale translations with tag-based categorisation, full-text search, and Redis-cached exports. Built with Laravel 13, MySQL 8, and Docker.

---

## Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 (PHP 8.4) |
| Auth | Laravel Sanctum (Bearer token) |
| Database | MySQL 8.0 |
| Cache | Redis |
| Testing | Pest v4 |
| Containers | Docker + Nginx |

---

## Quick Start

### Prerequisites

- Docker Desktop running

### 1. Clone and copy environment file

```bash
git clone <your-repo-url>
cd translation-service
cp .env.example .env
```

### 2. Update `.env` database credentials

```dotenv
DB_HOST=db
DB_PORT=3306
DB_DATABASE=translation_service
DB_USERNAME=translation_user
DB_PASSWORD=secret

REDIS_HOST=redis

CACHE_STORE=redis
```

### 3. Start containers

```bash
docker compose up -d --build
```

This starts four services: **nginx** (port 8000), **app** (PHP-FPM), **db** (MySQL 8, host port 3307), and **redis**.

### 4. Install dependencies and run migrations

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Create a user for authentication

> **Required before using the API or Swagger UI.** This is the account you will log in with.

```bash
docker compose exec app php artisan tinker --execute="App\Models\User::factory()->create(['email'=>'admin@example.com','password'=>bcrypt('password')])"
```

### 6. Open the API documentation

Visit **http://localhost:8000** — it redirects to the interactive Swagger UI at `/api-docs.html`.

**To authenticate in Swagger UI:**

1. Click **POST /api/v1/auth/token** → **Try it out** → execute with `admin@example.com` / `password`
2. Copy the `token` value from the response
3. Click **Authorize** (lock icon, top right) → paste the token → **Authorize**
4. All protected endpoints are now unlocked — you can test them directly in the browser

> The user created in step 5 is required for step 1 to succeed. Without it, the token request returns 401.

---

## Running Tests

### Standard suite (unit + feature)

```bash
docker compose exec app ./vendor/bin/pest --exclude-group=performance
```

### Performance tests — run separately

```bash
docker compose exec app ./vendor/bin/pest --group=performance
```

> **Run performance tests on their own.** Each test seeds 100 000 records in `beforeEach`, which takes ~40 seconds total. Running them alongside the standard suite would make every non-performance test wait for the full seed on every run.

---

## Seed 100 000 Translations

```bash
docker compose exec app php artisan translations:seed
# Custom count:
docker compose exec app php artisan translations:seed --count=50000
```

---

## API Endpoints

All protected routes require `Authorization: Bearer <token>`.

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/auth/token` | Issue Sanctum token |
| GET | `/api/v1/translations` | Paginated list with filters |
| POST | `/api/v1/translations` | Create translation |
| GET | `/api/v1/translations/{id}` | Get single translation |
| PUT | `/api/v1/translations/{id}` | Update translation |
| DELETE | `/api/v1/translations/{id}` | Delete translation |
| GET | `/api/v1/export/{locale}` | Export key→value map (cached) |
| GET | `/api/v1/tags` | List all tags |
| POST | `/api/v1/tags` | Create tag |

### Search filters (GET /api/v1/translations)

| Param | Behaviour |
|---|---|
| `key` | Partial match (`LIKE %value%`) |
| `locale` | Exact match |
| `content` | MySQL FULLTEXT for ≥ 3 chars, LIKE fallback |
| `tag` | Filters by tag name |
| `per_page` | 1–100, default 15 |

---

## Design Choices

### Service-Repository Pattern

Business logic lives in `TranslationService`, data access in `EloquentTranslationRepository`, bound together via `TranslationRepositoryInterface`. This inverts the dependency so services are testable without a real database (unit tests mock the interface), and the repository can be swapped without touching service code.

### Data Transfer Objects (DTOs)

`CreateTranslationDTO` and `UpdateTranslationDTO` are PHP 8.4 `readonly` value objects built directly from validated request data. They make method signatures explicit, prevent partial or invalid state from reaching the service layer, and are trivial to unit-test.

### API Resources

`TranslationResource` and `TagResource` use `whenLoaded()` to guard against N+1 queries. The response shape is decoupled from the Eloquent model — renaming a column never breaks the API contract.

### Redis Export Cache

`GET /api/v1/export/{locale}` is the hot path in production (CDN miss, mobile app boot, etc.). The result is cached per locale with a 1-hour TTL. Any create, update, or delete that affects that locale calls `invalidateCache()` immediately, so the cache is never stale. Using the injected `CacheRepository` (rather than the `Cache` facade) keeps the service unit-testable.

### MySQL FULLTEXT + Composite Index

The `translations` table has:
- `FULLTEXT` index on `content` — used when the search term is ≥ 3 characters
- `UNIQUE (key, locale)` — enforced at the DB level, not just in validation
- Individual indexes on `key` and `locale` — used by paginate filters

### Why MySQL for Tests (not SQLite)

The test suite runs against a real MySQL 8 database (`translation_service_test`, created automatically by `docker/mysql/init.sql`). SQLite was ruled out because it does not support `FULLTEXT` indexes or the `whereFullText()` query used in content search — tests would pass against SQLite and silently fail in production. Running the same engine in tests and production eliminates that class of divergence entirely.

### Batch Insert Seeder

The seeder inserts in batches of 1 000 rows using raw `DB::table()->insert()` to bypass Eloquent overhead. Pivot records are built using `lastInsertId()` (which returns the first ID of a batch on MySQL) and inserted with `insertOrIgnore`. This seeds 100 000 translations in seconds.

### Security

- **Sanctum token auth** — stateless Bearer tokens, no session cookies
- **FormRequest validation** — all input validated and typed before reaching the service
- **Unique constraint** — duplicate `key + locale` rejected at both validation and DB level
- **No mass-assignment exposure** — `$fillable` is explicit on every model

### Testing Strategy

| Suite | Count | What it covers |
|---|---|---|
| Unit (`tests/Unit`) | 8 | Service logic with mocked repository and cache |
| Feature (`tests/Feature`) | 38 | Full HTTP stack — CRUD, search, auth, export, tags |
| Performance (`--group=performance`) | 5 | 100 000-row DB — export cold/warm cache, search by locale/key/tag |

---

## Production Considerations

**Laravel Octane with FrankenPHP** would be the natural next step for production — eliminating per-request framework bootstrap overhead. Omitted here to keep the Docker setup clean and the scope focused on correctness over raw throughput.
