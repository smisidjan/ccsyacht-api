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

# Clear specific caches (but keep session data)
echo "🧹 Clearing application caches..."
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear
docker compose exec -T app php artisan event:clear
# Note: We do NOT clear config:clear here to preserve session settings

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

# Ensure critical environment variables are set for session/CSRF
echo "🔐 Ensuring session and CSRF configuration..."
docker compose exec -T app sh -c 'php artisan tinker --execute="
echo \"SESSION_DRIVER: \" . env(\"SESSION_DRIVER\", \"redis\");
echo \"\nSESSION_DOMAIN: \" . env(\"SESSION_DOMAIN\", \".ccsyacht.com\");
echo \"\nSANCTUM_STATEFUL_DOMAINS: \" . env(\"SANCTUM_STATEFUL_DOMAINS\");
"'

# Optimize application with all caches
echo "⚡ Optimizing application..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan event:cache

# Restart queue to pick up new config
echo "♻️ Restarting queue worker..."
docker compose restart queue

# Wait for container to be ready
sleep 3

echo "✅ Deployment complete!"