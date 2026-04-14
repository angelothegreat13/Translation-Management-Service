# Translation Management Service

A RESTful API for managing multi-locale translations with tag-based categorisation, full-text search, and Redis-cached exports. Built with Laravel 13, MySQL 8, and Docker.

---

## Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 (PHP 8.4) |
| Auth | Laravel Sanctum (Bearer token) |
| Database | MySQL 8.0 |
| Cache | Redis 7 |
| Testing | Pest v4 |
| Containers | Docker + Nginx |

---

## Quick Start

### Prerequisites

- Docker Desktop running

### 1. Clone the repository

```bash
git clone <your-repo-url>
cd translation-service
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

> The credentials in `.env.example` already match the Docker services (`DB_HOST=db`, `DB_USERNAME=laravel`, `DB_PASSWORD=secret`, `REDIS_HOST=redis`). No manual edits needed.

### 3. Start all containers

```bash
docker compose up -d --build
```

This starts four services:

| Container | Role | Host port |
|---|---|---|
| `ts_nginx` | Nginx web server | 8000 |
| `ts_app` | PHP 8.4-FPM application | — |
| `ts_db` | MySQL 8.0 | 3307 |
| `ts_redis` | Redis 7 | 6379 |

> MySQL is mapped to **3307** on the host (not 3306) to avoid conflicts with a locally installed MySQL. Inside Docker, containers still connect on port 3306.

### 4. Install dependencies and generate application key

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 5. Run migrations

```bash
docker compose exec app php artisan migrate
```

> Both `translation_service` and `translation_service_test` databases are created automatically on first MySQL startup via `docker/mysql/init.sql`. You do not need to create them manually.

### 6. Create a user

> **This step is required.** The database starts empty — no users exist until you run this. Without a user you cannot get a token and cannot call any protected endpoint.

```bash
docker compose exec app php artisan tinker --execute="App\Models\User::factory()->create(['email'=>'admin@example.com','password'=>bcrypt('password')])"
```

### 7. Open the API documentation

Visit **http://localhost:8000** — it redirects to the interactive Swagger UI.

**To authenticate:**

1. Click **POST /api/v1/auth/token** → **Try it out**
2. Enter `admin@example.com` and `password` → **Execute**
3. Copy the `token` value from the response body
4. Click **Authorize** (lock icon, top right) → paste the token → **Authorize**
5. All protected endpoints are now unlocked and testable in the browser

---

## Environment Files

### `.env` — application environment

Copied from `.env.example`. Key values:

```dotenv
DB_HOST=db            # Docker service name, not localhost
DB_PORT=3306          # Internal Docker port
DB_DATABASE=translation_service
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis      # Docker service name
CACHE_STORE=redis
CACHE_PREFIX=ts_
```

### `.env.testing` — test environment

Already committed to the repository. Laravel loads this automatically when `APP_ENV=testing`. It points to the dedicated test database:

```dotenv
DB_DATABASE=translation_service_test
DB_USERNAME=laravel
DB_PASSWORD=secret
CACHE_PREFIX=ts_test_
BCRYPT_ROUNDS=4       # Faster password hashing in tests
```

> `CACHE_STORE` is overridden to `array` (in-memory) by `phpunit.xml`, so tests never touch Redis and cache state is always isolated between test runs.

---

## Running Tests

### Standard suite (unit + feature)

```bash
docker compose exec app ./vendor/bin/pest --exclude-group=performance
```

Expected output: **46 tests, all passing**, in under 10 seconds.

### Performance tests — must be run separately

```bash
docker compose exec app ./vendor/bin/pest --group=performance
```

Expected output: **5 tests, all passing**, in ~40 seconds total.

> **Why separately?** Each performance test seeds 100 000 translations in `beforeEach`. Running them alongside the standard suite would force every non-performance test to wait through a full seed cycle on every run. Keeping them in their own group means the standard suite stays fast.

### Full suite (all tests)

```bash
docker compose exec app ./vendor/bin/pest
```

---

## Test Configuration

### Why MySQL for tests (not SQLite)

The test suite runs against a real MySQL 8 database (`translation_service_test`). SQLite was ruled out because it does not support `FULLTEXT` indexes or the `whereFullText()` query used in content search. Tests would pass on SQLite and silently break in production. Running the same engine in both environments eliminates that entire class of divergence.

### How test isolation works

`phpunit.xml` sets these overrides at runtime:

| Setting | Value | Why |
|---|---|---|
| `DB_DATABASE` | `translation_service_test` | Dedicated database, never touches app data |
| `CACHE_STORE` | `array` | In-memory cache — fast, isolated, no Redis side-effects |
| `BCRYPT_ROUNDS` | `4` | Faster password hashing in factory-created users |
| `QUEUE_CONNECTION` | `sync` | Jobs run immediately, no queue worker needed |

`RefreshDatabase` wraps each test in a transaction that is rolled back on teardown, so tests never interfere with each other.

---

## Seed 100 000 Translations

```bash
docker compose exec app php artisan translations:seed

# Custom count:
docker compose exec app php artisan translations:seed --count=50000
```

The seeder inserts in batches of 1 000 using raw `DB::table()->insert()` to bypass Eloquent overhead, seeding 100 000 records in seconds.

---

## Connect to MySQL with TablePlus / DBeaver

| Field | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `3307` |
| User | `laravel` |
| Password | `secret` |
| Database | `translation_service` |

---

## API Endpoints

All routes except `POST /api/v1/auth/token` require `Authorization: Bearer <token>`.

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/auth/token` | No | Issue Sanctum token |
| GET | `/api/v1/translations` | Yes | Paginated list with filters |
| POST | `/api/v1/translations` | Yes | Create translation |
| GET | `/api/v1/translations/{id}` | Yes | Get single translation |
| PUT | `/api/v1/translations/{id}` | Yes | Update translation |
| DELETE | `/api/v1/translations/{id}` | Yes | Delete translation |
| GET | `/api/v1/export/{locale}` | Yes | Export key→value map (Redis cached) |
| GET | `/api/v1/tags` | Yes | List all tags |
| POST | `/api/v1/tags` | Yes | Create tag |

### Search filters — GET /api/v1/translations

| Param | Behaviour |
|---|---|
| `key` | Partial match (`LIKE %value%`) |
| `locale` | Exact match |
| `content` | MySQL FULLTEXT for ≥ 3 chars, LIKE fallback for shorter terms |
| `tag` | Filters by tag name |
| `per_page` | 1–100, default 15 |
| `page` | Page number, default 1 |

---

## Design Choices

### Service-Repository Pattern

Business logic lives in `TranslationService`, data access in `EloquentTranslationRepository`, bound via `TranslationRepositoryInterface` in `AppServiceProvider`. This means:
- Unit tests mock the interface — no database required
- The repository implementation can be swapped (e.g. to Elasticsearch) without touching the service
- The service has no knowledge of Eloquent

### Data Transfer Objects (DTOs)

`CreateTranslationDTO` and `UpdateTranslationDTO` are PHP 8.4 `readonly` value objects constructed from validated request data. They make method signatures explicit and enforce that the service layer only ever receives valid, typed data.

### API Resources

`TranslationResource` and `TagResource` use `whenLoaded()` to prevent N+1 queries. The response shape is decoupled from the Eloquent model — any internal rename never breaks the API contract.

### Redis Export Cache

`GET /api/v1/export/{locale}` is the hot path (CDN miss, mobile app boot). The result is cached per locale with a 1-hour TTL. Any write operation (create, update, delete) that affects that locale immediately invalidates the cache. The service uses an injected `CacheRepository` rather than the `Cache` facade so the caching behaviour is fully unit-testable with Mockery.

### CDN Support

The export endpoint is designed to be placed behind a CDN (CloudFront, Fastly, Cloudflare, etc.):

- **Flat JSON response** — `key → value` map with no pagination envelope; CDN serves the whole file in one request, exactly the shape frontend frameworks expect.
- **`Cache-Control: public, max-age=3600`** — tells the CDN edge nodes they are allowed to cache and serve the response for up to 1 hour without hitting the origin.
- **Redis as origin cache** — on a CDN cache miss the request reaches the origin, which serves from Redis in milliseconds rather than querying MySQL.
- **Cache invalidation** — any create, update, or delete immediately purges the Redis key so the next CDN miss fetches fresh data.

### MySQL FULLTEXT + Indexes

The `translations` table has:
- `FULLTEXT` index on `content` — MySQL native full-text search for terms ≥ 3 characters
- `UNIQUE (key, locale)` — enforced at the DB level, not just in validation
- Individual indexes on `key` and `locale` — used by the filter queries in `paginate()`

### Security

- **Sanctum token auth** — stateless Bearer tokens, no session cookies exposed
- **FormRequest validation** — all input validated and typed before reaching the service
- **DB-level uniqueness** — `UNIQUE (key, locale)` constraint, not just application-level validation
- **Explicit `$fillable`** — no mass-assignment vulnerabilities on any model

### Testing Strategy

| Suite | Tests | What it covers |
|---|---|---|
| Unit | 8 | `TranslationService` with mocked repository and cache |
| Feature | 38 | Full HTTP stack — auth, CRUD, all search filters, export, tags |
| Performance | 5 | 100 000-row dataset — cold/warm export cache, search by locale/key/tag |

---

## Production Considerations

**Laravel Octane with FrankenPHP** would be the natural next step — eliminating per-request framework bootstrap overhead and significantly increasing throughput. Omitted here to keep the Docker setup clean and the scope focused on correctness over raw throughput optimisation.
