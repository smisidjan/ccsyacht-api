#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Clear ALL caches first (including bootstrap cache)
echo "🧹 Clearing all caches..."
rm -f bootstrap/cache/*.php
docker compose exec -T app php artisan optimize:clear

# Run migrations
echo "🗄️ Running migrations..."
docker compose exec -T app php artisan migrate --force

# Recreate app container to reload environment
echo "🔄 Recreating app container..."
docker compose stop app
docker compose rm -f app
docker compose up -d app

# Wait for container to be ready
sleep 5

# Optimize application (creates new caches with fresh environment)
echo "⚡ Optimizing application..."
docker compose exec -T app php artisan optimize

# Ensure CSRF/session configuration is correct
echo "🔐 Ensuring session configuration..."
docker compose exec -T app php artisan config:cache

# Final restart to ensure clean state
echo "♻️ Final restart for clean state..."
docker compose restart app

# Wait for container to be ready
sleep 3

echo "✅ Deployment complete!"