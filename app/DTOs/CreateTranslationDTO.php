<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\StoreTranslationRequest;

final readonly class CreateTranslationDTO
{
    public function __construct(
        public string $key,
        public string $locale,
        public string $content,
        public array $tags = [],
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
