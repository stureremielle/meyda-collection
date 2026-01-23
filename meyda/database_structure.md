# Database Structure - MeyDa Collection

Berikut adalah struktur tabel lengkap dari database `meyda_collection` yang digunakan di website ini.

## Core Tables

### 1. `user` (Staff & Admin)
Menyimpan kredensial akun internal (Manajemen/Admin).
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_user` | INT (PK, AI) | Identitas unik user internal. |
| `username` | VARCHAR(50) | Username unik untuk login. |
| `password_hash` | VARCHAR(255) | Hash password (bcrypt). |
| `nama_lengkap` | VARCHAR(100) | Nama lengkap admin/staff. |
| `role` | ENUM('admin', 'staff') | Level akses ke panel kontrol. |
| `last_login` | DATETIME | Jejak waktu terakhir login. |

### 2. `pelanggan` (Customers)
Menyimpan profil pelanggan serta status keamanan akun.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_pelanggan` | INT (PK, AI) | Identitas unik pelanggan. |
| `nama` | VARCHAR(100) | Nama lengkap pelanggan. |
| `email` | VARCHAR(100) | Email login (unik). |
| `password_hash` | VARCHAR(255) | Hash password. |
| `telepon` | VARCHAR(20) | Nomor telepon/WhatsApp. |
| `alamat` | TEXT | Alamat pengiriman utama. |
| `is_active` | TINYINT(1) | Status aktivasi email (0=Belum, 1=Aktif). |
| `activation_token`| VARCHAR(255) | Token unik untuk aktivasi via email. |
| `reset_token` | VARCHAR(255) | Token untuk fitur lupa password. |
| `reset_expires_at`| DATETIME | Batas waktu kadaluwarsa token reset. |

### 3. `kategori_produk`
Digunakan untuk mengelompokkan produk.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_kategori` | INT (PK, AI) | ID kategori. |
| `nama_kategori` | VARCHAR(100) | Label kategori (e.g., Silk, Cotton). |
| `deskripsi` | TEXT | Penjelasan tambahan kategori. |

### 4. `produk`
Pusat data informasi barang yang dijual.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_produk` | INT (PK, AI) | ID produk. |
| `id_kategori` | INT (FK) | Relasi ke `kategori_produk`. |
| `nama_produk` | VARCHAR(255) | Nama barang. |
| `deskripsi` | TEXT | Spesifikasi/detail produk. |
| `harga` | DECIMAL(15,2) | Harga jual barang. |
| `stok` | INT | Sisa stok barang. |
| `gambar` | VARCHAR(255) | Nama file gambar produk. |

### 5. `transaksi` (Orders)
Menyimpan ringkasan pesanan/invoice.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_transaksi` | INT (PK, AI) | Nomor ID pesanan. |
| `id_user` | INT (FK) | ID staff yang menangani (default: system). |
| `id_pelanggan` | INT (FK) | Pelanggan yang melakukan order. |
| `tanggal` | DATETIME | Waktu pesanan dibuat. |
| `total` | DECIMAL(15,2) | Total nilai pesanan. |
| `status` | VARCHAR(20) | Status transaksi (misal: 'paid', 'pending'). |

### 6. `detail_transaksi`
Menyimpan rincian item dalam setiap transaksi.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_detail` | INT (PK, AI) | ID rincian item. |
| `id_transaksi` | INT (FK) | Relasi ke tabel `transaksi`. |
| `id_produk` | INT (FK) | Produk yang dibeli. |
| `qty` | INT | Jumlah unit yang dibeli. |
| `harga_satuan` | DECIMAL(15,2) | Harga item saat transaksi. |
| `subtotal` | DECIMAL(15,2) | Kalkulasi `qty * harga_satuan`. |

### 7. `keranjang` (Persistent Cart)
Digunakan agar keranjang belanja tidak hilang setelah logout.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `id_pelanggan` | INT (FK) | Pemilik keranjang belanja. |
| `id_produk` | INT (FK) | ID produk di keranjang. |
| `qty` | INT | Jumlah unit produk. |

### 8. `laporan` (Summary Statistics)
Tabel agregat untuk data laporan keuangan di dashboard.
| Nama Kolom | Tipe Data | Deskripsi |
| :--- | :--- | :--- |
| `periode_year` | INT (PK) | Tahun rekapitulasi. |
| `periode_month` | INT (PK) | Bulan rekapitulasi. |
| `total_transaksi` | INT | Akumulasi jumlah pesanan. |
| `total_pendapatan`| DECIMAL(15,2) | Total nilai uang masuk. |
| `total_item_terjual`| INT | Total unit barang terjual. |
| `generated_at` | TIMESTAMP | Waktu update data laporan. |

---

## SQL Schema Script

```sql
CREATE DATABASE IF NOT EXISTS meyda_collection;
USE meyda_collection;

CREATE TABLE `user` (
  `id_user` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100),
  `role` ENUM('admin', 'staff') DEFAULT 'staff',
  `last_login` DATETIME
);

CREATE TABLE `pelanggan` (
  `id_pelanggan` INT PRIMARY KEY AUTO_INCREMENT,
  `nama` VARCHAR(100),
  `email` VARCHAR(100) UNIQUE,
  `password_hash` VARCHAR(255),
  `telepon` VARCHAR(20),
  `alamat` TEXT,
  `is_active` TINYINT(1) DEFAULT 0,
  `activation_token` VARCHAR(255),
  `reset_token` VARCHAR(255),
  `reset_expires_at` DATETIME
);

CREATE TABLE `kategori_produk` (
  `id_kategori` INT PRIMARY KEY AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT
);

CREATE TABLE `produk` (
  `id_produk` INT PRIMARY KEY AUTO_INCREMENT,
  `id_kategori` INT,
  `nama_produk` VARCHAR(255),
  `deskripsi` TEXT,
  `harga` DECIMAL(15,2),
  `stok` INT,
  `gambar` VARCHAR(255),
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori_produk`(`id_kategori`)
);

CREATE TABLE `transaksi` (
  `id_transaksi` INT PRIMARY KEY AUTO_INCREMENT,
  `id_user` INT,
  `id_pelanggan` INT,
  `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `total` DECIMAL(15,2),
  `status` VARCHAR(20),
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan`(`id_pelanggan`)
);

CREATE TABLE `detail_transaksi` (
  `id_detail` INT PRIMARY KEY AUTO_INCREMENT,
  `id_transaksi` INT,
  `id_produk` INT,
  `qty` INT,
  `harga_satuan` DECIMAL(15,2),
  `subtotal` DECIMAL(15,2),
  FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi`(`id_transaksi`) ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`)
);

CREATE TABLE `keranjang` (
  `id_pelanggan` INT,
  `id_produk` INT,
  `qty` INT,
  PRIMARY KEY (`id_pelanggan`, `id_produk`),
  FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan`(`id_pelanggan`) ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`) ON DELETE CASCADE
);

CREATE TABLE `laporan` (
  `periode_year` INT,
  `periode_month` INT,
  `total_transaksi` INT DEFAULT 0,
  `total_pendapatan` DECIMAL(15,2) DEFAULT 0,
  `total_item_terjual` INT DEFAULT 0,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`periode_year`, `periode_month`)
);
```
