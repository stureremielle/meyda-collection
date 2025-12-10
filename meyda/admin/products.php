<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();
$error = null;
$success = null;

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Handle delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    try {
        // Delete product image if exists
        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id_produk = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        if ($product && !empty($product['gambar'])) {
            $imgPath = $uploadsDir . '/' . $product['gambar'];
            if (file_exists($imgPath)) unlink($imgPath);
        }
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
        $stmt->execute([':id' => $id]);
        $success = 'Produk dihapus.';
    } catch (Exception $e) {
        $error = 'Tidak bisa menghapus: ' . $e->getMessage();
    }
}

// Handle add/edit with image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $kategori = (int)($_POST['kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $gambar = null;

    if (empty($nama) || $kategori <= 0 || $harga <= 0) {
        $error = 'Nama, kategori, dan harga harus diisi dengan benar.';
    } else {
        // Handle image upload
        if (!empty($_FILES['gambar']['name'])) {
            $file = $_FILES['gambar'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Gambar terlalu besar (max 2MB).';
            } else {
                try {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFilename = 'product_' . time() . '_' . uniqid() . '.' . strtolower($ext);
                    if (move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $newFilename)) {
                        $gambar = $newFilename;
                    } else {
                        $error = 'Gagal mengunggah gambar.';
                    }
                } catch (Exception $e) {
                    $error = 'Kesalahan upload: ' . $e->getMessage();
                }
            }
        }

        if (empty($error)) {
            try {
                if ($id > 0) {
                    // If new image uploaded, delete old one
                    if ($gambar) {
                        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id_produk = :id");
                        $stmt->execute([':id' => $id]);
                        $oldProduct = $stmt->fetch();
                        if ($oldProduct && !empty($oldProduct['gambar'])) {
                            $oldPath = $uploadsDir . '/' . $oldProduct['gambar'];
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                        $stmt = $pdo->prepare("UPDATE produk SET nama_produk=:nama, id_kategori=:kat, deskripsi=:desk, harga=:harga, stok=:stok, gambar=:gambar WHERE id_produk=:id");
                        $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok, ':gambar' => $gambar, ':id' => $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE produk SET nama_produk=:nama, id_kategori=:kat, deskripsi=:desk, harga=:harga, stok=:stok WHERE id_produk=:id");
                        $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok, ':id' => $id]);
                    }
                    $success = 'Produk diperbarui.';
                } else {
                    if (!$gambar) {
                        $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, id_kategori, deskripsi, harga, stok) VALUES (:nama, :kat, :desk, :harga, :stok)");
                        $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, id_kategori, deskripsi, harga, stok, gambar) VALUES (:nama, :kat, :desk, :harga, :stok, :gambar)");
                        $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok, ':gambar' => $gambar]);
                    }
                    $success = 'Produk ditambahkan.';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT p.id_produk, p.nama_produk, k.nama_kategori, p.harga, p.stok, p.gambar FROM produk p JOIN kategori_produk k ON p.id_kategori = k.id_kategori ORDER BY p.nama_produk");
$products = $stmt->fetchAll();

// Fetch categories for dropdown
$stmtCat = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori_produk ORDER BY nama_kategori");
$categories = $stmtCat->fetchAll();

// Check if editing
$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
if ($editId > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id");
    $stmtEdit->execute([':id' => $editId]);
    $editData = $stmtEdit->fetch();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Produk - MeyDa Collection</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .form-container { max-width: 600px; margin: 20px 0; padding: 20px; border: 1px solid #404040; border-radius: 8px; background: #252525; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #ffffff; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #404040; border-radius: 8px; font-family: inherit; background: #1a1a1a; color: #ffffff; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #ff6d00; box-shadow: 0 0 0 2px rgba(255,109,0,0.1); }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group input[type="file"] { padding: 4px; }
    .image-preview { max-width: 200px; margin-top: 10px; border-radius: 8px; }
    .form-buttons { display: flex; gap: 10px; }
    .form-buttons button { padding: 10px 20px; background: #ff6d00; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
    .form-buttons button:hover { background: #e55d00; transform: translateY(-1px); }
    .form-buttons a { padding: 10px 20px; background: #404040; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
    .form-buttons a:hover { background: #505050; }
    .error-msg { color: #ff9999; background: #4a2a2a; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #662a2a; }
    .success-msg { color: #99ff99; background: #2a4a3a; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #2a6a4a; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #252525; }
    table th, table td { padding: 10px; border-bottom: 1px solid #404040; text-align: left; color: #ffffff; }
    table th { background: #1a1a1a; font-weight: 600; }
    .action-link { color: #ff6d00; text-decoration: none; margin-right: 10px; transition: opacity 0.2s; }
    .action-link:hover { opacity: 0.8; }
    .delete-link { color: #ff6666; }
    .product-img { max-width: 60px; height: auto; border-radius: 4px; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="container">
    <h2><?php echo $editId > 0 ? 'Edit Produk' : 'Kelola Produk'; ?></h2>

    <?php if (!empty($error)): ?>
      <div class="error-msg"><?php echo h($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="success-msg"><?php echo h($success); ?></div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
      <div class="form-container">
        <h3><?php echo $editId > 0 ? 'Edit' : 'Tambah'; ?> Produk</h3>
        <form method="post" enctype="multipart/form-data">
          <?php if ($editId > 0): ?>
            <input type="hidden" name="id" value="<?php echo $editId; ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="nama">Nama Produk *</label>
            <input type="text" id="nama" name="nama" value="<?php echo $editData ? h($editData['nama_produk']) : ''; ?>" required>
          </div>

          <div class="form-group">
            <label for="kategori">Kategori *</label>
            <select id="kategori" name="kategori" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id_kategori']; ?>" <?php echo ($editData && $editData['id_kategori'] == $cat['id_kategori']) ? 'selected' : ''; ?>>
                  <?php echo h($cat['nama_kategori']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi"><?php echo $editData ? h($editData['deskripsi']) : ''; ?></textarea>
          </div>

          <div class="form-group">
            <label for="harga">Harga (Rp) *</label>
            <input type="number" id="harga" name="harga" value="<?php echo $editData ? $editData['harga'] : ''; ?>" step="0.01" min="0" required>
          </div>

          <div class="form-group">
            <label for="stok">Stok *</label>
            <input type="number" id="stok" name="stok" value="<?php echo $editData ? $editData['stok'] : ''; ?>" min="0" required>
          </div>

          <div class="form-group">
            <label for="gambar">Gambar Produk (JPG, PNG, GIF, WebP - Max 2MB)</label>
            <input type="file" id="gambar" name="gambar" accept="image/*">
            <?php if ($editData && !empty($editData['gambar'])): ?>
              <p style="font-size: 13px; color: #6b7280;">Gambar saat ini:</p>
              <img src="../uploads/<?php echo h($editData['gambar']); ?>" alt="Product" class="image-preview">
            <?php endif; ?>
          </div>

          <div class="form-buttons">
            <button type="submit"><?php echo $editId > 0 ? 'Update' : 'Tambah'; ?></button>
            <?php if ($editId > 0): ?>
              <a href="products.php">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <h3>Daftar Produk</h3>
    <?php if (empty($products)): ?>
      <p>Tidak ada produk.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Gambar</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <?php if (isAdmin()): ?>
              <th>Aksi</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <?php if (!empty($p['gambar'])): ?>
                  <img src="../uploads/<?php echo h($p['gambar']); ?>" alt="Product" class="product-img">
                <?php else: ?>
                  <span style="color: #6b7280; font-size: 13px;">Tidak ada gambar</span>
                <?php endif; ?>
              </td>
              <td><?php echo h($p['nama_produk']); ?></td>
              <td><?php echo h($p['nama_kategori']); ?></td>
              <td>Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
              <td><?php echo $p['stok']; ?></td>
              <?php if (isAdmin()): ?>
                <td>
                  <a href="products.php?edit=<?php echo $p['id_produk']; ?>" class="action-link">Edit</a>
                  <a href="products.php?delete=<?php echo $p['id_produk']; ?>" class="action-link delete-link" onclick="return confirm('Hapus produk ini?')">Hapus</a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection Admin</small></div>
  </footer>
</body>
</html>
