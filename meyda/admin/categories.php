<?php
// admin/categories.php - Category management (add, edit, delete)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Ensure staff/admin only
requireLogin('staff');

$pdo = getPDO();
$error = null;
$success = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    try {
        // Check if category has products
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM produk WHERE id_kategori = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetch()['cnt'] > 0) {
            $error = 'Tidak bisa menghapus kategori yang memiliki produk.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM kategori_produk WHERE id_kategori = :id");
            $stmt->execute([':id' => $id]);
            $success = 'Kategori berhasil dihapus.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    $name = trim($_POST['nama_kategori'] ?? '');
    $desc = trim($_POST['deskripsi'] ?? '');
    
    if (empty($name)) {
        $error = 'Nama kategori harus diisi.';
    } else {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO kategori_produk (nama_kategori, deskripsi) VALUES (:nama, :desc)");
                $stmt->execute([':nama' => $name, ':desc' => $desc]);
                $success = 'Kategori berhasil ditambahkan.';
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE kategori_produk SET nama_kategori = :nama, deskripsi = :desc WHERE id_kategori = :id");
                $stmt->execute([':nama' => $name, ':desc' => $desc, ':id' => $id]);
                $success = 'Kategori berhasil diperbarui.';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all categories
$stmt = $pdo->query("SELECT * FROM kategori_produk ORDER BY nama_kategori");
$categories = $stmt->fetchAll();

// Get category to edit (if any)
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM kategori_produk WHERE id_kategori = :id");
    $stmt->execute([':id' => $id]);
    $edit = $stmt->fetch();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Kategori - MeyDa Admin</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; }
    main.container { flex: 1; max-width: 1200px; margin: 0 auto; padding: 12px; width: 100%; }
    .form-section { background: #252525; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #404040; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #ffffff; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #404040; border-radius: 8px; font-family: inherit; background: #1a1a1a; color: #ffffff; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #ff6d00; box-shadow: 0 0 0 2px rgba(255,109,0,0.1); }
    .form-group textarea { min-height: 80px; resize: vertical; }
    button, .btn-secondary { background: #ff6d00; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; font-weight: 500; transition: all 0.2s; font-family: 'Futura', inherit; }
    button:hover, .btn-secondary:hover { background: #e55d00; transform: translateY(-1px); }
    .btn-secondary { background: #404040; }
    .btn-secondary:hover { background: #505050; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #252525; }
    table th, table td { padding: 16px; text-align: left; border-bottom: 1px solid #404040; color: #ffffff; }
    table th { background: #1a1a1a; font-weight: 600; }
    .action-cell { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .action-btn { background: #ff6d00; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; font-size: 13px; transition: all 0.2s; margin: 0; font-family: 'Futura', inherit; }
    .action-btn:hover { background: #e55d00; transform: translateY(-1px); }
    .action-btn-danger { background: #c84f2c; }
    .action-btn-danger:hover { background: #a83a1f; }
    .action-disabled { color: #888888; font-size: 13px; }
    .delete-form { display: inline; margin: 0; padding: 0; }
    .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; }
    .alert-error { background: #4a2a2a; color: #ff9999; border: 1px solid #662a2a; }
    .alert-success { background: #2a4a3a; color: #99ff99; border: 1px solid #2a6a4a; }
    .section { margin-bottom: 30px; }
    .section h2 { margin-top: 0; color: #ffffff; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="container">
    <h2>Kelola Kategori</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Form Add/Edit -->
    <div class="form-section">
      <h2><?php echo $edit ? 'Edit Kategori' : 'Tambah Kategori'; ?></h2>
      <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'edit' : 'add'; ?>">
          <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?php echo $edit['id_kategori']; ?>">
          <?php endif; ?>

          <div class="form-group">
            <label>Nama Kategori</label>
            <input type="text" name="nama_kategori" value="<?php echo $edit ? htmlspecialchars($edit['nama_kategori']) : ''; ?>" required>
          </div>

          <div class="form-group">
            <label>Deskripsi (opsional)</label>
            <textarea name="deskripsi"><?php echo $edit ? htmlspecialchars($edit['deskripsi'] ?? '') : ''; ?></textarea>
          </div>

          <button type="submit"><?php echo $edit ? 'Update' : 'Tambah'; ?> Kategori</button>
          <?php if ($edit): ?>
            <a href="categories.php" class="btn-secondary">Batal</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Category List -->
      <div class="section">
        <h2>Daftar Kategori</h2>
        <table class="table">
          <tr>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
            <th>Produk</th>
            <th>Aksi</th>
          </tr>
          <?php foreach ($categories as $c): ?>
            <?php
              $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM produk WHERE id_kategori = :id");
              $stmt->execute([':id' => $c['id_kategori']]);
              $productCount = $stmt->fetch()['cnt'];
            ?>
            <tr>
              <td><?php echo htmlspecialchars($c['nama_kategori']); ?></td>
              <td><?php echo htmlspecialchars($c['deskripsi'] ?? '-'); ?></td>
              <td><?php echo $productCount; ?></td>
              <td>
                <div class="action-cell">
                  <a href="?edit=<?php echo $c['id_kategori']; ?>" class="action-btn">Edit</a>
                  <?php if ($productCount == 0): ?>
                    <form method="post" class="delete-form">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $c['id_kategori']; ?>">
                      <button type="submit" class="action-btn action-btn-danger" onclick="return confirm('Yakin?')">Hapus</button>
                    </form>
                  <?php else: ?>
                    <span class="action-disabled">Tidak bisa hapus</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection Admin</small></div>
  </footer>

</body>
</html>
