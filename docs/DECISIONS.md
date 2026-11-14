# Architectural & Reconciliation Decisions (`DECISIONS.md`)

This document records all reconciliation decisions from **Section 0.2** of the Master Prompt, our architectural choices, and any schema refinements per **Section 12**.

---

## 1. Mandatory Source Reconciliation Decisions (D1–D14)

| ID | Topic | Applied Decision |
|---|---|---|
| **D1** | Promotion Strategies | Implement **all six** rules defined in Proposal §3.4.1 / FR-12 / UC-09: `comeback` (Comeback Promo), `repeat_order` (Repeat Order Reward), `hari_besar` (Promo Hari Besar), `welcome_next_visit` (Welcome/Next Visit Promo), `slow_hour` (Slow Hour Promo), and `return_tracking` (Promo Return Tracking). No 7th type is added. |
| **D2** | Pajak (Tax) | Configurable `tax_rate` setting (percentage, can be `0`). Displayed on Checkout & Invoice only when `tax_rate > 0`. Stored per order as a snapshot (`tax_rate`, `tax_amount`). Production seed defaults to `0%`; Demo seed uses `10%` clearly labelled as a demo value. No tax report is built. |
| **D3** | BPMN "Pelayan" Lane | Do **not** create a 7th role. "Pelayan" is an operational function performed by staff holding permission `orders.handover` (**Kasir** and **Kitchen/Barista**). Handover verifies the 4-character `order_code`, transitions `Siap → Selesai`, and records `handed_over_by` / `actor_user_id` in `order_status_histories`. |
| **D4** | Loyalty Points Trigger Status | Configurable setting `loyalty.earn_on_status` defaulting to **`Selesai`** (matches BPMN To-Be and guarantees paid + completed). Switchable by Manager to `Dibayar`. Point earning is idempotent via `unique(order_id, type)` on `point_ledger`. The exact same definition of `Order::successful()` is used by Loyalty, RFM, Repeat-Order marker, Promo evaluation, and Reporting. |
| **D5** | Payment Methods | Strictly **Tunai** and **QRIS** only. |
| **D6** | Invoice Delivery Channels | Adapter pattern (`InvoiceNotifier`) supporting **Email** (default, required at checkout) and **WhatsApp** (optional when phone is provided; runs in `mock` mode unless credentials exist). Delivery status (`pending`, `sent`, `failed`) is completely decoupled from `orders.status`, with automatic retry up to 3 times (`tries = 3`) and manual **Kirim Ulang Invoice** in POS. |
| **D7** | Table Number & Checkout Input | Table number is pre-filled from the scanned QR token (`/t/{qr_token}`) and displayed read-only on customer checkout. Only Kasir on POS has a table selector / "Ganti meja". Customer enters `nama` and `email` (pre-filled for logged-in Members). |
| **D8** | Editing Order Items | Items can be added/edited/removed by Kasir **only while order status is `Menunggu Pembayaran`**. Once `Dibayar`, items and amounts are locked. |
| **D9** | Single Root Transaction Entity | **`orders` is the single root transaction table.** `order_number` is the transaction number. No separate `transactions` table exists. `payments` is a 1:N child table recording payment attempts, guarded so at most one payment per order can reach `paid`. |
| **D10** | Cancellation & Refund Rules | Pre-payment (`Menunggu Pembayaran`): cancellable by Customer (own order), Kasir, or Manager. Post-payment (`Dibayar` or `Diproses`): cancellable by **Manager only**, requiring a mandatory `cancel_reason` and displaying the notice *"Pengembalian dana ditangani di luar sistem"*. `Siap`, `Selesai`, and `Dibatalkan` are terminal for cancellation. |
| **D11** | Kitchen Quality Check Loop | No extra order status. Clicking **Tandai Siap** on KDS opens a confirmation modal: *"Pesanan sudah sesuai?"* with actions **Ya, Tandai Siap** and **Belum, kembali mengerjakan**. |
| **D12** | ERD & Class Diagram | Derived directly from FR/UC/BPMN and documented in `docs/ERD.md`. |
| **D13** | Promotion Eligibility for Guests | Promotions and reward vouchers apply to **Members only**. Guests can view the `/promotions` page as informational content with a CTA **"Daftar untuk memakai promo"**. |
| **D14** | Promo / Reward Stacking | At most **one promotion OR one reward voucher redemption per order** (Assumption A5). |

---

## 2. Architecture & Schema Refinements

1. **Modular Domain Structure (`app/Domain/*`):**
   We organize business logic under `app/Domain/{Access,Catalog,Ordering,Payment,Kitchen,Invoice,Loyalty,Rfm,Promotion,Reporting,Settings}` while keeping Eloquent models in `app/Models/*` (standard Laravel convention for seamless factory/policy discovery) and Livewire/Controllers in `app/Livewire/*` and `app/Http/Controllers/*`.
2. **Demo Mode Flag (`is_demo`):**
   To satisfy Section 19 ("All demo rows carry `is_demo = true`"), we include a boolean `is_demo` column (default `false`) on core transactional and master tables (`users`, `categories`, `products`, `tables`, `orders`, `promotions`, `rewards`, `rfm_analyses`) and display a persistent **"Data Demonstrasi"** banner when `config('app.demo_mode')` is active.
3. **Single-Active Paid Guard on `payments`:**
   Since MySQL does not support partial unique indexes (`WHERE status = 'paid'`) natively without generated columns, we enforce "at most one paid payment per order" via:
   - Row-level locking (`Order::whereKey($id)->lockForUpdate()->first()`) in `PaymentService` checking `$order->status === OrderStatus::MenungguPembayaran`, AND
   - A nullable virtual/stored generated column or unique constraint check in `PaymentService` + `Order::paid_at` check inside a DB transaction.
4. **No Objections to Section 0:**
   All reconciliation decisions in Section 0 are sound, internally consistent, and directly aligned with the BPMN To-Be diagram and Capstone Proposal.
