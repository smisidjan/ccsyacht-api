#!/bin/bash
set -e

echo "🚀 Starting CCS Yacht application setup..."

# Clear any cached config first
echo "🧹 Clearing cached config..."
php artisan config:clear
php artisan cache:clear

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL..."
until pg_isready -h ${DB_HOST:-postgres} -U ${DB_USERNAME:-ccsyacht} -q; do
    sleep 1
done
echo "✅ PostgreSQL is ready"

# Wait for Redis to be ready
echo "⏳ Waiting for Redis..."
until redis-cli -h ${REDIS_HOST:-redis} ping > /dev/null 2>&1; do
    sleep 1
done
echo "✅ Redis is ready"

# Debug: show database config
echo "📋 Database config:"
echo "   Host: ${DB_HOST:-postgres}"
echo "   Database: ${DB_DATABASE:-ccsyacht}"
echo "   Username: ${DB_USERNAME:-ccsyacht}"

# Run landlord (central) migrations
echo "🔄 Running landlord migrations..."
php artisan migrate --path=database/migrations/landlord --force

# Seed landlord database (system admin + default tenant)
echo "🌱 Checking if seeding is needed..."
if ! php artisan tinker --execute="echo App\Models\SystemAdmin::count();" 2>/dev/null | grep -q "^[1-9]"; then
    echo "🌱 Seeding landlord database..."
    php artisan db:seed --force
fi

# Clear and cache config (skip route:cache for Scramble compatibility)
echo "🔧 Optimizing application..."
php artisan config:cache
php artisan view:cache

echo "✅ Setup complete! Starting application..."

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
