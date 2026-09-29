# ROADMAP SISTEM POS & MONITORING "BUDHE LAMONGAN"
**Stack Utama:** Laravel 13 | Filament Admin (v3/v4) | MySQL 8.x | Livewire  
**Model Operasional:** Single-User / Owner-Operator (Owner merangkap Kasir tunggal)

---

## 1. Overview & Fase Implementasi

Roadmap ini membagi pembangunan sistem menjadi 6 fase terstruktur. Fokus utama adalah kecepatan transaksi (*fast checkout*), keandalan stok berbasis database atomik, dan kemudahan monitoring omset harian bagi owner.
---

## 2. Rincian Fase Pengerjaan

### Fase 1: Setup Proyek, Konfigurasi Filament, & Skema Basis Data
Mempersiapkan lingkungan instalasi, konfigurasi regional, pembuatan skema tabel, dan penetapan relasi data.

*   [ ] **1.1 Inisialisasi Environment**
    *   Setup Laravel 13 dan koneksi database MySQL (`budhe_lamongan_pos`).
    *   Instalasi & integrasi paket Filament Admin Panel.
    *   Konfigurasi file `.env` dan `config/app.php`: Timezone `Asia/Jakarta`, Locale `id`.
    *   Pembuatan akun tunggal Owner via CLI / seeder.
*   [ ] **1.2 Migrasi Basis Data**
    *   `categories`: Identifikasi kelompok menu (makanan, minuman, sambal, paket).
    *   `products`: Data menu, HPP (`cost_price`), harga jual (`selling_price`), stok porsi, dan alert stok.
    *   `stock_mutations`: Pencatatan log masuk (`in`), rusak/basi (`waste`), koreksi (`adjustment`), dan penjualan (`sale`).
    *   `orders`: Header transaksi, nomor nota unik, total belanja, metode pembayaran, dan status nota.
    *   `order_items`: Detail transaksi per menu dengan snapshot harga modal dan harga jual saat transaksi terjadi.
    *   Pemasangan database index pada `order_number`, `ordered_at`, dan `status`.
*   [ ] **1.3 Pemodelan Eloquent & Seeding**
    *   Definisi relasi antar model (`Category` 1-N `Product`, `Product` 1-N `OrderItem`, `Order` 1-N `OrderItem`).
    *   Penetapan array `$fillable`, model casting tipe data (`decimal`, `datetime`, `boolean`).
    *   Pembuatan `CategorySeeder` & `ProductSeeder` menu khas Lamongan (Lele, Bebek, Ayam, Lalapan, Minuman).

---

### Fase 2: Manajemen Katalog & Penyesuaian Stok (CRUD Resources)
Membangun panel pengelolaan menu dan mutasi stok harian untuk persiapan sebelum warung buka.

*   [ ] **2.1 Resource Kategori Menu (`CategoryResource`)**
    *   Form input nama kategori dengan auto-slug generator.
    *   Pengaturan urutan tampil (*sort order*) dan switch status aktif/nonaktif.
    *   Tabel ringkasan kategori disertai kalkulasi jumlah menu aktif.
*   [ ] **2.2 Resource Menu Makanan & Minuman (`ProductResource`)**
    *   Form input 2 kolom: relasi kategori, nama menu, upload foto, HPP (modal), harga jual, dan stok porsi awal.
    *   Tabel katalog produk dengan badge status stok (normal, peringatan menipis, atau habis).
    *   Filter tabel berdasarkan kategori dan filter cepat item stok menipis (`stock <= min_stock_alert`).
*   [ ] **2.3 Aksi Mutasi Stok Porsi**
    *   Modal action pada tabel produk untuk *Input Stok Cepat*.
    *   Mencatat penambahan stok belanja pagi (`in`) atau porsi basi/rusak saat tutup (`waste`).
    *   Eksekusi perubahan stok produk dan pembuatan catatan mutasi di dalam `DB::transaction`.

---

### Fase 3: Antarmuka Kasir (POS Page) & Mesin Transaksi
Membangun antarmuka kasir cepat berbasis Livewire di dalam Filament untuk melayani antrean pelanggan tanpa hambatan.

*   [ ] **3.1 Layout Halaman Kasir Kustom (`PosPage`)**
    *   Konfigurasi halaman Filament *full-width* tanpa sidebar yang membatasi area kerja kasir.
    *   Split layout:
        *   **Sisi Kiri (65%):** Tab filter kategori, kotak pencarian menu instan, dan kartu menu responsif (foto, nama, harga, stok). Menu dengan stok 0 otomatis nonaktif.
        *   **Sisi Kanan (35%):** Panel keranjang (*cart*), kontrol kuantitas (+/-), rincian subtotal per baris, dan total tagihan.
*   [ ] **3.2 Logika Checkout & Validasi Stok Atomik**
    *   Implementasi logika checkout dengan transaksi database atomik:
        *   Proteksi *race condition* dengan `Product::lockForUpdate()`.
        *   Validasi kecukupan sisa stok riil di database.
        *   Insert ke tabel `orders` dengan generator kode nota: `BL-YYYYMMDD-XXXX`.
        *   Insert snapshot item ke `order_items`.
        *   Pemotongan kuantitas `product.stock` dan pencatatan mutasi tipe `sale`.
    *   Metode pembayaran:
        *   *Non-Tunai (QRIS / Transfer):* Nilai bayar otomatis disamakan dengan total tagihan.
        *   *Tunai (Cash):* Input uang tunai dengan tombol pintas pecahan cepat (Rp 10.000, 20.000, 50.000, 100.000, dan Uang Pas) serta kalkulasi kembalian otomatis.
*   [ ] **3.3 Modul Pencetakan Struk Thermal**
    *   Template struk nota kasir format 58mm / 80mm dengan CSS `@media print`.
    *   Trigger event JavaScript `window.print()` otomatis sesaat setelah checkout tervalidasi.

---

### Fase 4: Riwayat Pemesanan (Order Management & Audit)
Menyediakan modul pelacakan riwayat transaksi untuk keperluan audit, cetak ulang nota, atau pembatalan transaksi yang keliru.

*   [ ] **4.1 Resource Pemesanan (`OrderResource`)**
    *   Nonaktifkan fitur Create manual (order wajib dibuat melalui layar POS).
    *   Tabel riwayat pesanan: nomor nota, waktu transaksi, total belanja, metode bayar (badge warna), dan status nota.
    *   Filter transaksi berdasarkan rentang tanggal, status order, dan metode pembayaran.
*   [ ] **4.2 Tindakan Nota (Order Actions)**
    *   Slide-over modal untuk melihat rincian item pesanan.
    *   Tindakan cetak ulang (*re-print*) struk thermal.
    *   Tindakan pembatalan (*void order*): mengubah status ke `cancelled` dan mengembalikan (*restore*) stok porsi ke tabel produk.

---

### Fase 5: Dashboard Monitoring & Analitik Harian
Menyediakan visibilitas performa bisnis, uang kas fisik vs bank, serta keuntungan harian.

*   [ ] **5.1 Widget Rekonsiliasi Kas & Metrik Kunci (`StatsOverviewWidget`)**
    *   **Total Omset Hari Ini:** Nilai rupiah dari seluruh transaksi sukses pada tanggal berjalan.
    *   **Uang Kas Fisik di Laci:** Akumulasi transaksi metode tunai (`cash`) untuk rekonsiliasi tutup warung.
    *   **Uang Masuk Rekening / QRIS:** Akumulasi pembayaran digital (`qris` & `transfer`).
    *   **Estimasi Laba Kotor:** Hasil kalkulasi $(\text{Total Omset} - \sum (\text{Qty Terjual} \times \text{HPP}))$.
*   [ ] **5.2 Widget Analitik Grafis & Menu Terlaris**
    *   `HourlySalesChart`: Grafik batang/garis distribusi omset per jam (09.00 - 23.00) untuk evaluasi *peak hours*.
    *   `TopSellingProductsWidget`: Tabel peringkat 5 menu paling laku berdasarkan volume porsi dan kontribusi nominal rupiah.

---

### Fase 6: Pengujian, Optimasi Kinerja, & Finishing
Memastikan kehandalan sistem, kecepatan respon, dan kemudahan akses operasional.

*   [ ] **6.1 Pengujian Fungsional Otomatis (Feature Tests)**
    *   Uji coba kalkulasi subtotal, total akhir, dan kembalian tunai.
    *   Uji coba validasi stok: pencegahan checkout jika stok fisik tidak mencukupi.
    *   Uji coba pembatalan nota: verifikasi integritas pengembalian stok produk.
*   [ ] **6.2 Optimasi Kueri & Navigasi Antarmuka**
    *   Pencegahan issue *N+1 Query* pada halaman POS dan riwayat order dengan *eager loading*.
    *   Penyusunan urutan sidebar Filament (POS Kasir diletakkan pada posisi paling atas, diikuti Dashboard, Riwayat Order, Menu, dan Kategori).
    *   Pemasangan tombol pintas keyboard (*hotkeys*) pada kasir (misal: `F2` untuk fokus cari menu, `F9` untuk membuka modal pembayaran).

---

## 3. Estimasi Jadwal & Milestone Pengerjaan

| Tahapan | Deliverable Kunci | Estimasi Waktu |
| :--- | :--- | :---: |
| **Milestone 1** | Skema Database, Model Eloquent, Seeder, & Filament Base Setup | Hari 1 |
| **Milestone 2** | CRUD Kategori, CRUD Menu (HPP & Harga Jual), dan Mutasi Stok | Hari 2 |
| **Milestone 3** | Antarmuka Kasir (POS Screen), Checkout Atomik, & Cetak Struk Thermal | Hari 3 - 4 |
| **Milestone 4** | Riwayat Transaksi, Fitur Void, & Filter Pembayaran | Hari 5 |
| **Milestone 5** | Dashboard Omset Harian, Rekonsiliasi Kas/QRIS, & Laba Kotor | Hari 6 |
| **Milestone 6** | Eager Loading, Hotkeys, Testing Transaksi, & UAT Operasional | Hari 7 |