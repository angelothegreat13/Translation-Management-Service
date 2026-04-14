<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\TranslationRepositoryInterface;
use App\Repositories\EloquentTranslationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TranslationRepositoryInterface::class,
            EloquentTranslationRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
