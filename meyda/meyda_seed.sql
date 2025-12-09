-- meyda_seed.sql
USE meyda_collection;
START TRANSACTION;

-- users (admin + one staff) - replace password hashes with real ones later
INSERT INTO `user` (username, password_hash, nama_lengkap, role)
VALUES
  ('admin', '$2y$10$replace_with_real_hash_admin', 'Admin MeyDa', 'admin'),
  ('kasir1', '$2y$10$replace_with_real_hash_kasir', 'Kasir Satu', 'staff');

-- customers
-- Note: password_hash values below are placeholders. Replace with real bcrypt hashes or let users register.
INSERT INTO pelanggan (nama, email, password_hash, telepon, alamat)
VALUES
  ('Siti Nur', 'siti@example.com', '$2y$10$replace_with_real_hash_siti', '081234567890', 'Jl. Melati No.1'),
  ('Budi Santoso', 'budi@example.com', '$2y$10$replace_with_real_hash_budi', '081298765432', 'Jl. Kenanga No.5');

-- categories
INSERT INTO kategori_produk (nama_kategori, deskripsi)
VALUES
  ('Baju Wanita', 'Atasan dan kemeja wanita'),
  ('Baju Pria', 'Atasan dan kemeja pria'),
  ('Aksesoris', 'Topi, tas, dan aksesoris');

-- a small number of products (just enough)
INSERT INTO produk (nama_produk, id_kategori, deskripsi, harga, stok, gambar)
VALUES
  ('Kemeja Putih Wanita', 1, 'Kemeja katun, cocok untuk kerja', 149000.00, 20, NULL),
  ('Kemeja Polos Pria', 2, 'Kemeja casual pria', 129000.00, 30, NULL),
  ('Topi Casual', 3, 'Topi one-size', 45000.00, 40, NULL);

COMMIT;
