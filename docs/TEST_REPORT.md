# 774 HUB — Laporan Eksekusi Pengujian Otomatis (Automated Test Report)

> **Proyek:** Pengembangan Sistem Web-Based Ordering dan Point of Sale Terintegrasi dengan Behavioral Promotion Engine Berbasis RFM pada 774 HUB  
> **Status:** PASSED (71/71 Tests Passed, 273 Assertions, 0 Failures, 0 Errors)  
> **Tanggal Pengujian:** 14 November 2026  
> **Framework:** PHPUnit 11.5.58 & Laravel 12 Testing Framework  

---

## 1. Ringkasan Eksekusi Test Suite

| Test Suite / Modul | File Test | Jumlah Test | Assertions | Status | Waktu |
|---|---|---|---|---|---|
| **Auth & Keamanan (FR-02, FR-11, NFR-08..11)** | `tests/Feature/AuthTest.php` | 6 | 41 | **PASSED** | ~6.5 s |
| **Pemesanan Mandiri & POS (FR-01, FR-06, NFR-06, 12)** | `tests/Feature/OrderingTest.php` | 6 | 25 | **PASSED** | ~6.6 s |
| **Pembayaran & State Machine (FR-04, FR-05, NFR-06)** | `tests/Feature/PaymentTest.php` | 9 | 30 | **PASSED** | ~7.2 s |
| **Simulator QRIS & Sandbox Webhook (NFR-06)** | `tests/Feature/QrisSimulatorTest.php` | 6 | 13 | **PASSED** | ~6.8 s |
| **Kitchen Display System (FR-07, NFR-03, 10)** | `tests/Feature/KdsTest.php` | 6 | 28 | **PASSED** | ~4.3 s |
| **Invoice Digital & Retry (FR-08, NFR-07)** | `tests/Feature/InvoiceTest.php` | 4 | 16 | **PASSED** | ~3.7 s |
| **Loyalty Points & Rewards (FR-09, NFR-16)** | `tests/Feature/LoyaltyTest.php` | 5 | 14 | **PASSED** | ~5.0 s |
| **Portal & Keamanan Member (FR-09, NFR-11)** | `tests/Feature/MemberPortalTest.php` | 9 | 28 | **PASSED** | ~6.8 s |
| **Mesin RFM Deterministik (FR-12, NFR-17)** | `tests/Unit/RfmServiceTest.php` | 4 | 36 | **PASSED** | ~3.1 s |
| **Behavioral Promotion Engine (FR-10, 12, D14)** | `tests/Feature/PromotionEngineTest.php` | 5 | 8 | **PASSED** | ~4.1 s |
| **Manajemen Promosi Manager (FR-10, 12, UC-09)** | `tests/Feature/ManagerPromotionTest.php` | 6 | 22 | **PASSED** | ~6.5 s |
| **Pelaporan & Isolasi Data (FR-13, NFR-04, NFR-10)** | `tests/Feature/ReportTest.php` | 3 | 9 | **PASSED** | ~3.3 s |
| **Smoke & Basic Framework Tests** | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | 2 | 3 | **PASSED** | ~2.1 s |
| **TOTAL** | **13 Test Files** | **71 Tests** | **273 Assertions** | **100% PASSED** | **107.75 s** |

---

## 2. Cakupan Pengujian Rinci per Kebutuhan (FR & NFR)

### 2.1 Autentikasi, Hak Akses (RBAC), & Kepatuhan UU PDP
- `test_member_registration_requires_uu_pdp_consent`: Memverifikasi pendaftaran akun Member ditolak jika tidak mencentang persetujuan pemrosesan data pribadi (NFR-11, UU PDP No. 27/2022).
- `test_successful_member_registration`: Pendaftaran berhasil membuat user, profil member, kode member `MBR-774-xxxx`, role `member`, dan auto-login.
- `test_member_login_redirects_to_member_dashboard`: Login member menggunakan email atau nomor handphone mengarahkan ke dashboard member `/member`.
- `test_staff_login_redirects_according_to_role`: Pengalihan peran staf: `kasir` -> `/pos`, `kitchen` -> `/kds`, `manager` -> `/manager`, `owner` -> `/owner`.
- `test_rbac_prevents_unauthorized_access`: Verifikasi Kasir dilarang mengakses modul Manager (HTTP 403), Kitchen dilarang mengakses POS (HTTP 403), dan Guest dilarang mengakses halaman Member (HTTP 302).
- `test_session_idle_timeout_after_30_minutes`: Sesi tidak aktif lebih dari 30 menit otomatis di-logout dan diarahkan ke form login staf/member dengan pesan peringatan (NFR-08, NFR-09).

### 2.2 Pemesanan, Validasi Keranjang, & Idempotensi
- `test_scan_valid_qr_sets_session_and_shows_menu`: QR token meja valid otomatis mengisi nomor meja secara read-only dan menyimpan sesi meja aktif (FR-01).
- `test_invalid_qr_returns_invalid_page`: Token QR meja yang tidak terdaftar menampilkan halaman informasi "QR Meja Tidak Ditemukan" tanpa crash.
- `test_cart_management_add_update_remove`: Penambahan produk, pembaruan kuantitas/catatan khusus (notes), dan penghapusan item keranjang berfungsi dengan baik.
- `test_guest_checkout_creates_order_with_status_menunggu_pembayaran`: Checkout tamu tanpa akun berhasil membuat pesanan berstatus `Menunggu Pembayaran` (FR-04).
- `test_checkout_is_idempotent_with_client_request_id`: Pengiriman ganda formulir checkout dengan `client_request_id` yang sama menghasilkan tepat 1 transaksi pesanan (NFR-06).
- `test_server_side_pricing_protects_against_tampering`: Harga subtotal dihitung ulang 100% di sisi server dari tabel `products`; modifikasi harga dari browser diabaikan.

### 2.3 Pembayaran, Siklus Status, & Webhook QRIS
- `test_order_state_machine_valid_lifecycle`: Transisi sah berurutan: `Menunggu Pembayaran` -> `Dibayar` -> `Diproses` -> `Siap` -> `Selesai`.
- `test_order_state_machine_illegal_skip_throws_exception`: Upaya melompati status (misal: langsung dari `Menunggu Pembayaran` ke `Selesai`) ditolak dengan `ValidationException`.
- `test_cancellation_rules_per_actor`: Pelanggan dan Kasir dapat membatalkan saat `Menunggu Pembayaran`. Setelah `Dibayar`, Kasir dilarang membatalkan.
- `test_manager_can_cancel_paid_order_with_reason`: Manajer berhak membatalkan pesanan yang sudah dibayar dengan alasan wajib (D10).
- `test_kasir_confirm_cash_payment`: Konfirmasi tunai oleh kasir mencatat `cash_received`, menghitung kembalian, dan mentransisikan pesanan ke `Dibayar` secara row-locked (FR-05, NFR-06).
- `test_qris_webhook_valid_signature_transitions_order_to_dibayar`: Webhook QRIS dengan HMAC signature SHA-256 valid mentransisikan transaksi ke `Dibayar`.
- `test_qris_webhook_rejects_invalid_signature`: Webhook dengan tanda tangan tidak valid ditolak dengan HTTP 401.
- `test_qris_webhook_rejects_amount_mismatch`: Notifikasi nominal tidak sesuai tagihan ditandai `amount_mismatch` dan tidak mengubah pesanan menjadi Dibayar.
- `test_qris_webhook_idempotency_duplicate_event_ignored`: Panggilan ulang webhook dengan `event_id` yang sama diabaikan secara idempoten (HTTP 200 `duplicate_ignored`).

### 2.4 Simulator QRIS & Sandbox Webhook (NFR-06)
- `test_manager_can_view_qris_sandbox_page`: Halaman `/sandbox/payments` menampilkan antrean transaksi QRIS dan riwayat log kejadian webhook.
- `test_manager_can_trigger_qris_simulation_paid_without_custom_event_id`: Manager dapat menembakkan simulasi pembayaran berhasil secara aman tanpa mewajibkan input manual `custom_event_id`.
- `test_manager_can_trigger_qris_simulation_duplicate`: Simulasi pengiriman webhook ganda (replay attack) diverifikasi idempoten tanpa duplikasi status atau poin.
- `test_customer_can_view_qris_payment_page_with_svg`: Halaman pembayaran pelanggan merender kode SVG QRIS dinamis secara valid.
- `test_customer_can_simulate_qris_payment_success`: Pelanggan di lingkungan simulasi dapat menembakkan callback QRIS berhasil dan pesanan otomatis beralih ke `Dibayar`.
- `test_manager_can_generate_table_qr_svg`: Endpoint cetak QR Meja (`/manager/tables/{table}/qr.svg`) menghasilkan berkas `image/svg+xml` valid.

### 2.4 Kitchen Display System (KDS) & Handover
- `test_kitchen_user_can_access_kds`: Staf kitchen/barista dapat mengakses papan antrean KDS.
- `test_kds_only_shows_paid_orders_not_unpaid`: KDS hanya menampilkan pesanan berstatus `Dibayar`, `Diproses`, atau `Siap`. Pesanan belum bayar dilarang tampil (FR-07, NFR-03).
- `test_kds_feed_strictly_omits_prices_and_revenue`: Endpoint feed real-time KDS strictly tidak mengekspos nominal rupiah, harga satuan, subtotal, maupun total pendapatan (FR-07, NFR-10).
- `test_kitchen_start_processing_order`: Kitchen memulai persiapan pesanan (`Dibayar -> Diproses`).
- `test_kitchen_mark_ready_requires_quality_confirmation`: Penandaan pesanan Siap mewajibkan konfirmasi mutu/kesesuaian pesanan (D11).
- `test_handover_verifies_order_code_and_completes_order`: Penyerahan pesanan (handover) memvalidasi kode 4 karakter (`order_code`) sebelum beralih ke `Selesai` dan mencatat `handed_over_by` (D3).

### 2.5 Invoice Digital
- `test_invoice_automatically_generated_when_order_paid`: Invoice digital dengan nomor `INV-774-YYYYMMDD-xxxx` otomatis diterbitkan seketika status menjadi `Dibayar` (FR-08).
- `test_invoice_delivery_failure_leaves_order_status_intact`: Kegagalan pengiriman invoice (hingga retry maksimal 3 kali) mencatat status `failed` pada delivery, namun status pesanan tetap `Dibayar` (NFR-07).
- `test_pos_kasir_can_resend_invoice`: Kasir dapat mengirim ulang invoice ke email pelanggan baru dari antarmuka POS.
- `test_customer_can_view_invoice_page`: Pelanggan dapat melihat rincian invoice dan mengunduh PDF.

### 2.6 Program Loyalitas & Reward
- `test_points_awarded_only_when_order_is_selesai_by_default`: Poin loyalitas baru dikreditkan saat pesanan berstatus `Selesai` (D4: `loyalty.earn_on_status = Selesai`).
- `test_points_calculation_floor_formula`: Perhitungan 1 poin per kelipatan Rp10.000 subtotal bersih setelah diskon dengan pembulatan ke bawah (floor) (A2).
- `test_guest_and_cancelled_orders_never_produce_points`: Transaksi tamu atau pesanan yang dibatalkan tidak menghasilkan poin (NFR-16).
- `test_reward_redemption_and_insufficient_points_validation`: Penukaran reward mengurangi saldo poin dan menerbitkan voucher aktif. Penukaran dengan saldo tidak cukup ditolak secara aman.
- `test_manager_cancellation_reverses_earned_points`: Pembatalan pesanan yang sudah berpoin oleh Manager memicu pembalikan poin (reversal ledger) (D10).

### 2.7 Portal & Manajemen Akun Member (FR-09, NFR-11)
- `test_member_can_view_dashboard`: Dashboard member menampilkan kode unik member (`MBR-774-xxxx`), nama pengguna, saldo poin, dan segmen RFM terkini.
- `test_member_can_view_loyalty_page`: Halaman loyalitas menampilkan saldo poin, riwayat mutasi poin (`point_ledger`), dan katalog reward aktif.
- `test_member_can_redeem_reward`: Member berhasil menukarkan reward aktif yang memotong saldo poin dan menerbitkan voucher aktif.
- `test_member_can_view_profile`: Halaman profil menampilkan form data diri, form ganti kata sandi, dan persetujuan kepatuhan UU PDP No. 27/2022.
- `test_member_can_update_profile`: Member dapat memperbarui nama lengkap, alamat email, dan nomor telepon secara valid.
- `test_member_can_update_password_successfully`: Member dapat memperbarui kata sandi akun dengan verifikasi kata sandi lama, minimal 8 karakter, dan konfirmasi.
- `test_member_cannot_update_password_with_wrong_current`: Pembaruan kata sandi ditolak dengan pesan kesalahan jika kata sandi lama salah.
- `test_member_can_view_orders_history`: Member dapat meninjau riwayat transaksi pribadinya dengan filter status pesanan.
- `test_member_can_logout`: Member dapat keluar (logout) dari sesi perangkat saat ini secara aman.

### 2.8 Algoritma RFM & Segmentasi Perilaku (NFR-17)
- `test_rfm_recency_score_deterministic_boundary_conditions`: Uji batas skor Recency (R=5 s.d. 1) terhadap ambang batas 7, 14, 30, 60 hari.
- `test_rfm_frequency_score_deterministic_boundary_conditions`: Uji batas skor Frequency (F=5 s.d. 1) terhadap ambang batas 8, 5, 3, 2, 1 pesanan.
- `test_rfm_monetary_score_deterministic_boundary_conditions`: Uji batas skor Monetary (M=5 s.d. 1) terhadap ambang batas Rp1.000.000, Rp600.000, Rp300.000, Rp100.000.
- `test_rfm_segment_matching_against_hand_calculated_fixtures`: Klasifikasi aturan berurutan (*first match wins*) cocok 100% dengan perhitungan manual pada matriks:
  - Pelanggan Setia / Champions
  - Pelanggan Loyal
  - Berpotensi
  - Berisiko / At Risk
  - Tidak Aktif / Inactive
  - Belum Aktif (Frekuensi = 0)

### 2.9 Behavioral Promotion Engine (Enam Aturan)
- `test_comeback_promo_eligible_for_inactive_member`: Promo Comeback aktif bagi member tidak bertransaksi > 30 hari.
- `test_repeat_order_reward_rule`: Reward Repeat Order aktif bagi member dengan >= 5 pesanan dalam 30 hari.
- `test_slow_hour_promo_rule`: Promo Jam Sepi aktif pada jendela waktu 14:00–17:00 hari kerja dan tidak aktif di luar jam tersebut.
- `test_at_most_one_promo_or_reward_per_order_constraint`: Larangan penggabungan promo dan voucher reward dalam satu transaksi pesanan (D14).
- `test_guest_cannot_apply_promotions`: Tamu (Guest) dilarang menggunakan kode promo; sistem mengarahkan pendaftaran member (D13).

### 2.10 Pelaporan & Isolasi Data Multi-Tenant
- `test_report_service_revenue_equals_paid_orders_sum`: Total pendapatan operasional persis sama dengan jumlah `orders.total_amount` untuk status `Dibayar`, `Diproses`, `Siap`, dan `Selesai`. Pesanan dibatalkan atau belum bayar dikeluarkan dari total (Section 0.3).
- `test_only_manager_and_owner_can_access_reports`: Hanya Manager dan Owner yang memiliki akses ke laporan penjualan dan pendapatan (FR-13).
- `test_customer_data_isolation_returns_404_on_unauthorized_order`: Member A dilarang melihat pesanan/invoice Member B dan menerima respon HTTP 404 (bukan 403) untuk mencegah *resource enumeration* (Section 5.3).
