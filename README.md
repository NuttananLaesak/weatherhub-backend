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

# Seed sample data for login
php artisan db:seed --class=UserSeeder

# Run server
php artisan serve

# Run scheduler (ingestion) update weather auto
php artisan schedule:work

# Run test case
php artisan test
```
