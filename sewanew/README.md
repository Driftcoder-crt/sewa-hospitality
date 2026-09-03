# SEWA Hospitality Platform

A production-grade enterprise multi-module platform for corporate relocation, mobility, and hospitality services.

## 🏗️ Architecture

- **Framework**: Laravel 11.x
- **PHP**: 8.2+
- **Frontend**: Livewire 3.x, Tailwind CSS 4.x, Alpine.js
- **Database**: MySQL/PostgreSQL with ULID primary keys
- **Queue**: Database-driven queues
- **Authentication**: Laravel Sanctum + Spatie Permission

## 📦 Modules (14 Total)

1. **Ai** - AI-powered recommendations and automation
2. **Billing** - Invoice generation and payment processing
3. **Blog** - Content management and publishing
4. **Careers** - Job postings and applicant tracking
5. **Cities** - Location and city information
6. **Cms** - Content management system
7. **Csr** - Corporate social responsibility initiatives
8. **Hr** - Human resources management
9. **I18n** - Internationalization and localization
10. **Leads** - Lead generation and CRM
11. **Organizations** - Client and partner management
12. **Portal** - Customer self-service portal
13. **Search** - Elasticsearch-powered search
14. **Services** - Service catalog and booking
15. **Testimonials** - Customer reviews and testimonials

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ or PostgreSQL 14+

### Installation

```bash
# Clone repository
cd sewanew

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sewa_hospitality
# DB_USERNAME=root
# DB_PASSWORD=secret

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development server
php artisan serve
```

## 🎨 Design System

### Brand Colors

- **SEWA Teal**: `#0E7C66` (Primary)
- **SEWA Teal Dark**: `#0A5C4B` (Hover state)
- **SEWA Bronze**: `#C9974C` (Accent)
- **SEWA Sand**: `#F5F0EB` (Background)
- **SEWA Charcoal**: `#2D3748` (Text)

### Typography

- **Headings**: Fraunces (Serif)
- **Body**: Inter (Sans-serif)

## 📁 Project Structure

```
sewanew/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Modules/          # 14 domain modules
│   │   ├── Ai/
│   │   ├── Billing/
│   │   └── ...
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── admin.php
│   └── portal.php
└── tests/
```

## 🔐 Security Features

- CSRF Protection
- XSS Prevention
- SQL Injection Protection
- Rate Limiting
- Honeypot Spam Protection
- Secure Headers
- Password Hashing (bcrypt)

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ExampleTest.php
```

## 📊 Queue Configuration

Database queues are configured by default:

```env
QUEUE_CONNECTION=database
```

Run queue worker:
```bash
php artisan queue:work
```

## 🌍 Internationalization

Multi-language support built-in via the I18n module:

```php
// Get current locale
app()->getLocale()

// Set locale
app()->setLocale('ar') // RTL support included
```

## 📝 License

Proprietary - All rights reserved SEWA Hospitality

## 🤝 Contributing

Please read our contribution guidelines before submitting pull requests.

---

Built with ❤️ for the hospitality industry
