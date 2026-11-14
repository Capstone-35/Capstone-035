# 774 HUB — Batasan Sistem & Ruang Lingkup (Known Limitations & Scope Boundaries)

Dokumen ini mendokumentasikan batasan sistem yang disepakati secara eksplisit dalam **Master Prompt Section 0.4** dan rancangan sistem 774 HUB.

---

## 1. Batasan Ruang Lingkup (Out of Scope — Sengaja Tidak Dibangun)

Sesuai kesepakatan formal pada Master Prompt Bab 0.4, fitur-fitur berikut **secara sengaja tidak dibangun** karena berada di luar lingkup fungsional sistem web ordering & POS terintegrasi:

1. **Manajemen Stok Bahan Baku (Raw Material / Inventory):**
   - Sistem tidak melakukan pelacakan resep (*bill of materials*), pengurangan stok gramatur biji kopi, susu, sirup, atau bahan baku lainnya.
   - Ketersediaan menu dikendalikan secara manual melalui tombol toggle *Tersedia / Habis* pada modul POS dan KDS (Asumsi A6).
2. **Integrasi Driver Printer Termal Perangkat Keras (ESC/POS Raw Driver):**
   - Sistem tidak mengakses port serial USB/Bluetooth printer termal secara low-level.
   - Pencetakan struk dan invoice digital dilakukan menggunakan fungsi standar web *print dialog* (`window.print()`) dan pengunduhan dokumen PDF (`DomPDF`).
3. **Metode Pembayaran Kartu, Virtual Account, & Direct E-Wallet:**
   - Metode pembayaran operasional strictly dibatasi pada **Tunai di Kasir** dan **QRIS Digital** (Keputusan D5).
4. **Mesin Pengembalian Dana Otomatis (Automated Refunds Engine):**
   - Sistem tidak memproses pengembalian dana saldo (*disbursement*) kembali ke rekening bank atau dompet digital pelanggan.
   - Pembatalan transaksi berbayar hanya dapat dilakukan oleh Manajer dengan alasan wajib dan pemberitahuan resmi bahwa *"Pengembalian dana ditangani di luar sistem"* (Keputusan D10).
5. **Machine Learning / AI Clustering / Black-box Churn Prediction:**
   - Analisis RFM dan segmentasi perilaku pelanggan menggunakan formula matematis deterministik dan aturan ambang batas berbasis aturan (*rule-based*) agar hasil segmentasi dapat diverifikasi 100% terhadap perhitungan manual (NFR-17).
6. **Arsitektur Multi-Outlet / Multi-Cabang:**
   - Sistem dirancang untuk satu outlet kafe (774 HUB). Data transaksi, meja, dan operasional terkonsentrasi pada satu toko.
7. **Akuntansi Lengkap (Buku Besar, Neraca, Laba/Rugi Akrual):**
   - Laporan keuangan yang disajikan berfokus pada **pendapatan operasional penjualan kasir** (*operational revenue & sales summary*), bukan laporan akuntansi akrual, perpajakan formal, atau penggajian karyawan (*payroll*).

---

## 2. Asumsi Teknis & Parameter Operasional (Assumptions A1–A8)

| ID | Parameter / Asumsi | Nilai Default | Penjelasan |
|---|---|---|---|
| **A1** | Jendela Waktu QRIS | 15 Menit | Transaksi QRIS yang tidak diselesaikan dalam 15 menit otomatis berstatus `expired`. |
| **A2** | Perolehan Poin Loyalitas | 1 Poin per Rp10.000 | Dihitung dari nilai subtotal bersih setelah diskon dengan pembulatan ke bawah (*floor*). |
| **A3** | Periode Analisis RFM | 90 Hari | Perhitungan Recency, Frequency, dan Monetary mencakup transaksi berhasil dalam 90 hari terakhir. |
| **A4** | Ambang Batas Inaktif Promo Comeback | 30 Hari | Member yang tidak bertransaksi selama > 30 hari berhak mendapatkan Promo Comeback. |
| **A5** | Aturan Promosi & Reward | Maksimal 1 per Pesanan | Pelanggan hanya dapat menggunakan 1 promosi ATAU 1 voucher reward dalam satu pesanan (Keputusan D14). |
| **A6** | Status Menu | Manual Toggle | Ketersediaan menu dikendalikan oleh Kasir/Kitchen/Manager melalui antarmuka sistem. |
| **A7** | Tarif Pajak (*Tax Rate*) | 0% Produksi, 10% Demo | Dapat dikonfigurasi fleksibel di menu Pengaturan; baris Pajak hanya tampil jika tarif > 0% (Keputusan D2). |
| **A8** | Keranjang Belanja Pelanggan | Sesi Server (*Server-Side Session*) | Nilai total pesanan selalu dihitung ulang di sisi server saat proses checkout untuk mencegah manipulasi harga dari peramban. |
