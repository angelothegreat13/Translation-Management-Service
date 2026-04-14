<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        putenv('DB_DATABASE=translation_service_test');

        $_ENV['DB_DATABASE']    = 'translation_service_test';
        $_SERVER['DB_DATABASE'] = 'translation_service_test';

        parent::setUp();

        $this->app['config']->set(
            'database.connections.mysql.database',
            'translation_service_test'
        );

        DB::purge('mysql');
    }
}
