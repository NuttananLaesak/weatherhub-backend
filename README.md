# WeatherHub Backend (Laravel)

## Installation / Setup

```shell
# Clone repository
git clone https://github.com/NuttananLaesak/weatherhub-backend
cd weatherhub-backend

# Install dependencies
composer install

# Copy .env
Copy-Item .env.example -Destination .env

# Generate application key
php artisan key:generate

# Migrate database
php artisan migrate

# Run test case
php artisan test

# Seed User for Login and Location ChiangMai
php artisan db:seed

# Run server
php artisan serve

# Run scheduler (ingestion) update weather auto
php artisan schedule:work

# Example
INSERT INTO locations (name, lat, lon, timezone, created_at, updated_at)
VALUES ('Chiang Mai', 18.7883, 98.9853, 'Asia/Bangkok', NOW(), NOW());

php artisan ingest:weather --backfill=2025-10-18,2025-10-21
```
