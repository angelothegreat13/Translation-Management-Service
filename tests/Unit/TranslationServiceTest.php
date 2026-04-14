<?php

declare(strict_types=1);

use App\Contracts\TranslationRepositoryInterface;
use App\DTOs\CreateTranslationDTO;
use App\DTOs\UpdateTranslationDTO;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

beforeEach(function () {
    $this->repository = Mockery::mock(TranslationRepositoryInterface::class);
    $this->cache      = Mockery::mock(CacheRepository::class);
    $this->service    = new TranslationService($this->repository, $this->cache);
});

it('creates a translation and invalidates the locale cache', function () {
    $dto         = new CreateTranslationDTO('auth.login', 'en', 'Login', []);
    $translation = Translation::factory()->make(['locale' => 'en']);

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with($dto)
        ->andReturn($translation);

    $this->cache
        ->shouldReceive('forget')
        ->once()
        ->with('translations:export:en');

    $result = $this->service->create($dto);

    expect($result)->toBe($translation);
});

it('updates a translation and invalidates the old locale cache', function () {
    $translation = Translation::factory()->make(['locale' => 'en']);
    $dto         = new UpdateTranslationDTO(content: 'Updated');

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->with($translation, $dto)
        ->andReturn($translation);

    $this->cache
        ->shouldReceive('forget')
        ->once()
        ->with('translations:export:en');

    $result = $this->service->update($translation, $dto);

    expect($result)->toBe($translation);
});

it('invalidates both old and new locale caches when locale changes', function () {
    $translation = Translation::factory()->make(['locale' => 'en']);
    $dto         = new UpdateTranslationDTO(locale: 'fr');

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->andReturn($translation);

    $this->cache
        ->shouldReceive('forget')
        ->once()
        ->with('translations:export:en');

    $this->cache
        ->shouldReceive('forget')
        ->once()
        ->with('translations:export:fr');

    $this->service->update($translation, $dto);
});

it('deletes a translation and invalidates the locale cache', function () {
    $translation = Translation::factory()->make(['locale' => 'en']);

    $this->repository
        ->shouldReceive('delete')
        ->once()
        ->with($translation)
        ->andReturn(true);

    $this->cache
        ->shouldReceive('forget')
        ->once()
        ->with('translations:export:en');

    $result = $this->service->delete($translation);

    expect($result)->toBeTrue();
});

it('finds a translation by id', function () {
    $translation = Translation::factory()->make();

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($translation);

    $result = $this->service->findById(1);

    expect($result)->toBe($translation);
});

it('returns null when translation not found', function () {
    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturn(null);

    expect($this->service->findById(999))->toBeNull();
});

it('returns cached export on first call', function () {
    $data = ['auth.login' => 'Login', 'auth.logout' => 'Logout'];

    $this->cache
        ->shouldReceive('remember')
        ->once()
        ->with('translations:export:en', 3600, Mockery::type('Closure'))
        ->andReturn($data);

    $result = $this->service->export('en');

    expect($result)->toBe($data);
});

it('calls the repository inside the cache callback', function () {
    $data = ['auth.login' => 'Login'];

    $this->repository
        ->shouldReceive('getForExport')
        ->once()
        ->with('en')
        ->andReturn($data);

    $this->cache
        ->shouldReceive('remember')
        ->once()
        ->andReturnUsing(function (string $key, int $ttl, Closure $callback) {
            return $callback();
        });

    $result = $this->service->export('en');

    expect($result)->toBe($data);
});
