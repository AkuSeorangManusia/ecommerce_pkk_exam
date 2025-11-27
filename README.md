# Ujian PKK Laravel-Filament

---

|  |  |
|-------|-------|
| Nama | Muhammad Ahsan Sanadi |
| Kelas | XII SIJA B |
| No. Presensi | 2 |

---

## Tech Stack yang Digunakan

| Komponen | Teknologi |
|-----------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Admin Panel | Filament 3 |
| Frontend | React, TypeScript, Inertia.js |
| Database | MariaDB / MySQL |
| Auth | Laravel Fortify, Filament Shield (RBAC) |

---

### Admin Dashboard
- Revenue, profit, and order statistics
- Low stock alerts
- Recent orders overview

---

### Catalog Management
- Products with images, specifications, and pricing
- Categories with nested subcategories
- Brand management

---

### Order Management
- Order processing with status tracking
- Payment status management
- Automatic stock decrement on order

---

### Access Control
- Role-based permissions with Filament Shield
- Super admin and custom roles

---

## Getting Started

1. Clone repo ini
```bash
git clone https://github.com/AkuSeorangManusia/ecommerce_pkk_exam
```

2. Instal dependensinya
```bash
composer install
npm install
```

3. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Setup database di `.env`, lalu jalankan migrasi
```bash
php artisan migrate
php artisan storage:link
```

5. Buat admin user
```bash
php artisan make:filament-user
php artisan shield:super-admin
```

6. Mulai server development
```bash
composer run dev
```

7. Admin panel Filament dapat diakses di `http://localhost:8000/admin`    