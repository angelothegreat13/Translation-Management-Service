<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\UpdateTranslationRequest;

final class UpdateTranslationDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $locale = null,
        public readonly ?string $content = null,
        public readonly ?array $tags = null,
    ) {}

    public static function fromRequest(UpdateTranslationRequest $request): self
    {
        return new self(
            key: $request->validated('key'),
            locale: $request->validated('locale'),
            content: $request->validated('content'),
            tags: $request->has('tags') ? $request->validated('tags') : null,
        );
    }
}
