# 774 HUB — Integrated Web Ordering, Point of Sale (POS) & Behavioral Promotion Engine (RFM)

> **Sistem Web-Based Ordering dan Point of Sale Terintegrasi dengan Behavioral Promotion Engine Berbasis RFM pada 774 HUB**  
> Proyek Capstone: Solusi terpadu pemesanan mandiri melalui QR meja pelanggan, kasir POS terintegrasi, layar Kitchen Display System (KDS), invoice digital, program loyalitas poin reward, serta mesin promosi berbasis segmentasi analitik RFM (*Recency, Frequency, Monetary*).

---

## 1. Arsitektur Inti: Transaction as the Hub

Data transaksi (`orders`) berfungsi sebagai **hub sentral** yang menghubungkan seluruh siklus operasional:
1. **Pemesanan Mandiri (QR Meja):** Pelanggan tamu maupun member memindai QR meja, memilih menu, dan memesan secara langsung dengan nomor meja terisi otomatis (*read-only*).
2. **Pembayaran Terintegrasi:** Hanya mendukung **Tunai di Kasir** (diverifikasi oleh kasir melalui POS) dan **QRIS Digital** (terverifikasi otomatis melalui webhook ber-HMAC SHA-256).
3. **Kitchen Display System (KDS):** Antrean pesanan tampil otomatis di layar dapur/barista seketika status transaksi menjadi `Dibayar`, tanpa rekonsiliasi manual atau cetak kertas. KDS *strictly* tidak mengekspos nominal harga atau omzet pendapatan.
4. **Invoice Digital:** Diterbitkan otomatis saat pembayaran lunas, dikirimkan melalui Email/WhatsApp dengan mekanisme *exponential retry* maksimal 3 kali.
5. **Program Loyalitas:** Poin reward dikreditkan saat pesanan berstatus `Selesai` (1 poin per kelipatan Rp10.000 subtotal bersih setelah diskon).
6. **Behavioral Promotion Engine:** Mengklasifikasikan member ke dalam segmen analitik RFM 90-hari secara deterministik untuk mendorong 6 aturan promosi perilaku terarah.
7. **Laporan Penjualan & Pendapatan:** Ringkasan KPI operasional harian, mingguan, dan bulanan berbasis data riil transaksi database.

---

## 2. Enam Aktor Sistem & Kredensial Uji Coba

Sistem menerapkan kontrol akses berbasis peran (*Role-Based Access Control* / RBAC):

| Peran (Role) | Kredensial Masuk | Halaman Utama | Deskripsi Hak Akses |
|---|---|---|---|
| **Guest (Tamu)** | Tanpa login (scan QR meja) | `/t/{qr_token}` | Pemesanan mandiri di meja, checkout tamu, pelacakan status pesanan, lihat invoice digital. |
| **Member** | `member1@774hub.test` / `password` | `/member` | Profil member, katalog reward poin, riwayat transaksi, voucher promo khusus member. |
| **Kasir** | `kasir@774hub.test` / `password` | `/pos` | Pembuatan pesanan manual di kasir, konfirmasi tunai, cetak/kirim ulang invoice digital, toggle ketersediaan menu. |
| **Kitchen / Barista** | `kitchen@774hub.test` / `password` | `/kds` | Papan antrean KDS 3 kolom (*Dibayar*, *Diproses*, *Siap*), konfirmasi mutu pesanan, penyerahan pesanan via kode 4 karakter. |
| **Manager** | `manager@774hub.test` / `password` | `/manager` | Eksekusi kalkulasi RFM, master parameter scoring, manajemen 6 strategi promosi, audit log pembatalan pesanan berbayar. |
| **Owner** | `owner@774hub.test` / `password` | `/owner` | Dashboard eksekutif omzet bisnis, rekapitulasi laporan penjualan/pendapatan operasional harian, mingguan, bulanan. |

---

## 3. Siklus Status Pesanan (*Order Statuses*) & Metode Pembayaran

### 6 Status Transaksi Resmi (Bahasa Indonesia):
1. `Menunggu Pembayaran` — Pesanan berhasil dibuat, menunggu konfirmasi kasir atau penyelesaian QRIS.
2. `Dibayar` — Pembayaran terverifikasi; pesanan otomatis masuk ke antrean KDS dan invoice diterbitkan.
3. `Diproses` — Barista/kitchen mulai meracik dan menyiapkan pesanan.
4. `Siap` — Pesanan selesai disiapkan dan telah lolos uji kesesuaian mutu (*quality check loop*).
5. `Selesai` — Pesanan telah diserahkan ke pelanggan setelah verifikasi kode 4 karakter (`order_code`); poin loyalitas dikreditkan.
6. `Dibatalkan` — Pesanan dibatalkan (dapat dilakukan oleh Pelanggan/Kasir saat *Menunggu Pembayaran*, atau oleh Manajer setelah *Dibayar* dengan alasan wajib).

### 2 Metode Pembayaran Resmi:
- `tunai` (**Tunai di Kasir**) — Dikonfirmasi langsung oleh Kasir pada antarmuka POS.
- `qris` (**QRIS Digital**) — Menggunakan standar QRIS dengan jendela kedaluwarsa 15 menit dan verifikasi webhook aman.

---

## 4. Enam Aturan Behavioral Promotion Engine (RFM)

1. **Comeback Promo (`comeback`):** Memberikan diskon khusus bagi member yang tidak bertransaksi selama > 30 hari untuk mendorong kunjungan kembali.
2. **Repeat Order Reward (`repeat_order`):** Memberikan potongan harga bagi pelanggan setia yang mencapai >= 5 transaksi sukses dalam 30 hari terakhir.
3. **Promo Hari Besar (`hari_besar`):** Promosi yang berlaku secara otomatis hanya pada rentang tanggal hari libur atau perayaan tertentu.
4. **Welcome / Next Visit Promo (`welcome_next_visit`):** Mendorong kunjungan kedua bagi member baru yang baru mendaftar dalam kurun waktu <= 14 hari.
5. **Slow Hour Promo (`slow_hour`):** Meningkatkan transaksi pada jam-jam sepi kafe (misal: Senin–Jumat jam 14:00–17:00).
6. **Promo Return Tracking (`return_tracking`):** Melacak tingkat keberhasilan kembalinya pelanggan (*return tracking*) dalam kurun 14 hari setelah menggunakan promosi *Comeback*.

> **Aturan Pembatasan (Keputusan D14):** Dalam satu transaksi pesanan, pelanggan hanya dapat menggunakan **maksimal 1 promosi ATAU 1 voucher reward**. Tamu (*Guest*) dilarang menggunakan promo (khusus Member, D13).

---

## 5. Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan Lingkungan
- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `sqlite3`, `mbstring`, `openssl`, `curl`, `gd`)
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL Server (XAMPP / Standalone di port 3306)

### Langkah Setup

```bash
# 1. Masuk ke direktori proyek
cd "d:\xampp\htdocs\774 HUB"

# 2. Salin environment file (jika belum ada)
cp .env.example .env

# 3. Jalankan instalasi dependensi PHP & JavaScript (jika belum)
composer install
npm install

# 4. Generate Application Key
php artisan key:generate

# 5. Jalankan Migrasi & Seeder Lengkap (Database db_774_hub)
php artisan migrate:fresh --seed

# 6. Kompilasi aset frontend
npm run build
```

Setelah langkah di atas selesai, aplikasi siap diakses melalui peramban web pada URL lokal:
- **Pemesanan Mandiri Meja 07:** `http://localhost/t/qr_demo_meja_07`
- **Login Staf (Kasir, KDS, Manager, Owner):** `http://localhost/staff/login`
- **Login Member:** `http://localhost/login`
- **Katalog Styleguide & Komponen UI:** `http://localhost/styleguide`

---

## 6. Pengujian Otomatis (*Automated Test Suite*)

Sistem dilengkapi test suite komprehensif menggunakan PHPUnit dan database in-memory SQLite:

```bash
# Menjalankan seluruh pengujian (50 tests, 210 assertions)
php vendor/phpunit/phpunit/phpunit
```

Hasil pengujian otomatis:
- `tests/Feature/AuthTest.php`: 6 tests (RBAC, UU PDP consent, 30-min idle timeout)
- `tests/Feature/OrderingTest.php`: 6 tests (QR token, idempotency, tampering protection)
- `tests/Feature/PaymentTest.php`: 9 tests (State machine, cash confirm, QRIS webhook HMAC)
- `tests/Feature/KdsTest.php`: 6 tests (No prices exposed, quality loop, 4-char handover)
- `tests/Feature/InvoiceTest.php`: 4 tests (Auto invoice, retry max 3, manual resend)
- `tests/Feature/LoyaltyTest.php`: 5 tests (Earn on Selesai, floor formula, reversal)
- `tests/Unit/RfmServiceTest.php`: 4 tests (NFR-17 boundary scores & deterministic segment matching)
- `tests/Feature/PromotionEngineTest.php`: 5 tests (6 promo rules, 1 promo/order constraint)
- `tests/Feature/ReportTest.php`: 3 tests (Real DB queries, tenant data isolation)
- `tests/Feature/ExampleTest.php` & `tests/Unit/ExampleTest.php`: 2 tests
- **Total: 50 Tests, 210 Assertions, 100% Passed.**

---

## 7. Asumsi Teknis (A1–A8)

- **A1:** Jendela waktu pembayaran QRIS = 15 menit, setelah itu berstatus *expired*.
- **A2:** Poin loyalitas = 1 poin per kelipatan Rp10.000 subtotal bersih setelah diskon (*floor*).
- **A3:** Periode analisis RFM = 90 hari terakhir.
- **A4:** Ambang inaktif Promo Comeback = 30 hari. Syarat Repeat Order Reward = 5 pesanan sukses dalam 30 hari.
- **A5:** Maksimal satu promo atau voucher reward per transaksi pesanan.
- **A6:** Status ketersediaan menu dikendalikan secara manual (*toggle* tanpa pelacakan stok bahan baku).
- **A7:** Tarif pajak (*tax rate*) default 0% di produksi, 10% pada data seed demonstrasi.
- **A8:** Keranjang belanja disimpan di sesi server; seluruh total harga dihitung ulang secara mutlak di sisi server saat checkout.

---

## 8. Pertanyaan Terbuka (*Open Questions Q1–Q12*)

1. **Q1 (Tarif Pajak):** Kepastian persentase pajak PB1 (saat ini dapat dikonfigurasi fleksibel di menu Pengaturan).
2. **Q2 (Nilai Poin):** Kepastian rasio konversi poin dan katalog reward akhir.
3. **Q3 (Peran Penyerahan):** Fungsi "Pelayan" saat ini dijalankan oleh staf Kasir atau Kitchen/Barista yang bertugas.
4. **Q4 (Pemicu Poin):** Status pemicu perolehan poin default adalah `Selesai` (dapat diubah ke `Dibayar` di pengaturan).
5. **Q5 (Pembatalan Berbayar):** Kebijakan pengembalian dana transaksi berbayar yang ditangani secara manual di luar sistem oleh Manajer.
6. **Q6 (Akses Tamu ke Promo):** Akses promosi saat ini terbatas untuk Member terdaftar; tamu diarahkan mendaftar akun.
7. **Q7 (Ambang Batas RFM):** Kalibrasi batas hari dan nilai transaksi per segmen kafe.
8. **Q8 (Penyedia QRIS):** Pilihan gateway QRIS produksi (Midtrans, Xendit, atau DOKU).
9. **Q9 (Penyedia Notifikasi):** Gateway WhatsApp resmi (Fonnte, Wablas, atau Twilio).
10. **Q10 (Kalender Hari Libur):** Daftar hari besar spesifik untuk Promo Hari Besar.
11. **Q11 (Jendela Jam Sepi):** Jam sepi kafe (default: Senin–Jumat 14:00–17:00).
12. **Q12 (Format Cetak Struk):** Ukuran kertas struk fisik (58mm vs 80mm).

---

## 9. Indeks Dokumen Terkait

- [docs/TEST_REPORT.md](file:///d:/xampp/htdocs/774%20HUB/docs/TEST_REPORT.md) — Laporan lengkap eksekusi 50 pengujian otomatis.
- [docs/UAT_SCRIPTS.md](file:///d:/xampp/htdocs/774%20HUB/docs/UAT_SCRIPTS.md) — Panduan skenario pengujian penerimaan pengguna untuk 6 peran.
- [docs/KNOWN_LIMITATIONS.md](file:///d:/xampp/htdocs/774%20HUB/docs/KNOWN_LIMITATIONS.md) — Batasan ruang lingkup sistem (*out-of-scope boundaries*).
- [docs/DECISIONS.md](file:///d:/xampp/htdocs/774%20HUB/docs/DECISIONS.md) — Rekonsiliasi 14 keputusan arsitektural (D1–D14).
- [docs/OPEN_QUESTIONS.md](file:///d:/xampp/htdocs/774%20HUB/docs/OPEN_QUESTIONS.md) — Rincian 12 pertanyaan terbuka (Q1–Q12).
- [docs/ERD.md](file:///d:/xampp/htdocs/774%20HUB/docs/ERD.md) — Diagram relasi entitas Mermaid untuk 20 tabel domain.
- [docs/ROUTES.md](file:///d:/xampp/htdocs/774%20HUB/docs/ROUTES.md) — Matriks 127 rute web sistem.
- [docs/PERMISSIONS.md](file:///d:/xampp/htdocs/774%20HUB/docs/PERMISSIONS.md) — Matriks peran dan hak akses (RBAC).
- [docs/PROGRESS.md](file:///d:/xampp/htdocs/774%20HUB/docs/PROGRESS.md) — Pelacak kemajuan proyek (100% selesai).
