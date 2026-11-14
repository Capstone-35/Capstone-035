# Open Questions & Configurable Assumptions (`OPEN_QUESTIONS.md`)

## 1. Open Questions to Confirm with 774 HUB Partner (Section 0.6)

These items are implemented as configurable settings or swappable adapters so development is never blocked and the partner can adjust them at any time without code changes:

| ID | Question for 774 HUB Partner | Current Implementation / Default | Where Configured |
|---|---|---|---|
| **Q1** | What is the final applicable local tax rate (*Pajak Daerah / PB1*) for 774 HUB Yogyakarta? | Configurable `tax_rate` (0% in production seed; 10% in demo seed, hidden on UI when 0%). | `/manager` Settings (`settings.tax_rate`) |
| **Q2** | What are the final loyalty point earning ratios and reward redemption point costs? | Default: **1 point per Rp10.000** of subtotal after discount (`floor`). Rewards catalogue is editable by Manager. | `/manager/loyalty` & `/manager/rewards` |
| **Q3** | Who physically performs order handover ("Pelayan" lane in BPMN) during daily shifts? | Permission `orders.handover` is granted to both **Kasir** and **Kitchen/Barista**, and the user who confirms handover is logged. | Role/Permission matrix (`spatie/laravel-permission`) |
| **Q4** | Should loyalty points be awarded when the order becomes `Dibayar` or `Selesai`? | Default: **`Selesai`** (`loyalty.earn_on_status = Selesai`), switchable to `Dibayar`. | `/manager/loyalty` (`loyalty.earn_on_status`) |
| **Q5** | What is the operational SOP for cancelling an order after payment is confirmed? | Restricted to **Manager only** with mandatory reason and notice *"Pengembalian dana ditangani di luar sistem"*, plus automatic point/promo reversal. | `OrderPolicy` & `OrderService::cancel` |
| **Q6** | Should any future promotions be usable by Guest customers without registering? | Currently **Members only** per BPMN; Guests see promotions with a CTA to register. | `PromotionEngine` |
| **Q7** | What are the final RFM analysis period (days), score thresholds (1–5), and segment names? | Default: 90-day window; R/F/M thresholds and 6 segment rules (*Pelanggan Setia, Pelanggan Loyal, Pelanggan Baru, Berpotensi, Berisiko, Tidak Aktif*) stored in DB and editable by Manager. | `/manager/rfm/parameters` |
| **Q8** | Which licensed QRIS payment gateway provider (e.g., Midtrans, Xendit, DOKU) will be used in production? | Built behind `PaymentGateway` interface. Default `PAYMENT_DRIVER=mock` (clearly badged **SIMULASI**) + `/sandbox/payments` webhook simulator. | `.env` (`PAYMENT_DRIVER`) |
| **Q9** | Which Email SMTP / transactional provider and WhatsApp API provider will be used? | Built behind `InvoiceNotifier` interface (`EmailInvoiceNotifier` via Laravel Mail, `WhatsAppInvoiceNotifier` via adapter, default `mock`). | `.env` (`MAIL_*`, `WHATSAPP_DRIVER`) |
| **Q10** | Which holiday dates apply to **Promo Hari Besar**? | Manager configures exact `starts_at` / `ends_at` date windows per promo (no hardcoded calendar). | `/manager/promotions` |
| **Q11** | Which weekdays and hours define **Slow Hour Promo** at 774 HUB? | Default demo: Weekdays (Mon–Fri) `14:00–16:00` WIB; configurable per promo in conditions JSON. | `/manager/promotions` |
| **Q12** | Must digital invoices display an outlet NPWP / Tax ID? | Optional `outlet_tax_id` setting in outlet profile; rendered on invoice when non-empty. | `settings.outlet_tax_id` |

---

## 2. Flagged Assumptions (`ASSUMPTION` A1–A8)

| ID | Assumption | Default Value | Configurable Via |
|---|---|---|---|
| **A1** | QRIS payment expiry window | `15` minutes | `settings.qris_expiry_minutes` |
| **A2** | Points earning rate & expiry | `1` point per `Rp10.000` (`points_per_amount = 10000`), no expiry by default | `settings.points_per_amount` |
| **A3** | RFM analysis period & thresholds | `90` days; 1–5 thresholds for R, F, M | `rfm_parameters` table |
| **A4** | Comeback & Repeat Order thresholds | Comeback: `30` days inactive (alt `60`); Repeat Order: `5` successful orders in `30` days | Promotion `conditions` JSON |
| **A5** | Promotion / reward stacking | Max `1` promotion OR `1` reward voucher per order | `OrderPricingService` |
| **A6** | Menu availability | Manual toggle (`is_available`) by Manager/Kasir/Kitchen; no stock/ingredient tracking | `/manager/products`, POS, KDS |
| **A7** | Tax rate default | `0%` in production seed; `10%` in demo seed | `settings.tax_rate` |
| **A8** | Cart storage & pricing trust | Server-side session cart; all prices/discounts/taxes recomputed from DB on checkout | `OrderPricingService` |
