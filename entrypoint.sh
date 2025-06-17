#!/bin/bash
set -e

echo "Menunggu database..."
until nc -z db 3306; do
  sleep 1
done
echo "Database tersedia!"

php artisan migrate --force
php artisan queue:work --tries=3 --sleep=3 --timeout=30 &

exec apache2-foreground