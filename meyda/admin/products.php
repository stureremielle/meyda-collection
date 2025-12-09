<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();
$error = null;
$success = null;

// Handle delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
        $stmt->execute([':id' => $id]);
        $success = 'Produk dihapus.';
    } catch (Exception $e) {
        $error = 'Tidak bisa menghapus: ' . $e->getMessage();
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $kategori = (int)($_POST['kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);

    if (empty($nama) || $kategori <= 0 || $harga <= 0) {
        $error = 'Nama, kategori, dan harga harus diisi dengan benar.';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE produk SET nama_produk=:nama, id_kategori=:kat, deskripsi=:desk, harga=:harga, stok=:stok WHERE id_produk=:id");
                $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok, ':id' => $id]);
                $success = 'Produk diperbarui.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, id_kategori, deskripsi, harga, stok) VALUES (:nama, :kat, :desk, :harga, :stok)");
                $stmt->execute([':nama' => $nama, ':kat' => $kategori, ':desk' => $deskripsi, ':harga' => $harga, ':stok' => $stok]);
                $success = 'Produk ditambahkan.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT p.id_produk, p.nama_produk, k.nama_kategori, p.harga, p.stok FROM produk p JOIN kategori_produk k ON p.id_kategori = k.id_kategori ORDER BY p.nama_produk");
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
    .form-container { max-width: 600px; margin: 20px 0; padding: 20px; border: 1px solid #eef2f6; border-radius: 6px; background: #f8fafc; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-buttons { display: flex; gap: 10px; }
    .form-buttons button { padding: 10px 20px; background: #1f6feb; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .form-buttons a { padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 4px; }
    .error-msg { color: #8b1e1e; background: #fff4f4; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .success-msg { color: #11644a; background: #f4fffb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { padding: 10px; border-bottom: 1px solid #eef2f6; text-align: left; }
    table th { background: #f8fafc; font-weight: 600; }
    .action-link { color: #1f6feb; text-decoration: none; margin-right: 10px; }
    .delete-link { color: #c84f2c; }
    .btn-add { display: inline-block; padding: 8px 16px; background: #1f6feb; color: white; text-decoration: none; border-radius: 4px; margin-bottom: 15px; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection - Admin</h1>
      <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="reports.php">Laporan</a>
        <a href="transactions.php">Transaksi</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <h2><?php echo $editId > 0 ? 'Edit Produk' : 'Kelola Produk'; ?></h2>

    <?php if (!empty($error)): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
      <div class="form-container">
        <h3><?php echo $editId > 0 ? 'Edit' : 'Tambah'; ?> Produk</h3>
        <form method="post">
          <?php if ($editId > 0): ?>
            <input type="hidden" name="id" value="<?php echo $editId; ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="nama">Nama Produk *</label>
            <input type="text" id="nama" name="nama" value="<?php echo $editData ? htmlspecialchars($editData['nama_produk']) : ''; ?>" required>
          </div>

          <div class="form-group">
            <label for="kategori">Kategori *</label>
            <select id="kategori" name="kategori" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id_kategori']; ?>" <?php echo ($editData && $editData['id_kategori'] == $cat['id_kategori']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi"><?php echo $editData ? htmlspecialchars($editData['deskripsi']) : ''; ?></textarea>
          </div>

          <div class="form-group">
            <label for="harga">Harga (Rp) *</label>
            <input type="number" id="harga" name="harga" value="<?php echo $editData ? $editData['harga'] : ''; ?>" step="0.01" min="0" required>
          </div>

          <div class="form-group">
            <label for="stok">Stok *</label>
            <input type="number" id="stok" name="stok" value="<?php echo $editData ? $editData['stok'] : ''; ?>" min="0" required>
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
              <td><?php echo htmlspecialchars($p['nama_produk']); ?></td>
              <td><?php echo htmlspecialchars($p['nama_kategori']); ?></td>
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
