<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\StoreTranslationRequest;

final class CreateTranslationDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $locale,
        public readonly string $content,
        public readonly array $tags = [],
    ) {}

    public static function fromRequest(StoreTranslationRequest $request): self
    {
        return new self(
            key: $request->validated('key'),
            locale: $request->validated('locale'),
            content: $request->validated('content'),
            tags: $request->validated('tags', []),
        );
    }
}
