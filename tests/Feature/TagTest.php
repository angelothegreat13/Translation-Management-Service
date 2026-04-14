<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a list of tags', function () {
    Tag::factory()->create(['name' => 'web']);
    Tag::factory()->create(['name' => 'mobile']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/tags');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name']]]);

    expect($response->json('data'))->toHaveCount(2);
});

it('returns tags in alphabetical order', function () {
    Tag::factory()->create(['name' => 'web']);
    Tag::factory()->create(['name' => 'mobile']);
    Tag::factory()->create(['name' => 'desktop']);

    $names = collect(
        $this->actingAs($this->user)->getJson('/api/v1/tags')->json('data')
    )->pluck('name')->toArray();

    expect($names)->toBe(['desktop', 'mobile', 'web']);
});

it('creates a tag', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tags', ['name' => 'API']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'api');

    $this->assertDatabaseHas('tags', ['name' => 'api']);
});

it('stores tag names in lowercase', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/tags', ['name' => 'MOBILE'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'mobile');
});

it('returns 422 for a duplicate tag name', function () {
    Tag::factory()->create(['name' => 'web']);

    $this->actingAs($this->user)
        ->postJson('/api/v1/tags', ['name' => 'web'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns 422 when tag name is missing', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/tags', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns 401 for unauthenticated tag requests', function () {
    $this->getJson('/api/v1/tags')->assertUnauthorized();
    $this->postJson('/api/v1/tags', ['name' => 'web'])->assertUnauthorized();
});
