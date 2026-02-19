.PHONY: help build up down restart logs shell migrate seed fresh test swagger

# Default target
help:
	@echo "CCS Yacht - Available commands:"
	@echo ""
	@echo "  make build      - Build Docker containers"
	@echo "  make up         - Start all containers"
	@echo "  make down       - Stop all containers"
	@echo "  make restart    - Restart all containers"
	@echo "  make logs       - View container logs"
	@echo "  make shell      - Open shell in app container"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Seed the database"
	@echo "  make fresh      - Fresh migration with seed"
	@echo "  make test       - Run tests"
	@echo "  make swagger    - Generate Swagger documentation"
	@echo "  make tinker     - Open Laravel Tinker"
	@echo "  make composer   - Run composer commands (use: make composer cmd='install')"
	@echo "  make artisan    - Run artisan commands (use: make artisan cmd='migrate')"

# Build containers
build:
	docker-compose build

# Start containers
up:
	docker-compose up -d
	@echo ""
	@echo "🚀 CCS Yacht is running!"
	@echo ""
	@echo "  API:      http://localhost:8000"
	@echo "  Swagger:  http://localhost:8000/api/documentation"
	@echo "  Mailpit:  http://localhost:8025"
	@echo ""

# Stop containers
down:
	docker-compose down

# Restart containers
restart:
	docker-compose restart

# View logs
logs:
	docker-compose logs -f

# View app logs only
logs-app:
	docker-compose logs -f app

# Open shell in app container
shell:
	docker-compose exec app bash

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# Seed database
seed:
	docker-compose exec app php artisan db:seed

# Fresh migration with seed
fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# Run tests
test:
	docker-compose exec app php artisan test

# Generate Swagger documentation
swagger:
	docker-compose exec app php artisan l5-swagger:generate

# Open Tinker
tinker:
	docker-compose exec app php artisan tinker

# Run composer commands
composer:
	docker-compose exec app composer $(cmd)

# Run artisan commands
artisan:
	docker-compose exec app php artisan $(cmd)

# Install dependencies (first time setup)
install:
	cp -n .env.example .env || true
	docker-compose build
	docker-compose up -d
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	@echo ""
	@echo "✅ Installation complete! Run 'make up' to start the application."

# Clean everything
clean:
	docker-compose down -v --remove-orphans
	@echo "✅ All containers and volumes removed."
