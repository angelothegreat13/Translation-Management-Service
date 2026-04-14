<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->group('performance');

beforeEach(function () {
    $this->user = User::factory()->create();

    Artisan::call('translations:seed', ['--count' => 100000]);
});

it('exports translations for a locale in under 500ms (cold cache)', function () {
    Cache::flush();

    $start = hrtime(true);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/export/en');

    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $response->assertOk();
    expect($durationMs)->toBeLessThan(500);
})->timeout(30);

it('exports translations for a locale in under 50ms (warm cache)', function () {
    $this->actingAs($this->user)->getJson('/api/v1/export/en');

    $start = hrtime(true);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/export/en');

    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $response->assertOk();
    expect($durationMs)->toBeLessThan(50);
})->timeout(10);

it('searches translations by locale in under 200ms', function () {
    $start = hrtime(true);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?locale=en&per_page=15');

    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $response->assertOk();
    expect($durationMs)->toBeLessThan(200);
})->timeout(10);

it('searches translations by key in under 200ms', function () {
    $start = hrtime(true);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?key=auth&per_page=15');

    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $response->assertOk();
    expect($durationMs)->toBeLessThan(200);
})->timeout(10);

it('searches translations by tag in under 200ms', function () {
    $start = hrtime(true);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/translations?tag=web&per_page=15');

    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $response->assertOk();
    expect($durationMs)->toBeLessThan(200);
})->timeout(10);
