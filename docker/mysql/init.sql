CREATE DATABASE IF NOT EXISTS translation_service;
CREATE DATABASE IF NOT EXISTS translation_service_test;

GRANT ALL PRIVILEGES ON translation_service.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON translation_service_test.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
