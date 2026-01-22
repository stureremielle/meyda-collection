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
            $error = 'Cannot delete a category that still has products.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM kategori_produk WHERE id_kategori = :id");
            $stmt->execute([':id' => $id]);
            $success = 'Category deleted successfully.';
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
        $error = 'Category name is required.';
    } else {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO kategori_produk (nama_kategori, deskripsi) VALUES (:nama, :desc)");
                $stmt->execute([':nama' => $name, ':desc' => $desc]);
                $success = 'Category added successfully.';
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE kategori_produk SET nama_kategori = :nama, deskripsi = :desc WHERE id_kategori = :id");
                $stmt->execute([':nama' => $name, ':desc' => $desc, ':id' => $id]);
                $success = 'Category updated successfully.';
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
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Categories - MeyDa Admin</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <style>
    .categories-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 24px;
      display: grid;
      grid-template-columns: 350px 1fr;
      gap: 32px;
      align-items: flex-start;
    }
    @media (max-width: 992px) {
      .categories-layout { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="categories-layout">
    <!-- Form Side -->
    <aside class="admin-sidebar-form">
      <div class="admin-card">
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">
          <?php echo $edit ? 'Edit Category' : 'Add New Category'; ?>
        </h3>

        <?php if ($error): ?>
          <div class="alert alert-error" style="margin-bottom: 24px;"><?php echo h($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success" style="margin-bottom: 24px;"><?php echo h($success); ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="action" value="<?php echo $edit ? 'edit' : 'add'; ?>">
          <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?php echo $edit['id_kategori']; ?>">
          <?php endif; ?>

          <div class="admin-form-group">
            <label>Category Name *</label>
            <input type="text" name="nama_kategori" class="admin-input" value="<?php echo $edit ? h($edit['nama_kategori']) : ''; ?>" required placeholder="e.g. Silk Collection">
          </div>

          <div class="admin-form-group">
            <label>Description (optional)</label>
            <textarea name="deskripsi" class="admin-input" style="min-height: 100px;"><?php echo $edit ? h($edit['deskripsi'] ?? '') : ''; ?></textarea>
          </div>

          <div style="margin-top: 32px; display: flex; gap: 12px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="flex: 1; justify-content: center;">
              <?php echo $edit ? 'Update Category' : 'Add Category'; ?>
            </button>
            <?php if ($edit): ?>
              <a href="categories.php" class="admin-btn admin-btn-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </aside>

    <!-- Table Side -->
    <div class="admin-card" style="overflow-x: auto;">
      <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">Category List</h3>
      
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Products</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <?php
              $stmtCnt = $pdo->prepare("SELECT COUNT(*) as cnt FROM produk WHERE id_kategori = :id");
              $stmtCnt->execute([':id' => $c['id_kategori']]);
              $productCount = $stmtCnt->fetch()['cnt'];
            ?>
            <tr>
              <td style="font-weight: 600;"><?php echo h($c['nama_kategori']); ?></td>
              <td style="color: var(--muted); font-size: 14px;"><?php echo h($c['deskripsi'] ?? '-'); ?></td>
              <td>
                <span class="admin-badge">
                  <span class="badge-count"><?php echo $productCount; ?></span> Products
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                  <a href="?edit=<?php echo $c['id_kategori']; ?>" class="admin-btn admin-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                  <?php if ($productCount == 0): ?>
                    <form method="post" style="display: inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $c['id_kategori']; ?>">
                      <button type="button" class="admin-btn admin-btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="confirmDelete(this, 'category')">Delete</button>
                    </form>
                  <?php else: ?>
                    <span style="color: var(--muted); font-size: 11px; font-style: italic;">Cannot delete</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
