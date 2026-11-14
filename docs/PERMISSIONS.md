# Role-Based Access Control (RBAC) & Permissions (`PERMISSIONS.md`)

> Aligned with FR-11, NFR-10, and Section 5 of the Master Prompt.  
> Enforced via `spatie/laravel-permission`, route middleware, and Laravel Policies.

---

## 1. Six Actors & Home Routes

| Actor | Role Slug | Auth Guard | Post-Login Redirect | Primary Device |
|---|---|---|---|---|
| **Guest** | *(unauthenticated + table session)* | Session (`qr_table_id` + `public_token`) | `/t/{token}` → `/menu` | Smartphone (≥ 360px) |
| **Member** | `member` | `web` (email/phone + password) | `/member` (or back to `/menu` if table active) | Smartphone (≥ 360px) |
| **Kasir** | `kasir` | `web` (via `/staff/login`) | `/pos` | Laptop / Tablet / PC |
| **Kitchen/Barista** | `kitchen` | `web` (via `/staff/login`) | `/kds` | Landscape Tablet / Monitor |
| **Manager** | `manager` | `web` (via `/staff/login`) | `/manager` | Desktop / Tablet |
| **Owner** | `owner` | `web` (via `/staff/login`) | `/owner` | Desktop / Tablet |

---

## 2. Granular Permissions (`module.action`) & Role Mapping

| Permission Name | Description | Member | Kasir | Kitchen | Manager | Owner |
|---|---|:---:|:---:|:---:|:---:|:---:|
| `member.portal` | Access Member profile, history, points, vouchers, redeem rewards | ✔ | — | — | — | — |
| `pos.access` | Access POS workstation (`/pos/*`) | — | ✔ | — | — | — |
| `orders.view` | View all orders & payment histories | — | ✔ | — | ✔ | ✔ |
| `orders.create` | Create manual order on POS (`Menunggu Pembayaran`) | — | ✔ | — | — | — |
| `orders.edit` | Edit order items while status is `Menunggu Pembayaran` | — | ✔ | — | — | — |
| `orders.confirm_cash` | Confirm Tunai payment (`Menunggu Pembayaran → Dibayar`) | — | ✔ | — | — | — |
| `orders.cancel_unpaid` | Cancel order while `Menunggu Pembayaran` (Customer also can for `own`) | own | ✔ | — | ✔ | — |
| `orders.cancel_paid` | Cancel order when `Dibayar` or `Diproses` (requires reason; refund outside system) | — | — | — | ✔ | — |
| `orders.handover` | Verify 4-char `order_code` & mark `Siap → Selesai` ("Pelayan" function, D3) | — | ✔ | ✔ | — | — |
| `kds.view` | View paid queue on Kitchen Display System (`/kds`, no prices exposed) | — | — | ✔ | — | — |
| `kds.update_status` | Transition `Dibayar → Diproses` (*Mulai Proses*) & `Diproses → Siap` (*Tandai Siap*) | — | — | ✔ | — | — |
| `invoices.view` | View digital invoices and delivery logs (Customer also can for `own`) | own | ✔ | — | ✔ | ✔ |
| `invoices.resend` | Trigger manual **Kirim Ulang Invoice** via Email/WhatsApp | — | ✔ | — | ✔ | — |
| `products.manage` | Full CRUD on products, categories, and dining tables / QR tokens | — | — | — | ✔ | — |
| `products.toggle_availability` | Toggle `is_available` (Tersedia / Habis) | — | ✔ | ✔ | ✔ | — |
| `members.view` | View Member directory, transaction history, and loyalty balances | — | — | — | ✔ | ✔ |
| `loyalty.manage` | Configure loyalty parameters (`points_per_amount`, `earn_on_status`) & rewards CRUD | — | — | — | ✔ | — |
| `loyalty.view` | View loyalty parameters and reward catalogue (read-only) | — | — | — | ✔ | ✔ |
| `rfm.manage` | Edit RFM parameters/segments and trigger manual **Hitung Ulang** | — | — | — | ✔ | — |
| `rfm.view` | View RFM segment distributions and member scores | — | — | — | ✔ | ✔ |
| `promotions.manage` | Create, edit, activate/deactivate promotions (6 behavioral rules) | — | — | — | ✔ | — |
| `promotions.view` | View promotion performance, usage, and return tracking | — | — | — | ✔ | ✔ |
| `reports.sales.view` | View and export Sales Dashboard & *Laporan Penjualan* | — | — | — | ✔ | ✔ |
| `reports.revenue.view` | View and export Revenue Dashboard & *Laporan Pendapatan* | — | — | — | ✔ | ✔ |
| `users.manage_staff` | Create/edit/deactivate non-Owner staff accounts (Kasir, Kitchen, Manager) | — | — | — | ✔ | ✔ |
| `users.manage_all` | Manage all staff accounts and roles including Owner | — | — | — | — | ✔ |
| `settings.manage` | Configure outlet profile, tax rate, and view integration status | — | — | — | ✔ | ✔ |

---

## 3. Enforcement Rules

1. **Double Layer Protection:** Every protected route group uses middleware (`auth`, `role:...` or `permission:...`) AND every controller / Livewire action invokes `$this->authorize(...)` against Eloquent Policies (`OrderPolicy`, `InvoicePolicy`, `ProductPolicy`, `PromotionPolicy`, `UserPolicy`).
2. **Ownership Isolation (404 vs 403):**
   - When a Guest or Member attempts to access `/order/{public_token}` or `/member/orders/{order}` belonging to another customer, the query is scoped by ownership (`member_id = auth()->user()->member->id` or matching `public_token` / session) and returns **HTTP 404** to prevent order/invoice enumeration.
   - When an authenticated user accesses a staff/role route outside their permission matrix (e.g., Kasir visiting `/manager/reports` or Kitchen visiting `/pos`), the application returns **HTTP 403** with the custom Indonesian page (*"Anda tidak memiliki akses ke halaman ini"*) and a button linking back to their role's home page.
3. **Kitchen Data Redaction:** The `/kds` view and `/kds/feed` JSON endpoint serialize only operational fields (`id`, `order_number`, `order_code`, `table_label`, `status`, `paid_at`, `items[product_name, quantity, note]`) and **never** include `unit_price`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, or payment details.
4. **30-Minute Idle Session Timeout (NFR-08):** Configured via `SESSION_LIFETIME=30` and enforced by a sliding activity middleware that logs out idle authenticated sessions after 30 minutes and redirects gracefully to `/login` or `/staff/login`.
