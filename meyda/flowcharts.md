# System Flow Diagrams - MeyDa Collection

Berikut adalah visualisasi alur sistem utama menggunakan Mermaid diagram.

## 1. Login Flow (Customer)
Alur ini menjelaskan proses masuknya pelanggan ke dalam sistem, mulai dari pengisian form hingga pengalihan halaman.

```mermaid
graph TD
    A[Start: Halaman Login] --> B{Input Email & Password}
    B -- Valid Format --> C[auth.php: customerLogin]
    B -- Form Kosong --> A
    C --> D{Cek Email di Database}
    D -- Ditemukan --> E{Verifikasi Password Hash}
    D -- Tidak Ada --> F[Error: Akun Tidak Ditemukan]
    E -- Match --> G{Cek Status Aktivasi}
    E -- No Match --> H[Error: Password Salah]
    G -- Aktif (is_active=1) --> I[Set Session & Merge Cart]
    G -- Belum Aktif --> J[Error: Silakan Aktivasi Email]
    I --> K[Redirect ke Beranda/Tujuan]
    F --> A
    H --> A
    J --> A
```

---

## 2. Transaction Flow (Checkout)
Alur proses pembelian mulai dari keranjang belanja hingga konfirmasi pembayaran dan pengiriman resi.

```mermaid
graph TD
    A[Start: Halaman Keranjang] --> B{Cek Login Customer}
    B -- Belum Login --> C[Redirect ke Login]
    B -- Sudah Login --> D{Cek Kelengkapan Alamat}
    D -- Kosong --> E[Form Input Alamat]
    E -- Simpan --> D
    D -- Lengkap --> F[Pilih Metode Pembayaran]
    F -- Simulasi CC/VA --> G[Action: Checkout]
    G --> H[db: START TRANSACTION]
    H --> I[Simpan Header: transaksi]
    I --> J[Simpan Rincian: detail_transaksi]
    J --> K[Update Stok Produk]
    K --> L[Update Tabel: laporan]
    L --> M[db: COMMIT]
    M --> N[Kirim Email Resi (OrderMailer)]
    N --> O[Kosongkan Keranjang]
    O --> P[Selesai: Halaman Sukses]
```

---

## 3. Admin CRUD Flow (Product Management)
Alur bagaimana pengelola (Admin/Staff) mengelola data produk yang dijual.

```mermaid
graph LR
    A[Dashboard Admin] --> B[Kelola Produk]
    B --> C{Pilih Aksi}
    C -- List --> D[Tampil Semua Produk]
    C -- Tambah --> E[Isi Form & Upload Gambar]
    C -- Edit --> F[Update Data & Gambar Baru]
    C -- Hapus --> G{Cek Referensi Transaksi}
    
    E --> H[Validasi & Upload ke /uploads]
    H --> I[Simpan ke db: produk]
    F --> J[Hapus Gambar Lama & Simpan Baru]
    J --> K[Update db: produk]
    
    G -- Ada --> L[Gagal: Masih Ada Transaksi]
    G -- Ada + Force --> M[Hapus Detail Transaksi]
    M --> N[Hapus Produk & Gambar]
    G -- Tidak Ada --> N
    
    I --> D
    K --> D
    N --> D
```
