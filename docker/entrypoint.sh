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

# Create storage symlink if needed
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "Application ready!"

# Execute the main command
exec "$@"
