<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TranslationRepositoryInterface;
use App\DTOs\CreateTranslationDTO;
use App\DTOs\UpdateTranslationDTO;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentTranslationRepository implements TranslationRepositoryInterface
{
    public function create(CreateTranslationDTO $dto): Translation
    {
        $translation = Translation::create([
            'key'     => $dto->key,
            'locale'  => $dto->locale,
            'content' => $dto->content,
        ]);

        if (!empty($dto->tags)) {
            $translation->tags()->sync($this->resolveTagIds($dto->tags));
        }

        return $translation->load('tags');
    }

    public function update(Translation $translation, UpdateTranslationDTO $dto): Translation
    {
        $attributes = array_filter([
            'key'     => $dto->key,
            'locale'  => $dto->locale,
            'content' => $dto->content,
        ], fn($value) => $value !== null);

        if (!empty($attributes)) {
            $translation->update($attributes);
        }

        if ($dto->tags !== null) {
            $translation->tags()->sync($this->resolveTagIds($dto->tags));
        }

        return $translation->load('tags');
    }

    public function delete(Translation $translation): bool
    {
        return (bool) $translation->delete();
    }

    public function findById(int $id): ?Translation
    {
        return Translation::with('tags')->find($id);
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Translation::with('tags')
            ->select(['id', 'key', 'locale', 'content', 'created_at', 'updated_at']);

        if (!empty($filters['key'])) {
            $query->where('key', 'like', '%' . $filters['key'] . '%');
        }

        if (!empty($filters['locale'])) {
            $query->where('locale', $filters['locale']);
        }

        if (!empty($filters['content'])) {
            $term = $filters['content'];
            if (mb_strlen($term) >= 3) {
                $query->whereFullText('content', $term);
            } else {
                $query->where('content', 'like', '%' . $term . '%');
            }
        }

        if (!empty($filters['tag'])) {
            $query->whereHas(
                'tags',
                fn($q) => $q->where('name', $filters['tag'])
            );
        }

        return $query->paginate($perPage);
    }

    public function getForExport(string $locale): array
    {
        return DB::table('translations')
            ->select(['key', 'content'])
            ->where('locale', $locale)
            ->orderBy('key')
            ->get()
            ->pluck('content', 'key')
            ->toArray();
    }

    private function resolveTagIds(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn(string $name) => Tag::firstOrCreate(
                ['name' => strtolower(trim($name))]
            )->id)
            ->toArray();
    }
}
