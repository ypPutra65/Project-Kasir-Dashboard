# PRODUCT REQUIREMENT DOCUMENT & ROADMAP PROMPT
## Sistem Point of Sale (POS) & Monitoring Restoran "Budhe Lamongan"
**Stack:** Laravel 13 | Filament Admin (v3/v4) | MySQL 8.x | Livewire  
**Model Operasional:** Single User / Owner-Operator (Owner merangkap sebagai Kasir tunggal)

---

# BAGIAN 1: PRODUCT REQUIREMENT DOCUMENT (PRD)

## 1. Ringkasan Eksekutif & Karakteristik Arsitektur
Restoran **Budhe Lamongan** beralih dari operasional manual ke sistem digital terintegrasi berbasis web. Seluruh aktivitas operasional—mulai dari melayani pelanggan di meja kasir, memantau ketersediaan stok porsi, hingga menganalisis laba kotor harian—dikelola langsung oleh satu orang (Owner).

Karakteristik utama arsitektur ini:
* **Single Role / Single Panel:** Tidak memerlukan RBAC bertingkat (*multi-role*). Satu panel Filament melayani alur kasir cepat (*fast-checkout*) sekaligus ringkasan analitik bisnis.
* **Operasional Berorientasi Kecepatan:** Antarmuka kasir dirancang minim klik, mendukung tombol pintas pecahan tunai, dan cetak struk otomatis.
* **Integritas Transaksi:** Pengurangan stok dieksekusi secara atomik menggunakan *pessimistic locking* guna menjamin keakuratan stok bahan/porsi.

---

## 2. Navigasi & Struktur Panel

Panel Filament dikonfigurasi dengan prioritas urutan akses berikut:

| Urutan | Navigasi | Tipe Komponen | Deskripsi Fungsional |
| :---: | :--- | :--- | :--- |
| **1** | **Kasir (POS)** | `Custom Page` | Halaman kerja utama saat warung beroperasi (grid menu & keranjang pesanan). |
| **2** | **Dashboard** | `Dashboard Page` | Ringkasan metrik harian: omset, kas laci, QRIS, estimasi laba kotor, dan grafik transaksi. |
| **3** | **Riwayat Transaksi** | `Resource (Order)` | Audit nota penjualan, cetak ulang struk, dan pembatalan pesanan (*void*). |
| **4** | **Menu Makanan & Minuman** | `Resource (Product)` | Kelola harga jual, modal HPP, gambar, batas peringatan stok, dan penyesuaian porsi. |
| **5** | **Kategori Menu** | `Resource (Category)` | Pengelompokan katalog menu (Makanan Utama, Lauk Tambahan, Minuman, Paket). |

---

## 3. Spesifikasi Fitur Terperinci

### 3.1 Manajemen Menu & Kategori
* **Kategori Menu:** Nama kategori, slug unik, nomor urut prioritas (*sort order*), dan sakelar status aktif/nonaktif.
* **Item Menu:**
  * Nama produk, foto makanan, relasi kategori.
  * **Harga Pokok Penjualan (HPP / Biaya Dasar):** Nilai modal bahan per porsi untuk kalkulasi laba.
  * **Harga Jual Kasir:** Nilai transaksi ke konsumen.
  * **Stok Porsi & Batas Kritis:** Kuantitas porsi siap saji dan ambang batas peringatan (*low stock alert*).
  * Status otomatis nonaktif pada layar kasir jika stok porsi bernilai 0.

### 3.2 Modul Kasir (POS Screen)
* **Tata Letak Layar:** 
  * Area katalog menu dengan filter tab kategori, kolom pencarian cepat, serta indikator visual stok porsi yang menipis atau habis.
  * Area keranjang pesanan (*order summary cart*) interaktif untuk modifikasi kuantitas porsi, subtotal, dan pembatalan item.
* **Proses Pembayaran:**
  * **Non-Tunai (QRIS / Transfer Bank):** Nilai pembayaran otomatis disesuaikan tepat dengan total tagihan (*exact match*).
  * **Tunai (Cash):** Kolom input uang diterima disertai tombol pintas pecahan nominal riil (Rp 10.000, Rp 20.000, Rp 50.000, Rp 100.000, dan Uang Pas) dengan kalkulasi nilai kembalian seketika.
* **Cetak Struk Thermal:** Kompatibilitas pencetakan langsung pada kertas struk 58mm/80mm tanpa komponen grafis berlebih.

### 3.3 Manajemen & Mutasi Stok
* Setiap transaksi penjualan yang sukses otomatis memotong stok produk dan merekam log mutasi tipe `sale`.
* Form penyesuaian stok instan untuk operasional warung:
  * `in`: Penambahan stok saat belanja bahan baku harian.
  * `waste`: Pengurangan porsi akibat basi, rusak, atau sisa tak layak jual.
  * `adjustment`: Penyesuaian fisik (*stock opname*).

### 3.4 Dashboard & Analitik Harian
* **Rekonsiliasi Kas Tutup Warung:** Menampilkan perbandingan nominal uang fisik di laci kasir (*Cash*) versus saldo uang masuk di rekening/e-wallet (*QRIS/Transfer*).
* **Estimasi Margin Laba Kotor Harian:**
  $$\text{Laba Kotor} = \text{Total Penjualan Hari Ini} - \sum (\text{Qty Terjual} \times \text{HPP})$$
* **Distribusi Penjualan per Jam:** Grafik batang/garis untuk mendeteksi jam puncak kunjungan pelanggan (*peak hours*).
* **Menu Terlaris:** Peringkat 5 menu paling laku berdasarkan volume porsi dan kontribusi nominal rupiah harian.

---

## 4. Skema Database (MySQL)

```sql
-- Tabel Kategori Menu
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Tabel Menu / Produk
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL,
    image_path VARCHAR(255) NULL,
    stock INT NOT NULL DEFAULT 0,
    min_stock_alert INT NOT NULL DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tabel Riwayat Mutasi Stok
CREATE TABLE stock_mutations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    type ENUM('in', 'waste', 'adjustment', 'sale') NOT NULL,
    quantity INT NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabel Transaksi Penjualan
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) NOT NULL,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'qris', 'transfer') NOT NULL,
    status ENUM('paid', 'cancelled') NOT NULL DEFAULT 'paid',
    ordered_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_orders_ordered_at (ordered_at),
    INDEX idx_orders_status (status)
);

-- Tabel Rincian Item Penjualan
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);