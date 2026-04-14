<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\CreateTranslationDTO;
use App\DTOs\UpdateTranslationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['key', 'locale', 'content', 'tag']);
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $translations = $this->service->search($filters, $perPage);

        return TranslationResource::collection($translations);
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->service->create(
            CreateTranslationDTO::fromRequest($request)
        );

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Translation $translation): TranslationResource
    {
        $translation->loadMissing('tags');

        return new TranslationResource($translation);
    }

    public function update(
        UpdateTranslationRequest $request,
        Translation $translation
    ): TranslationResource {
        $updated = $this->service->update(
            $translation,
            UpdateTranslationDTO::fromRequest($request)
        );

        return new TranslationResource($updated);
    }

    public function destroy(Translation $translation): JsonResponse
    {
        $this->service->delete($translation);

        return response()->json(null, 204);
    }
}
