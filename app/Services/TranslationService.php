<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TranslationRepositoryInterface;
use App\DTOs\CreateTranslationDTO;
use App\DTOs\UpdateTranslationDTO;
use App\Models\Translation;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TranslationService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const CACHE_KEY_PREFIX  = 'translations:export:';

    public function __construct(
        private readonly TranslationRepositoryInterface $repository,
        private readonly CacheRepository $cache,
    ) {}

    public function create(CreateTranslationDTO $dto): Translation
    {
        $translation = $this->repository->create($dto);
        $this->invalidateCache($dto->locale);

        return $translation;
    }

    public function update(Translation $translation, UpdateTranslationDTO $dto): Translation
    {
        $oldLocale = $translation->locale;
        $updated   = $this->repository->update($translation, $dto);

        $this->invalidateCache($oldLocale);

        if ($dto->locale !== null && $dto->locale !== $oldLocale) {
            $this->invalidateCache($dto->locale);
        }

        return $updated;
    }

    public function delete(Translation $translation): bool
    {
        $locale = $translation->locale;
        $result = $this->repository->delete($translation);
        $this->invalidateCache($locale);

        return $result;
    }

    public function findById(int $id): ?Translation
    {
        return $this->repository->findById($id);
    }

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function export(string $locale): array
    {
        return $this->cache->remember(
            self::CACHE_KEY_PREFIX . $locale,
            self::CACHE_TTL_SECONDS,
            fn() => $this->repository->getForExport($locale)
        );
    }

    private function invalidateCache(string $locale): void
    {
        $this->cache->forget(self::CACHE_KEY_PREFIX . $locale);
    }
}
