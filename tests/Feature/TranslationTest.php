<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a paginated list of translations', function () {
    Translation::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/api/v1/translations')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'key', 'locale', 'content', 'tags', 'created_at', 'updated_at']],
            'links',
            'meta',
        ]);
});

it('searches translations by key', function () {
    Translation::factory()->create(['key' => 'auth.login', 'locale' => 'en']);
    Translation::factory()->create(['key' => 'dashboard.title', 'locale' => 'en']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?key=auth');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['key'])->toBe('auth.login');
});

it('searches translations by locale', function () {
    Translation::factory()->locale('en')->count(2)->create();
    Translation::factory()->locale('fr')->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?locale=fr');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('searches translations by tag', function () {
    $tag   = Tag::factory()->create(['name' => 'mobile']);
    $match = Translation::factory()->create();
    $match->tags()->attach($tag);

    Translation::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?tag=mobile');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

it('searches translations by content', function () {
    Translation::factory()->create(['content' => 'Welcome to the application', 'locale' => 'en']);
    Translation::factory()->create(['content' => 'Click save to continue', 'locale' => 'en']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?content=We');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.content'))->toBe('Welcome to the application');
});

it('respects per_page parameter with a max of 100', function () {
    Translation::factory()->count(5)->create();

    $this->actingAs($this->user)
        ->getJson('/api/v1/translations?per_page=2')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 2);

    $this->actingAs($this->user)
        ->getJson('/api/v1/translations?per_page=200')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('creates a translation', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/translations', [
            'key'     => 'auth.login',
            'locale'  => 'en',
            'content' => 'Login',
            'tags'    => ['web', 'mobile'],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.key', 'auth.login')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.content', 'Login');

    expect($response->json('data.tags'))->toHaveCount(2);

    $this->assertDatabaseHas('translations', [
        'key'    => 'auth.login',
        'locale' => 'en',
    ]);
});

it('returns 422 when creating a duplicate key+locale', function () {
    Translation::factory()->create(['key' => 'auth.login', 'locale' => 'en']);

    $this->actingAs($this->user)
        ->postJson('/api/v1/translations', [
            'key'     => 'auth.login',
            'locale'  => 'en',
            'content' => 'Login again',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['key']);
});

it('returns 422 when required fields are missing', function () {
    $this->actingAs($this->user)
        ->postJson('/api/v1/translations', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['key', 'locale', 'content']);
});

it('creates a translation without tags', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/translations', [
            'key'     => 'auth.register',
            'locale'  => 'en',
            'content' => 'Register',
        ]);

    $response->assertCreated();
    expect($response->json('data.tags'))->toBeEmpty();
});

it('shows a single translation', function () {
    $translation = Translation::factory()->create();

    $this->actingAs($this->user)
        ->getJson("/api/v1/translations/{$translation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $translation->id)
        ->assertJsonPath('data.key', $translation->key);
});

it('returns 404 for a non-existent translation', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/translations/99999')
        ->assertNotFound();
});

it('updates a translation content', function () {
    $translation = Translation::factory()->create(['content' => 'Old content']);

    $this->actingAs($this->user)
        ->putJson("/api/v1/translations/{$translation->id}", ['content' => 'New content'])
        ->assertOk()
        ->assertJsonPath('data.content', 'New content');

    $this->assertDatabaseHas('translations', [
        'id'      => $translation->id,
        'content' => 'New content',
    ]);
});

it('updates translation tags', function () {
    $tag         = Tag::factory()->create(['name' => 'web']);
    $translation = Translation::factory()->create();
    $translation->tags()->attach($tag);

    $this->actingAs($this->user)
        ->putJson("/api/v1/translations/{$translation->id}", ['tags' => ['mobile']])
        ->assertOk();

    expect($translation->fresh()->tags->pluck('name')->toArray())
        ->toBe(['mobile']);
});

it('returns 404 when updating a non-existent translation', function () {
    $this->actingAs($this->user)
        ->putJson('/api/v1/translations/99999', ['content' => 'x'])
        ->assertNotFound();
});

it('deletes a translation', function () {
    $translation = Translation::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/v1/translations/{$translation->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
});

it('returns 404 when deleting a non-existent translation', function () {
    $this->actingAs($this->user)
        ->deleteJson('/api/v1/translations/99999')
        ->assertNotFound();
});
