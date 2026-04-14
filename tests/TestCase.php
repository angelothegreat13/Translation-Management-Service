<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Lightweight backup to tests/bootstrap.php.
        // bootstrap.php already set these before Laravel booted so the
        // connection was established against translation_service_test from
        // the start — no reconnection needed here.
        putenv('DB_DATABASE=translation_service_test');
        $_ENV['DB_DATABASE']    = 'translation_service_test';
        $_SERVER['DB_DATABASE'] = 'translation_service_test';

        parent::setUp();
    }
}
