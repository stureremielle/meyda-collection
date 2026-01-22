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


// Handle delete
if ((isset($_GET['delete']) || (isset($_POST['action']) && $_POST['action'] === 'delete')) && isAdmin()) {
    $id = (int)($_GET['delete'] ?? $_POST['id'] ?? 0);
    $force_delete = isset($_GET['force']) || (isset($_POST['force']) && $_POST['force'] === '1');
    
    if ($id > 0) {
        try {
            // Check if product is referenced in any transaction details
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM detail_transaksi WHERE id_produk = :id");
            $checkStmt->execute([':id' => $id]);
            $count = $checkStmt->fetchColumn();
            
            if ($count > 0 && !$force_delete) {
                // Product is referenced and force delete not specified
                $error = 'Cannot delete: Product is referenced in past transactions. Use "force delete" if you really want to remove it.';
            } else {
                if ($count > 0 && $force_delete) {
                    // Force delete - remove related records in detail_transaksi first
                    $stmt = $pdo->prepare("DELETE FROM detail_transaksi WHERE id_produk = :id");
                    $stmt->execute([':id' => $id]);
                }
                
                // Delete product image if exists
                $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id_produk = :id");
                $stmt->execute([':id' => $id]);
                $product = $stmt->fetch();
                if ($product && !empty($product['gambar'])) {
                    $imgPath = $uploadsDir . '/' . $product['gambar'];
                    if (file_exists($imgPath)) unlink($imgPath);
                }
                
                // Delete the product
                $stmt = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
                $stmt->execute([':id' => $id]);
                $success = 'Product deleted successfully.';
            }
        } catch (Exception $e) {
            $error = 'Cannot delete: ' . $e->getMessage();
        }
    }
}

// Handle add/edit with image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama']) && isAdmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $kategori = (int)($_POST['kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $gambar = null;

    if (empty($nama) || $kategori <= 0 || $harga <= 0) {
        $error = 'Name, category, and price are required.';
    } else {
        // Handle image upload
        if (!empty($_FILES['gambar']['name'])) {
            $file = $_FILES['gambar'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'Unsupported image format. Use JPG, PNG, GIF, or WebP.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Image too large (max 2MB).';
            } else {
                try {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFilename = 'product_' . time() . '_' . uniqid() . '.' . strtolower($ext);
                    if (move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $newFilename)) {
                        $gambar = $newFilename;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } catch (Exception $e) {
                    $error = 'Upload error: ' . $e->getMessage();
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
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Products - MeyDa Admin</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <style>
    .products-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 24px;
      display: grid;
      grid-template-columns: 350px 1fr;
      gap: 32px;
      align-items: flex-start;
    }
    @media (max-width: 992px) {
      .products-layout { grid-template-columns: 1fr; }
    }
    .product-img-cell {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="products-layout">
    <!-- Form Side -->
    <aside class="admin-sidebar-form">
      <div class="admin-card">
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">
          <?php echo $editId > 0 ? 'Edit Product' : 'Add New Product'; ?>
        </h3>
        
        <?php if (!empty($error)): ?>
          <div class="alert alert-error" style="margin-bottom: 24px;"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success" style="margin-bottom: 24px;"><?php echo h($success); ?></div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
          <form method="post" enctype="multipart/form-data">
            <?php if ($editId > 0): ?>
              <input type="hidden" name="id" value="<?php echo $editId; ?>">
            <?php endif; ?>

            <div class="admin-form-group">
              <label>Product Name *</label>
              <input type="text" name="nama" class="admin-input" value="<?php echo $editData ? h($editData['nama_produk']) : ''; ?>" required placeholder="e.g. Silk Scarf">
            </div>

            <div class="admin-form-group">
              <label>Category *</label>
              <select name="kategori" class="admin-input" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['id_kategori']; ?>" <?php echo ($editData && $editData['id_kategori'] == $cat['id_kategori']) ? 'selected' : ''; ?>>
                    <?php echo h($cat['nama_kategori']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-form-group">
              <label>Description</label>
              <textarea name="deskripsi" class="admin-input" style="min-height: 100px;"><?php echo $editData ? h($editData['deskripsi']) : ''; ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="admin-form-group">
                <label>Price (Rp) *</label>
                <input type="number" name="harga" class="admin-input" value="<?php echo $editData ? $editData['harga'] : ''; ?>" step="0.01" min="0" required>
              </div>
              <div class="admin-form-group">
                <label>Stock *</label>
                <input type="number" name="stok" class="admin-input" value="<?php echo $editData ? $editData['stok'] : ''; ?>" min="0" required>
              </div>
            </div>

            <div class="admin-form-group">
              <label>Product Image</label>
              <input type="file" name="gambar" class="admin-input" accept="image/*">
              <?php if ($editData && !empty($editData['gambar'])): ?>
                <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px;">
                  <img src="../uploads/<?php echo h($editData['gambar']); ?>" style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover;">
                  <span style="font-size: 12px; color: var(--muted);">Current Image</span>
                </div>
              <?php endif; ?>
            </div>

            <div style="margin-top: 32px; display: flex; gap: 12px;">
              <button type="submit" class="admin-btn admin-btn-primary" style="flex: 1; justify-content: center;">
                <?php echo $editId > 0 ? 'Update Product' : 'Add Product'; ?>
              </button>
              <?php if ($editId > 0): ?>
                <a href="products.php" class="admin-btn admin-btn-secondary">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        <?php else: ?>
          <p style="color: var(--muted); font-size: 14px;">Log in as Admin to manage products.</p>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Table Side -->
    <div class="admin-card" style="overflow-x: auto;">
      <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">Product Inventory</h3>
      
      <?php if (empty($products)): ?>
        <p style="color: var(--muted); padding: 40px 0; text-align: center;">No products found.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <?php if (isAdmin()): ?>
                <th style="text-align: right;">Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <?php if (!empty($p['gambar'])): ?>
                    <img src="../uploads/<?php echo h($p['gambar']); ?>" class="product-img-cell">
                  <?php else: ?>
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 10px;">No Image</div>
                  <?php endif; ?>
                </td>
                <td style="font-weight: 600;"><?php echo h($p['nama_produk']); ?></td>
                <td><span style="font-size: 13px; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px;"><?php echo h($p['nama_kategori']); ?></span></td>
                <td style="color: var(--accent); font-weight: 600;">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                <td>
                  <span style="<?php echo ($p['stok'] <= 5) ? 'color: #f87171;' : ''; ?>">
                    <?php echo $p['stok']; ?>
                  </span>
                </td>
                <?php if (isAdmin()): ?>
                  <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                      <a href="products.php?edit=<?php echo $p['id_produk']; ?>" class="admin-btn admin-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                      
                      <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $p['id_produk']; ?>">
                    <button type="button" class="admin-btn admin-btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="confirmDelete(this, 'product')">Delete</button>
                  </form>

                      <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $p['id_produk']; ?>">
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="admin-btn admin-btn-danger" style="padding: 6px 12px; font-size: 12px; opacity: 0.6;" onclick="return confirm('Force delete will remove this product even if it was in previous transactions. Continue?')">Force</button>
                      </form>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>

  <script>
  function confirmDelete(btn, type) {
      const form = btn.closest('form');
      adminConfirm({
          title: 'Delete ' + type.charAt(0).toUpperCase() + type.slice(1),
          message: 'Are you sure you want to permanently delete this ' + type + '?',
          confirmText: 'Delete',
          confirmClass: 'admin-btn-danger'
      }, () => {
          form.submit();
      });
  }
  </script>

  <?php include __DIR__ . '/_footer.php'; ?>
