# Route Map & Sitemap Specification (`ROUTES.md`)

> Organized into modular route files (`routes/customer.php`, `routes/member.php`, `routes/pos.php`, `routes/kds.php`, `routes/manager.php`, `routes/owner.php`, `routes/webhooks.php`) loaded via `bootstrap/app.php` or `routes/web.php`.

---

## 1. Public & Customer Ordering Routes (`routes/customer.php`)

| Method | URI | Route Name | Auth / Guard | Purpose |
|---|---|---|---|---|
| `GET` | `/` | `home` | Public | Redirects to `/menu` (or shows table scan prompt / demo table selector) |
| `GET` | `/t/{qr_token}` | `table.scan` | Public | Validates active `DiningTable` QR token, stores in session, renders **A1 QR Landing** |
| `POST` | `/t/{qr_token}/guest` | `table.continue_guest` | Public | Marks session as Guest mode (`step 2 of 5`) and redirects to `/menu` |
| `GET` | `/menu` | `menu.index` | Public | **A2 Menu Homepage** with category chips (`?category=`) & search (`?q=`) |
| `GET` | `/menu/{product:slug}` | `menu.show` | Public | **A4 Product Detail** page / bottom-sheet payload |
| `GET` | `/cart` | `cart.index` | Public | **A5 Cart** review page |
| `POST` | `/cart/items` | `cart.items.store` | Public (CSRF) | Add item (product_id, qty, note) to server-side session cart |
| `PATCH` | `/cart/items/{lineKey}` | `cart.items.update` | Public (CSRF) | Update item quantity / note |
| `DELETE` | `/cart/items/{lineKey}` | `cart.items.destroy` | Public (CSRF) | Remove item from cart |
| `GET` | `/checkout` | `checkout.index` | Table session + non-empty cart | **A6 Checkout** (customer info, Member promo/voucher selector, summary) |
| `POST` | `/checkout` | `checkout.store` | `throttle:checkout` | Creates `Order` (`Menunggu Pembayaran`) idempotently via `client_request_id` |
| `GET` | `/order/{public_token}/payment` | `order.payment` | Order owner (session/member) | **A7 Payment Selection** & **A8 Payment Status** (Tunai instructions / QRIS timer) |
| `POST` | `/order/{public_token}/payment` | `order.payment.select` | Order owner | Selects/switches payment method (`tunai` or `qris`), creates/renews QRIS charge |
| `GET` | `/order/{public_token}` | `order.track` | Order owner | **A9 Order Tracking** & **A10 Order Success** (4-step live stepper) |
| `GET` | `/order/{public_token}/status` | `order.status.json` | Order owner | JSON polling endpoint for payment & kitchen status |
| `POST` | `/order/{public_token}/cancel` | `order.cancel.customer` | Order owner | Cancels own order while status is `Menunggu Pembayaran` |
| `GET` | `/order/{public_token}/invoice` | `order.invoice.show` | Order owner | **G HTML Digital Invoice** view |
| `GET` | `/order/{public_token}/invoice.pdf` | `order.invoice.pdf` | Order owner | **G PDF Digital Invoice** download (`dompdf`) |
| `GET` | `/promotions` | `promotions.public` | Public | **A16 Promotion** page (Guest sees info + CTA; Member sees eligibility) |

---

## 2. Authentication & Shared Routes (`routes/web.php`)

| Method | URI | Route Name | Auth / Guard | Purpose |
|---|---|---|---|---|
| `GET` | `/login` | `login` | `guest` | **A11 Member Login** page |
| `POST` | `/login` | `login.store` | `guest`, `throttle:login` | Authenticate Member (redirects to `/checkout`, `/menu`, or `/member`) |
| `GET` | `/register` | `register` | `guest` | **A12 Member Register** page (with UU PDP consent checkbox) |
| `POST` | `/register` | `register.store` | `guest`, `throttle:register` | Create Member user + `members` row |
| `GET` | `/staff/login` | `staff.login` | `guest` | Separate **Staff Login** page (Kasir, Kitchen, Manager, Owner) |
| `POST` | `/staff/login` | `staff.login.store` | `guest`, `throttle:login` | Authenticate staff and redirect to role home (`/pos`, `/kds`, `/manager`, `/owner`) |
| `POST` | `/logout` | `logout` | `auth` | Logout and invalidate session |
| `GET` | `/offline-notice` | `offline` | Public | Friendly connection/offline recovery instructions |
| `GET` | `/styleguide` | `styleguide` | `auth`, `role:manager|owner` (non-prod) | **Section 11.3 Design System & Reusable Component Showcase** |

---

## 3. Member Portal Routes (`routes/member.php` — `auth`, `role:member`)

| Method | URI | Route Name | Purpose |
|---|---|---|---|
| `GET` | `/member` | `member.dashboard` | Member overview (points balance, active orders, eligible promos) |
| `GET` | `/member/profile` | `member.profile` | **A13 Member Profile** & UU PDP data privacy info |
| `PATCH` | `/member/profile` | `member.profile.update` | Update name, email, phone, password |
| `GET` | `/member/orders` | `member.orders.index` | **A14 Order History** list |
| `GET` | `/member/orders/{order:order_number}` | `member.orders.show` | Order detail + invoice link (ownership checked → 404 if not own) |
| `POST` | `/member/orders/{order:order_number}/repeat` | `member.orders.repeat` | **Pesan Lagi**: loads available items into cart, notifies skipped unavailable items |
| `GET` | `/member/loyalty` | `member.loyalty` | **A15 Reward & Loyalty** (balance, point ledger, reward catalogue, my vouchers) |
| `POST` | `/member/rewards/{reward}/redeem` | `member.rewards.redeem` | Redeem points for a reward voucher (`reward_redemptions`) |

---

## 4. Kasir Point of Sale Routes (`routes/pos.php` — `auth`, `permission:pos.access`)

| Method | URI | Route Name | Permission | Purpose |
|---|---|---|---|---|
| `GET` | `/pos` | `pos.dashboard` | `pos.access` | **POS Dashboard** (today's counts, unpaid queue, failed invoices, recent 10) |
| `GET` | `/pos/orders` | `pos.orders.index` | `orders.view` | **Pesanan Masuk** (live board/table with status & source filters) |
| `GET` | `/pos/orders/{order:order_number}` | `pos.orders.show` | `orders.view` | POS Order Detail, edit items (while unpaid), timeline, payment, invoice |
| `PATCH` | `/pos/orders/{order:order_number}` | `pos.orders.update` | `orders.edit` | Add/update/remove order items while `Menunggu Pembayaran` (D8) |
| `GET` | `/pos/new` | `pos.orders.create` | `orders.create` | **Pesanan Manual** workstation (two-pane 65%/35% layout, UC-05) |
| `POST` | `/pos/orders` | `pos.orders.store` | `orders.create` | Save manual order (`source='pos'`, status `Menunggu Pembayaran`) |
| `GET` | `/pos/payments` | `pos.payments.index` | `orders.confirm_cash` | **Pembayaran** queue (`Menunggu Pembayaran` Tunai & QRIS monitor) |
| `POST` | `/pos/orders/{order:order_number}/confirm-cash` | `pos.orders.confirm_cash` | `orders.confirm_cash` | **Konfirmasi Pembayaran** Tunai (`Menunggu Pembayaran → Dibayar`, UC-04) |
| `POST` | `/pos/orders/{order:order_number}/cancel` | `pos.orders.cancel` | `orders.cancel_unpaid` | Cancel order while `Menunggu Pembayaran` |
| `POST` | `/pos/orders/{order:order_number}/handover` | `pos.orders.handover` | `orders.handover` | Verify 4-char `order_code` & mark `Siap → Selesai` (D3) |
| `GET` | `/pos/transactions` | `pos.transactions.index` | `orders.view` | **Transaksi** full list & search |
| `GET` | `/pos/invoices` | `pos.invoices.index` | `invoices.view` | **Invoice** list & delivery status monitor |
| `POST` | `/pos/invoices/{invoice}/resend` | `pos.invoices.resend` | `invoices.resend` | **Kirim Ulang Invoice** (with optional updated recipient email) |
| `POST` | `/pos/products/{product}/toggle-availability` | `pos.products.toggle` | `products.toggle_availability` | Quick toggle product Tersedia / Habis |
| `GET` | `/pos/feed` | `pos.feed` | `orders.view` | Live JSON feed for POS order board |

---

## 5. Kitchen Display System Routes (`routes/kds.php` — `auth`, `permission:kds.view`)

| Method | URI | Route Name | Permission | Purpose |
|---|---|---|---|---|
| `GET` | `/kds` | `kds.index` | `kds.view` | **Full-screen 3-column KDS board** (*Pesanan Baru, Sedang Diproses, Siap Diserahkan*) |
| `GET` | `/kds/history` | `kds.history` | `kds.view` | Recent `Selesai` orders & kitchen availability toggle |
| `GET` | `/kds/feed` | `kds.feed` | `kds.view` | JSON feed of `Dibayar`, `Diproses`, `Siap` orders (strictly no prices/totals) |
| `POST` | `/kds/orders/{order:order_number}/start` | `kds.orders.start` | `kds.update_status` | **Mulai Proses** (`Dibayar → Diproses`, UC-06) |
| `POST` | `/kds/orders/{order:order_number}/ready` | `kds.orders.ready` | `kds.update_status` | **Tandai Siap** (`Diproses → Siap` after D11 quality confirmation) |
| `POST` | `/kds/orders/{order:order_number}/handover` | `kds.orders.handover` | `orders.handover` | Optional handover from KDS (`Siap → Selesai` with `order_code` verification) |

---

## 6. Manager Routes (`routes/manager.php` — `auth`, `role:manager`)

| Method | URI | Route Name | Purpose |
|---|---|---|---|
| `GET` | `/manager` | `manager.dashboard` | **Manager Dashboard** (KPIs, daily sales chart, top 10 products, RFM doughnut, active promos, ops metrics) |
| `GET` | `/manager/transactions` | `manager.transactions.index` | All transactions + post-payment cancellation (`Dibayar`/`Diproses → Dibatalkan` with reason, D10) |
| `GET` | `/manager/transactions/{order:order_number}` | `manager.transactions.show` | Full transaction detail, timeline, payment attempts, invoice resend |
| `POST` | `/manager/orders/{order:order_number}/cancel` | `manager.orders.cancel` | Manager cancellation (unpaid or paid with mandatory reason) |
| `GET` | `/manager/orders` | `manager.orders.index` | Operational order monitor across all statuses |
| `GET` | `/manager/members` | `manager.members.index` | Member directory, points, RFM segment, transaction history |
| `GET` | `/manager/members/{member}` | `manager.members.show` | Member detail & RFM / point ledger drawer |
| `GET` | `/manager/rfm` | `manager.rfm.index` | **RFM Overview** (segment chart + table, **Hitung Ulang** button) |
| `POST` | `/manager/rfm/recalculate` | `manager.rfm.recalculate` | Run RFM calculation immediately |
| `GET` | `/manager/rfm/segments/{segment:slug}` | `manager.rfm.segment` | Members in specific RFM segment |
| `GET` | `/manager/rfm/parameters` | `manager.rfm.parameters` | Edit RFM period, 1–5 thresholds, and segment rules |
| `PATCH` | `/manager/rfm/parameters` | `manager.rfm.parameters.update` | Save validated monotonic RFM thresholds & segment rules |
| `GET` | `/manager/promotions` | `manager.promotions.index` | **Promosi** list & status filter |
| `GET` | `/manager/promotions/create` | `manager.promotions.create` | Create behavioral promotion (6 rule types, UC-09) |
| `POST` | `/manager/promotions` | `manager.promotions.store` | Store promotion (immediately active for next checkout) |
| `GET` | `/manager/promotions/performance` | `manager.promotions.performance` | **Evaluasi & Performa Promosi** (usage, discount, return rate from Promo Return Tracking) |
| `GET` | `/manager/promotions/{promotion}` | `manager.promotions.show` | Promotion detail & usage/return history |
| `GET` | `/manager/promotions/{promotion}/edit` | `manager.promotions.edit` | Edit promotion parameters |
| `PATCH` | `/manager/promotions/{promotion}` | `manager.promotions.update` | Update promotion |
| `POST` | `/manager/promotions/{promotion}/toggle` | `manager.promotions.toggle` | Activate / deactivate promotion (UC-09 S-1) |
| `GET` | `/manager/loyalty` | `manager.loyalty.index` | Loyalty parameters (`points_per_amount`, `earn_on_status`, tax & outlet settings) |
| `PATCH` | `/manager/loyalty` | `manager.loyalty.update` | Update loyalty & system settings |
| `GET` | `/manager/rewards` | `manager.rewards.index` | **Reward & Loyalty** catalogue CRUD |
| `POST` | `/manager/rewards` | `manager.rewards.store` | Create reward |
| `PATCH` | `/manager/rewards/{reward}` | `manager.rewards.update` | Update reward |
| `GET` | `/manager/products` | `manager.products.index` | **Produk** list, search, filter, availability toggles |
| `GET` | `/manager/products/create` | `manager.products.create` | Create product form |
| `POST` | `/manager/products` | `manager.products.store` | Store product with image validation |
| `GET` | `/manager/products/{product}/edit` | `manager.products.edit` | Edit product form |
| `PATCH` | `/manager/products/{product}` | `manager.products.update` | Update product |
| `DELETE` | `/manager/products/{product}` | `manager.products.destroy` | Soft-delete product |
| `GET` | `/manager/categories` | `manager.categories.index` | **Kategori** CRUD & sort order |
| `POST` | `/manager/categories` | `manager.categories.store` | Create category |
| `PATCH` | `/manager/categories/{category}` | `manager.categories.update` | Update/reorder category |
| `DELETE` | `/manager/categories/{category}` | `manager.categories.destroy` | Delete/deactivate category |
| `GET` | `/manager/tables` | `manager.tables.index` | **Meja & QR** management + printable QR sheet |
| `POST` | `/manager/tables` | `manager.tables.store` | Create dining table with QR token |
| `POST` | `/manager/tables/{table}/regenerate` | `manager.tables.regenerate` | Regenerate table QR token (invalidating old token) |
| `GET` | `/manager/tables/{table}/qr.svg` | `manager.tables.qr` | Download table QR image |
| `GET` | `/manager/reports` | `manager.reports.index` | **Laporan Penjualan & Pendapatan** (filter harian/mingguan/bulanan + `?format=csv`) |
| `GET` | `/manager/users` | `manager.users.index` | **Pengguna** (manage non-Owner staff accounts) |
| `POST` | `/manager/users` | `manager.users.store` | Create staff account |
| `PATCH` | `/manager/users/{user}` | `manager.users.update` | Update staff account |

---

## 7. Owner Routes (`routes/owner.php` — `auth`, `role:owner`)

| Method | URI | Route Name | Purpose |
|---|---|---|---|
| `GET` | `/owner` | `owner.dashboard` | **Owner Executive Dashboard** (Total penjualan, Total pendapatan, tx count, AOV, trends, category/hour breakdown, Member vs Tamu) |
| `GET` | `/owner/reports/sales` | `owner.reports.sales` | **Laporan Penjualan** (harian/mingguan/bulanan + CSV export + print view) |
| `GET` | `/owner/reports/revenue` | `owner.reports.revenue` | **Laporan Pendapatan** (gross sales, discount, tax, total received by payment method + CSV export) |
| `GET` | `/owner/customers` | `owner.customers.index` | **Pelanggan** (read-only Member directory & RFM segmentation) |
| `GET` | `/owner/promotions` | `owner.promotions.index` | **Performa Promosi** (read-only promotion usage & return tracking) |
| `GET` | `/owner/users` | `owner.users.index` | **Pengguna** (manage all staff accounts including Manager & Owner) |
| `POST` | `/owner/users` | `owner.users.store` | Create staff/Owner account |
| `PATCH` | `/owner/users/{user}` | `owner.users.update` | Update staff/Owner account |

---

## 8. Webhooks & Sandbox Simulator (`routes/webhooks.php`)

| Method | URI | Route Name | Protection | Purpose |
|---|---|---|---|---|
| `POST` | `/webhooks/payments/{provider}` | `webhooks.payments` | Signature HMAC, `throttle:webhooks`, CSRF-exempt | Real/simulated QRIS webhook processor |
| `GET` | `/sandbox/payments` | `sandbox.payments.index` | Non-prod + `role:manager` | **SIMULASI** QRIS Webhook tester UI (send signed paid/failed/expired/duplicate/mismatch callbacks) |
| `POST` | `/sandbox/payments/trigger` | `sandbox.payments.trigger` | Non-prod + `role:manager` | Dispatches signed webhook request to `webhooks.payments` |
