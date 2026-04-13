<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\CreateTranslationDTO;
use App\DTOs\UpdateTranslationDTO;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TranslationRepositoryInterface
{
    public function create(CreateTranslationDTO $dto): Translation;

    public function update(Translation $translation, UpdateTranslationDTO $dto): Translation;

    public function delete(Translation $translation): bool;

    public function findById(int $id): ?Translation;

    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function getForExport(string $locale): array;
}
