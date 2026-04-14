<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly TranslationService $service,
    ) {}

    public function export(string $locale): JsonResponse
    {
        $translations = $this->service->export($locale);

        return response()->json($translations)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
