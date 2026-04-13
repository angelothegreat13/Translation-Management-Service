<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    private static array $groups = [
        'auth', 'dashboard', 'navigation', 'buttons', 'messages', 'errors', 'notifications',
    ];

    private static array $locales = ['en', 'fr', 'es', 'de', 'it'];

    public function definition(): array
    {
        $group = fake()->randomElement(self::$groups);

        return [
            'key'     => $group . '.' . fake()->unique()->lexify('????????'),
            'locale'  => fake()->randomElement(self::$locales),
            'content' => fake()->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(['locale' => $locale]);
    }

    public function forKey(string $key): static
    {
        return $this->state(['key' => $key]);
    }
}
