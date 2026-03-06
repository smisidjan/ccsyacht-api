#!/bin/bash

# Deployment script for CCS Yacht API
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment to production server..."

SERVER_IP="167.235.135.241"
SERVER_USER="deploy"
PROJECT_DIR="/home/deploy/ccsyacht"
SSH_KEY="~/.ssh/css_key"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📦 Creating deployment archive...${NC}"
tar -czf ccsyacht-deploy.tar.gz \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/app/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    --exclude='.env' \
    --exclude='*.log' \
    --exclude='ccsyacht-deploy.tar.gz' \
    .

echo -e "${YELLOW}📤 Uploading to server...${NC}"
scp -i $SSH_KEY ccsyacht-deploy.tar.gz $SERVER_USER@$SERVER_IP:/tmp/

echo -e "${YELLOW}🔧 Deploying on server...${NC}"
ssh -i $SSH_KEY $SERVER_USER@$SERVER_IP << 'EOF'
set -e

# Create project directory if it doesn't exist
sudo mkdir -p /home/deploy/ccsyacht
sudo chown -R deploy:deploy /home/deploy/ccsyacht
cd /home/deploy

# Backup existing deployment if it exists
if [ -d "ccsyacht" ] && [ "$(ls -A ccsyacht)" ]; then
    echo "Backing up existing deployment..."
    sudo tar -czf ccsyacht-backup-$(date +%Y%m%d-%H%M%S).tar.gz ccsyacht/
fi

# Extract new deployment
echo "Extracting new deployment..."
cd ccsyacht
tar -xzf /tmp/ccsyacht-deploy.tar.gz
rm /tmp/ccsyacht-deploy.tar.gz

# Copy production environment file
if [ ! -f .env ]; then
    cp .env.production .env
    echo "Environment file created. Generating keys..."
fi

# Create necessary directories
mkdir -p storage/app/public
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p /var/backups/ccsyacht

# Set permissions
chmod -R 775 storage bootstrap/cache
sudo chown -R deploy:www-data storage bootstrap/cache

echo "✅ Deployment files in place!"
EOF

echo -e "${GREEN}✅ Files deployed successfully!${NC}"
echo -e "${YELLOW}📝 Next steps:${NC}"
echo "1. SSH into the server: ssh -i $SSH_KEY $SERVER_USER@$SERVER_IP"
echo "2. Navigate to project: cd $PROJECT_DIR"
echo "3. Generate keys: docker compose -f docker-compose.production.yml exec app php artisan key:generate"
echo "4. Run migrations: docker compose -f docker-compose.production.yml exec app php artisan migrate --seed"
echo "5. Start services: docker compose -f docker-compose.production.yml up -d"

# Clean up local archive
rm ccsyacht-deploy.tar.gz