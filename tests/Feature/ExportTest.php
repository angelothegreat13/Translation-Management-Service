<?php

declare(strict_types=1);

use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a flat key-value json for the given locale', function () {
    Translation::factory()->create(['key' => 'auth.login',  'locale' => 'en', 'content' => 'Login']);
    Translation::factory()->create(['key' => 'auth.logout', 'locale' => 'en', 'content' => 'Logout']);
    Translation::factory()->create(['key' => 'auth.login',  'locale' => 'fr', 'content' => 'Connexion']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/export/en');

    $response->assertOk()->assertJson([
        'auth.login'  => 'Login',
        'auth.logout' => 'Logout',
    ]);

    expect(array_keys($response->json()))->toHaveCount(2);
});

it('returns an empty object for a locale with no translations', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/export/ja')
        ->assertOk()
        ->assertExactJson([]);
});

it('caches the export result', function () {
    Translation::factory()->create(['key' => 'auth.login', 'locale' => 'en', 'content' => 'Login']);

    $this->actingAs($this->user)->getJson('/api/v1/export/en')->assertOk();

    expect(Cache::has('translations:export:en'))->toBeTrue();

    $cached = Cache::get('translations:export:en');

    expect($cached)
        ->toBeArray()
        ->toHaveKey('auth.login')
        ->and($cached['auth.login'])->toBe('Login');
});

it('invalidates cache when a translation is created', function () {
    Translation::factory()->create(['key' => 'nav.home', 'locale' => 'en', 'content' => 'Home']);

    $this->actingAs($this->user)->getJson('/api/v1/export/en')->assertOk();

    $this->actingAs($this->user)->postJson('/api/v1/translations', [
        'key'     => 'nav.about',
        'locale'  => 'en',
        'content' => 'About',
    ])->assertCreated();

    $response = $this->actingAs($this->user)->getJson('/api/v1/export/en');

    $response->assertOk();
    expect($response->json())->toHaveKey('nav.about');
});

it('invalidates cache when a translation is updated', function () {
    $translation = Translation::factory()->create([
        'key'     => 'auth.login',
        'locale'  => 'en',
        'content' => 'Login',
    ]);

    $this->actingAs($this->user)->getJson('/api/v1/export/en')->assertOk();
    expect(Cache::has('translations:export:en'))->toBeTrue();

    $this->actingAs($this->user)
        ->putJson("/api/v1/translations/{$translation->id}", ['content' => 'Sign In'])
        ->assertOk();

    expect(Cache::has('translations:export:en'))->toBeFalse();

    $this->assertDatabaseHas('translations', [
        'id'      => $translation->id,
        'content' => 'Sign In',
    ]);
});

it('invalidates cache when a translation is deleted', function () {
    $t1 = Translation::factory()->create(['key' => 'auth.login',  'locale' => 'en', 'content' => 'Login']);
    $t2 = Translation::factory()->create(['key' => 'auth.logout', 'locale' => 'en', 'content' => 'Logout']);

    $this->actingAs($this->user)->getJson('/api/v1/export/en')->assertOk();

    $this->actingAs($this->user)
        ->deleteJson("/api/v1/translations/{$t1->id}")
        ->assertNoContent();

    $response = $this->actingAs($this->user)->getJson('/api/v1/export/en');

    expect($response->json())->not->toHaveKey('auth.login')
        ->and($response->json())->toHaveKey('auth.logout');
});
