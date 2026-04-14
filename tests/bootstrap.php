<?php

declare(strict_types=1);

/**
 * Force test environment variables before PHPUnit or Laravel bootstrap.
 *
 * docker-compose injects DB_DATABASE=translation_service into the container
 * as a real OS process variable. PHP populates $_ENV from it at startup,
 * so phpunit.xml <env> tags alone cannot reliably override it. Setting the
 * values here — via putenv() AND directly in $_ENV/$_SERVER — guarantees
 * the test database is used regardless of container environment variables.
 */
putenv('APP_ENV=testing');
putenv('DB_DATABASE=translation_service_test');
putenv('CACHE_STORE=array');

$_ENV['APP_ENV']    = 'testing';
$_ENV['DB_DATABASE'] = 'translation_service_test';
$_ENV['CACHE_STORE'] = 'array';

$_SERVER['APP_ENV']    = 'testing';
$_SERVER['DB_DATABASE'] = 'translation_service_test';
$_SERVER['CACHE_STORE'] = 'array';

require __DIR__ . '/../vendor/autoload.php';
