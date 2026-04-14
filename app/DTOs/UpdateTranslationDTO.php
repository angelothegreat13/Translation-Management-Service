<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\UpdateTranslationRequest;

final readonly class UpdateTranslationDTO
{
    public function __construct(
        public ?string $key = null,
        public ?string $locale = null,
        public ?string $content = null,
        public ?array $tags = null,
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
