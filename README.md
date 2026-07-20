# Backend Intern Case — Laravel 12 REST API

E-commerce REST API sederhana menggunakan Laravel 12 + Sanctum.

## Requirements
- PHP 8.3+
- Composer
- MySQL (XAMPP)

## Instalasi

1. Clone repository
   ```bash
   git clone https://github.com/Raeaw/backend-intern-case.git
   cd backend-intern-case
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Copy environment file
   ```bash
   cp .env.example .env
   ```

4. Generate app key
   ```bash
   php artisan key:generate
   ```

5. Buat database `backend_intern_case` di phpMyAdmin, sesuaikan `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=backend_intern_case
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Migrate & seed database
   ```bash
   php artisan migrate:fresh --seed
   ```

7. Jalankan server
   ```bash
   php artisan serve
   ```

## Akun default (dari seeder)
| Role  | Email             | Password |
|-------|-------------------|----------|
| Admin | admin@example.com | password |
| User  | user@example.com  | password |

## Endpoint

### Auth
- POST /api/login
- POST /api/logout (auth)

### Products
- GET /api/products (public)
- GET /api/products/{id} (public)
- POST /api/products (admin)
- PUT /api/products/{id} (admin)
- DELETE /api/products/{id} (admin)

### Orders
- POST /api/orders (public)
- GET /api/orders (admin)
- GET /api/orders/{id} (admin)

## Testing

```bash
php artisan test
```

## Postman Collection
Tersedia di file `postman-collection/postman_collection.json` di root repository.
