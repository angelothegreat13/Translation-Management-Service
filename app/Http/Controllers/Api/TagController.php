<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::select(['id', 'name'])->orderBy('name')->get();

        return TagResource::collection($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name'],
        ]);

        $tag = Tag::create(['name' => strtolower(trim((string) $request->input('name')))]);

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201);
    }
}
