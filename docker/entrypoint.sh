#!/bin/sh

set -e

echo "Starting Neo ERP application..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan db:show > /dev/null 2>&1; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is up - executing migrations"

# Run migrations
php artisan migrate --force --no-interaction

# Clear and cache config
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage directories exist with proper structure
echo "Setting up storage directories..."
mkdir -p /var/www/html/storage/app/public/logos
mkdir -p /var/www/html/storage/app/public/documents
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/logs

# Create storage symlink if needed
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Ensure public/storage symlink has correct permissions
if [ -L /var/www/html/public/storage ]; then
    chown -h www-data:www-data /var/www/html/public/storage
fi

echo "Application ready!"

# Execute the main command
exec "$@"
