.PHONY: up down build shell migrate fresh seed test cache logs redis-flush ps install key

# Start all containers
up:
	docker compose up -d

# Stop all containers
down:
	docker compose down

# Build/rebuild containers
build:
	docker compose build --no-cache

# Open shell in app container
shell:
	docker compose exec app bash

# Run migrations
migrate:
	docker compose exec app php artisan migrate

# Fresh migration + seed
fresh:
	docker compose exec app php artisan migrate:fresh --seed

# Run the translation seeder
seed:
	docker compose exec app php artisan translations:seed --count=100000

# Run tests
test:
	docker compose exec app php artisan test

# Run tests with coverage
test-coverage:
	docker compose exec app php artisan test --coverage

# Clear and rebuild all caches
cache:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache

# View logs
logs:
	docker compose logs -f

# Flush Redis
redis-flush:
	docker compose exec redis redis-cli FLUSHALL

# Show running containers
ps:
	docker compose ps

# Install dependencies
install:
	docker compose exec app composer install

# Generate app key
key:
	docker compose exec app php artisan key:generate

# First-time setup
setup: up
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate
	@echo ""
	@echo "Setup complete! App running at http://localhost:8000"
