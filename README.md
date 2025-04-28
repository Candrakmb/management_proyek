A Laravel Filament 3 application for managing projects with ticket management and status tracking.

## Requirements

- PHP > 8.2+
- Laravel 12
- MySQL 8.0+ / PostgreSQL 12+
- Composer

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/Candrakmb/management_proyek.git
   cd management_proyek
   ```

2. Install dependencies:
   ```
   composer install
   npm install
   ```

3. Set up environment:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=management_proyek
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Run migrations:
   ```
   php artisan migrate
   ```

6. Create storage link for file uploads
   ```
   php artisan storage:link
   ```

7. Create a Filament admin user:
   ```
   php artisan make:filament-user
   ```
8. Activate Role & Permission
   ```
   php artisan shield:setup
   php artisan shield:install
   php artisan shield:super-admin
   ```

9. Start the development server:
   ```
   php artisan serve
   ```
