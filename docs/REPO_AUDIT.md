# Phase 0 — Repository Inspection & Audit (`REPO_AUDIT.md`)

**Date:** 2026-11-13  
**Project:** Sistem Web-Based Ordering dan Point of Sale Terintegrasi dengan Behavioral Promotion Engine Berbasis RFM pada 774 HUB  
**Workspace:** `d:\xampp\htdocs\774 HUB`

---

## 1. Environment & Tooling Versions

| Component | Detected Version | Status |
|---|---|---|
| Operating System | Windows (XAMPP stack) | Active |
| PHP | `8.2.12` (CLI ZTS Visual C++ 2019 x64) | Compatible with Laravel 12 (`^8.2`) |
| Composer | `2.8.6` | Installed |
| Laravel Framework | `12.72.0` | Installed in `vendor/` |
| Node.js / npm | Node `v24.9.0` / npm `11.6.0` | Installed |
| Frontend Bundler | Vite `^7.0.7` (`laravel-vite-plugin ^2.0.0`) | Configured in `vite.config.js` |
| CSS Framework | Tailwind CSS `^4.0.0` (`@tailwindcss/vite ^4.0.0`) | Configured in `resources/css/app.css` |
| Test Runner | PHPUnit `11.5.58` | Installed in `vendor/` |

---

## 2. Existing Repository Structure

The repository is a clean, newly initialized **Laravel 12** skeleton:
- **`app/`**: Contains only default `Controller.php`, `Models/User.php`, and `Providers/AppServiceProvider.php`. No domain modules exist yet.
- **`database/migrations/`**: Contains only the 3 default Laravel migrations (`0001_01_01_000000_create_users_table.php`, `0001_01_01_000001_create_cache_table.php`, `0001_01_01_000002_create_jobs_table.php`) plus an empty `database/database.sqlite` file.
- **`routes/`**: Only default `web.php` (renders `welcome.blade.php`) and `console.php`.
- **`resources/`**: Default `css/app.css` (Tailwind v4 `@import 'tailwindcss';`), `js/app.js`, and `views/welcome.blade.php`. No frontend SPA framework (Vue/React/Inertia) is installed; therefore, we will use **Blade + Livewire 3 + Alpine.js + Tailwind CSS + Chart.js + `@fontsource/plus-jakarta-sans` + Lucide icons** as specified in Section 4 of the Master Prompt.

---

## 3. Database & Configuration Findings

- **`.env` Database Configuration:** Currently configured for MySQL (`DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=db_774_hub`).
- **Session & Queue:** `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`.
- **Adjustments required in Phase 2:**
  - Set `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `SESSION_LIFETIME=30` (NFR-08: 30-minute idle timeout).
  - Add custom environment variables to `.env` and `.env.example`: `APP_DEMO_MODE=true`, `PAYMENT_DRIVER=mock`, `PAYMENT_SANDBOX_UI=true`, `PAYMENT_WEBHOOK_SECRET=...`, `WHATSAPP_DRIVER=mock`.

---

## 4. Composer & NPM Packages to Install in Phase 2

To fulfill Section 4 of the Master Prompt using open-source packages:
1. **Composer (`composer.json`):**
   - `livewire/livewire` (v3, reactive UI & `wire:poll` for KDS ≤ 5s and POS live updates)
   - `spatie/laravel-permission` (RBAC roles & permissions)
   - `barryvdh/laravel-dompdf` (Digital Invoice PDF rendering)
   - `chillerlan/php-qrcode` or `simplesoftwareio/simple-qrcode` (offline SVG/PNG QR code rendering for Table QRs and Mock QRIS)
2. **NPM (`package.json`):**
   - `chart.js` (Sales, Revenue, and RFM charts)
   - `@fontsource/plus-jakarta-sans` (self-hosted typography per Section 11.2)
   - `lucide` (or inline Blade SVG icon components)

---

## 5. Technical Risks & Mitigations Identified

1. **Windows Workspace Path with Space (`774 HUB`):** Some CLI tools need quoted paths on Windows PowerShell. All scripts and Vite/Composer invocations will quote paths properly.
2. **Tailwind CSS v4 (`@tailwindcss/vite`):** Already installed; custom design tokens (`--bg`, `--surface`, `--primary`, etc.) will be declared in `resources/css/app.css` under `:root` and `@theme` so both CSS variables and Tailwind utility classes work seamlessly.
3. **Concurrency & Idempotency (NFR-06):** Both SQLite (if used in fast unit tests) and MySQL (InnoDB) must enforce unique indexes (`client_request_id`, `unique(provider, event_id)`, `unique(order_id, type)` on `point_ledger`) alongside `DB::transaction()` and `lockForUpdate()`.
