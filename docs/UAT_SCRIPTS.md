# 774 HUB — Panduan Skenario Pengujian Penerimaan Pengguna (UAT Scripts)

Dokumen ini berisi panduan skenario pengujian langkah-demi-langkah (*step-by-step User Acceptance Testing*) untuk 6 aktor sistem 774 HUB.

---

## Data Kredensial Staf & Akun Uji Coba

| Peran (Role) | Email Masuk | Kata Sandi | Halaman Utama |
|---|---|---|---|
| **Kasir** | `kasir@774hub.test` | `password` | `/pos` |
| **Kitchen / Barista** | `kitchen@774hub.test` | `password` | `/kds` |
| **Manajer** | `manager@774hub.test` | `password` | `/manager` |
| **Owner** | `owner@774hub.test` | `password` | `/owner` |
| **Member Contoh** | `member1@774hub.test` / `081200000001` | `password` | `/member` |

---

## Skenario UAT 1: Pelanggan Tamu (Guest) — Pemesanan Mandiri via QR Meja

### Tujuan
Menguji alur pemesanan mandiri oleh tamu dari pindai QR meja hingga penyelesaian pembayaran dan pelacakan pesanan (FR-01, FR-04, FR-05, FR-08, NFR-12).

### Langkah Pengujian
1. **Pindai QR Meja:** Buka browser dan akses URL meja contoh: `http://localhost/t/qr_demo_meja_07`.
2. **Pilih Opsi Tamu:** Di halaman landing QR, klik tombol **"Lanjut sebagai Tamu (Tanpa Akun)"**.
3. **Pilih Menu:**
   - Perhatikan badge meja di header ("Meja 07").
   - Pilih kategori **Signature Coffee**.
   - Klik produk **"Kopi Susu Gula Aren 774"** (Rp25.000). Masukkan catatan *"Less sugar"*, lalu klik **"Tambah ke Keranjang"**.
4. **Periksa Keranjang:**
   - Klik ikon **Keranjang** di pojok kanan atas atau bottom nav.
   - Periksa subtotal, catatan item, dan baris pajak (10% untuk demo seed).
   - Klik **"Lanjut ke Checkout"**.
5. **Formulir Checkout:**
   - Nomor Meja: Terisi otomatis **"Meja 07"** secara read-only.
   - Nama Pemesan: Masukkan **"Dimas Prasetyo"**.
   - Email: Masukkan **"dimas@example.com"** (wajib untuk invoice digital).
   - Klik tombol **"Buat Pesanan & Bayar"**.
6. **Halaman Pembayaran:**
   - Sistem menampilkan status **"Menunggu Pembayaran"** dengan Nomor Pesanan (misal: `774-20261114-xxxx`) dan Kode Penyerahan 4 karakter (misal: `7K9P`).
   - Pilih metode **QRIS Digital**.
   - Di lingkungan lokal/demo, klik tombol simulasi **"Simulasikan Pembayaran QRIS Berhasil"**.
7. **Pelacakan & Invoice:**
   - Halaman otomatis beralih ke status **"Dibayar"**.
   - Klik link **"Lihat Invoice Digital"**.
   - Periksa rincian invoice dan klik tombol **"Unduh PDF"**.

---

## Skenario UAT 2: Pelanggan Member — Registrasi, Poin Loyalitas, & Penukaran Reward

### Tujuan
Menguji registrasi member berlandaskan UU PDP No. 27/2022, perolehan poin loyalitas, serta pemakaian voucher reward (FR-02, FR-09, NFR-11).

### Langkah Pengujian
1. **Registrasi Member:**
   - Akses `http://localhost/register`.
   - Isi Nama: **"Ayu Safitri"**, Email: **"ayu.safitri@example.com"**, Kontak/WA: **"081299887766"**, Password: **"password123"**.
   - Coba submit tanpa mencentang persetujuan data pribadi -> Sistem wajib menolak dengan pesan validasi UU PDP.
   - Centang checkbox persetujuan UU PDP No. 27/2022, lalu klik **"Daftar Akun Member"**.
2. **Portal Member:**
   - Sistem mengarahkan ke `/member`.
   - Periksa kartu profil: Kode Member otomatis dibuat (misal `MBR-774-0041`), Saldo Poin awal `0 Poin`.
3. **Katalog Reward:**
   - Klik menu **"Tukar Poin"** (`/member/loyalty`).
   - Periksa katalog reward (misal: Voucher Potongan Rp10.000, Free Upgrade Size).
4. **Pemesanan Member & Poin:**
   - Lakukan pemesanan via meja (`/t/qr_demo_meja_02`) saat login sebagai Member.
   - Perhatikan nama dan email terisi otomatis dari profil.
   - Selesaikan pembayaran tunai/QRIS.
   - Setelah pesanan diserahkan (status **Selesai**), periksa saldo poin di `/member`. Poin bertambah sebesar 1 poin per kelipatan Rp10.000 subtotal bersih (A2).

---

## Skenario UAT 3: Kasir — POS Manual, Konfirmasi Tunai, & Resend Invoice

### Tujuan
Menguji modul POS untuk pembuatan pesanan manual langsung di kasir, verifikasi uang tunai dengan proteksi double-submit, dan pengiriman ulang invoice (FR-05, FR-06, FR-08, NFR-06).

### Langkah Pengujian
1. **Login Staf Kasir:**
   - Buka `/staff/login`, masukkan `kasir@774hub.test` / `password`.
   - Sistem mengarahkan ke dashboard POS `/pos`.
2. **Konfirmasi Pembayaran Tunai (Antrean Kasir):**
   - Di tab **"Menunggu Pembayaran"** (`/pos/payments`), temukan pesanan berstatus Menunggu Pembayaran.
   - Klik tombol **"Konfirmasi Tunai"**.
   - Masukkan nominal uang yang diterima (misal total tagihan Rp55.000, kasir input Rp100.000).
   - Sistem menampilkan hitungan kembalian Rp45.000. Klik **"Simpan & Konfirmasi Lunas"**.
   - Pesanan seketika berpindah ke status **"Dibayar"** dan invoice digital otomatis diterbitkan.
3. **Input Pesanan Manual di Kasir:**
   - Klik menu **"Input Pesanan Manual"** (`/pos/orders/create`).
   - Pilih jenis: **Dine In** (pilih Meja 03) atau **Takeaway**.
   - Pilih produk dan atur jumlah.
   - Selesaikan transaksi langsung di kasir.
4. **Kirim Ulang Invoice:**
   - Buka menu **"Invoice Digital"** (`/pos/invoices`).
   - Cari nomor pesanan atau nama pelanggan.
   - Klik tombol **"Kirim Ulang"**, pilih kanal **Email**, masukkan email tujuan baru, lalu klik **"Kirim"**.
   - Sistem mencatat riwayat pengiriman baru tanpa mengubah status pesanan.

---

## Skenario UAT 4: Kitchen / Barista — Papan Antrean KDS & Penyerahan Mutu

### Tujuan
Menguji operasional dapur tanpa rekonsiliasi manual, alur verifikasi kualitas, dan penyerahan pesanan dengan validasi kode 4 karakter (FR-07, NFR-03, NFR-10).

### Langkah Pengujian
1. **Login Barista/Kitchen:**
   - Akses `/staff/login`, masuk dengan `kitchen@774hub.test` / `password`.
   - Sistem membuka antarmuka layar penuh Kitchen Display System `/kds`.
2. **Pemeriksaan Kerahasiaan (Zero Price Exposure):**
   - Pastikan pada seluruh kartu pesanan di KDS tidak tercantum nominal harga rupiah atau total omzet penjualan (NFR-10).
   - Pastikan pesanan yang belum dibayar (*Menunggu Pembayaran*) **tidak muncul** di KDS.
3. **Mulai Memproses Pesanan:**
   - Pada kolom 1 (**Pesanan Baru / Dibayar**), klik tombol **"Mulai Proses"**.
   - Kartu pesanan otomatis berpindah ke Kolom 2 (**Sedang Dikerjakan / Diproses**).
4. **Tandai Siap (Quality Check Loop D11):**
   - Pada kartu pesanan di kolom 2, klik **"Tandai Siap"**.
   - Sistem menampilkan dialog konfirmasi mutu: *"Pesanan sudah sesuai?"*.
   - Pilih **"Ya, Tandai Siap"**.
   - Kartu pesanan berpindah ke Kolom 3 (**Siap Diserahkan**).
5. **Penyerahan ke Pelanggan (Handover):**
   - Saat pelanggan datang ke pick-up counter, minta 4 karakter kode pesanan (misal `ND8P`).
   - Masukkan kode ke dalam input verifikasi handover di KDS.
   - Klik **"Verifikasi & Serahkan"**.
   - Pesanan selesai dan tersimpan ke riwayat `/kds/history`. Poin loyalitas otomatis dikreditkan ke akun member terkait.

---

## Skenario UAT 5: Manajer — Analisis RFM, Master Promosi, & Pembatalan Terkendali

### Tujuan
Menguji eksekusi perhitungan RFM 90 hari, penyesuaian parameter ambang batas, konfigurasi 6 strategi promosi, dan hak pembatalan pesanan berbayar (FR-10, FR-12, D10).

### Langkah Pengujian
1. **Login Manajer:**
   - Masuk di `/staff/login` menggunakan `manager@774hub.test` / `password`.
   - Sistem membuka dashboard manajer `/manager`.
2. **Jalankan Analisis RFM:**
   - Buka menu **"Analisis RFM"** (`/manager/rfm`).
   - Klik tombol **"Jalankan Analisis Sekarang"**.
   - Periksa ringkasan distribusi segmen: *Pelanggan Setia, Pelanggan Loyal, Berpotensi, Berisiko, Tidak Aktif*.
   - Klik salah satu segmen untuk melihat daftar nama member, skor R-F-M, dan nilai belanja masing-masing.
3. **Pengaturan Parameter Ambang Batas RFM:**
   - Buka menu **"Parameter Scoring RFM"** (`/manager/rfm/parameters`).
   - Ubah ambang batas Recency, Frequency, atau Monetary.
   - Sistem memvalidasi urutan monotonik (R naik, F turun, M turun).
4. **Manajemen Promosi Terarah:**
   - Buka `/manager/promotions`.
   - Periksa daftar 6 aturan promosi aktif: *Comeback Promo, Repeat Order Reward, Promo Hari Besar, Welcome/Next Visit, Slow Hour, Promo Return Tracking*.
   - Buat promosi baru berjenis **Slow Hour Promo** dengan target waktu jam 14:00–17:00.
5. **Pembatalan Pesanan Berbayar (D10):**
   - Buka `/manager/transactions`.
   - Pilih transaksi yang berstatus `Dibayar` atau `Diproses`.
   - Klik tombol **"Batalkan Pesanan (Manajer)"**.
   - Masukkan alasan pembatalan: *"Bahan baku espresso blend habis"*.
   - Perhatikan notifikasi resmi: *"Pengembalian dana ditangani di luar sistem"*.
   - Status berubah menjadi `Dibatalkan` dan poin yang telah diperoleh otomatis dibatalkan.

---

## Skenario UAT 6: Owner — Dashboard Eksekutif & Laporan Omzet Penjualan

### Tujuan
Menguji laporan penjualan dan pendapatan operasional dengan filter periode harian, mingguan, bulanan yang bebas dari angka hard-coded (FR-13, Section 0.3).

### Langkah Pengujian
1. **Login Owner:**
   - Masuk di `/staff/login` menggunakan `owner@774hub.test` / `password`.
   - Sistem membuka `/owner`.
2. **Dashboard Eksekutif:**
   - Periksa metrik KPI utama: Pendapatan Hari Ini, Total Transaksi Lunas, Rata-rata Nilai Keranjang (AOV), Proporsi Tunai vs QRIS.
   - Periksa grafik tren pendapatan 30 hari terakhir.
3. **Laporan Penjualan & Pendapatan:**
   - Buka menu **"Laporan Penjualan"** (`/owner/reports/sales`).
   - Ganti filter:
     - **Harian:** Menampilkan rekapitulasi penjualan hari ini per jam.
     - **Mingguan:** Menampilkan rekapitulasi 7 hari terakhir.
     - **Bulanan:** Menampilkan rekapitulasi 30 hari terakhir.
   - Pastikan nominal pendapatan bersih mengecualikan pesanan yang dibatalkan.
