<div align="center">

# WareTrack

**A Warehouse Management System built with Laravel 13 + Livewire 4**

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)

</div>

---

## About

WareTrack is a full-featured Warehouse Management System developed as an eindwerk (final project) for the 2025–2026 academic year. It allows businesses to manage warehouses, products, stock levels, incoming deliveries, and stock movements across multiple locations — with full audit logging and role-based access control.

**Key features:**
- Multi-warehouse and multi-location stock management
- Incoming delivery tracking (supplier → products → stock)
- Full stock movement audit log (incoming, outgoing, transfer, correction)
- Minimum stock warnings and low-stock overview
- Role-based access: Admin and Warehouse Worker
- Dashboard with stock summary and recent activity
- Reporting and stock history

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Frontend | Livewire 4, Flux UI, Tailwind CSS, GSAP |
| Database | MySQL 8.0 |
| Auth | Laravel Fortify (2FA support) |
| Testing | Pest 4 |
| Audit log | spatie/laravel-activitylog |

---

## Requirements

- PHP 8.4+
- Composer
- Node.js + npm
- MySQL 8.0+
- [Laravel Herd](https://herd.laravel.com) (recommended) or another local server

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/Yaman69420/WareTrack.git
cd WareTrack

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=waretrack
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 5. Create the database (in MySQL)
# CREATE DATABASE waretrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 6. Run migrations and seed demo data
php artisan migrate --seed

# 7. Create storage symlink
php artisan storage:link

# 8. Build assets (or run dev server)
npm run build
# or for development:
npm run dev
```

The application is now available at **http://waretrack.test** (Herd) or your configured `APP_URL`.

---

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@waretrack.test | password |
| Warehouse Worker | worker@waretrack.test | password |

---

## Project Structure

```
app/
├── Actions/Fortify/  # Auth actions (CreateNewUser, ResetUserPassword)
├── Enums/            # UserRole, StockMovementType, DeliveryStatus
├── Exceptions/       # InsufficientStockException
├── Http/Middleware/  # EnsureUserIsAdmin
├── Livewire/         # Full-page Livewire components per domain
├── Models/           # Eloquent models with relationships
├── Policies/         # Authorization policies per model
└── Services/         # StockService, WarehouseService, ReportService
```

---

## Running Tests

```bash
./vendor/bin/pest
```

---

## License

This project was developed as an academic eindwerk and is not licensed for commercial use.
