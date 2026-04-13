# Laravel 12 + Vue 3 Base Framework

A boilerplate project combining **Laravel 12** (backend API) and **Vue 3** (frontend SPA) for rapid software development.  

---

## 🚀 Features
- **Laravel 12** – Powerful backend with REST API support
- **Vue 3 (Composition API)** – Modern frontend framework
- **Vite** – Lightning-fast frontend build tool
- **Authentication Scaffold** – Ready-to-use login/register (API + frontend)
- **RBAC (Role-Based Access Control)** – Extendable permissions system
- **Axios + API Service Layer** – For clean API integration
- **Tailwind CSS** – Utility-first CSS framework
- **Reusable Components** – Base UI components for faster dev
- **Docker Support** (optional) – Containerized local development

---

## 📦 Installation

### Backend (Laravel 12)
```bash
# Clone repository
git clone https://github.com/your-company/your-repo-name.git
cd your-repo-name

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Start local server
php artisan serve
