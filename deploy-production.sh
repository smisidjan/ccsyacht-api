#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Backup .env before pull
echo "💾 Backing up .env..."
cp .env .env.backup

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Restore .env to keep production settings
echo "♻️ Restoring production .env..."
cp .env.backup .env

# Clear ALL caches first (including bootstrap cache)
echo "🧹 Clearing all caches..."
rm -f bootstrap/cache/*.php
docker compose exec -T app php artisan optimize:clear

# Run migrations
echo "🗄️ Running migrations..."
docker compose exec -T app php artisan migrate --force

# Recreate app and queue containers to reload environment
echo "🔄 Recreating app and queue containers..."
docker compose stop app queue
docker compose rm -f app queue
docker compose up -d app queue

# Wait for containers to be ready
sleep 5

# Clear config cache first to ensure fresh mail config
echo "🧹 Clearing config cache for fresh mail settings..."
docker compose exec -T app php artisan config:clear

# Optimize application (but don't cache config yet)
echo "⚡ Optimizing application..."
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan event:cache

# Cache config last with correct environment
echo "🔐 Caching configuration with correct mail settings..."
docker compose exec -T app php artisan config:cache

# Restart queue to pick up new config
echo "♻️ Restarting queue worker..."
docker compose restart queue

# Wait for container to be ready
sleep 3

echo "✅ Deployment complete!"