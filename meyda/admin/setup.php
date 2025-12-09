<?php
// admin/setup.php - Create first admin account with invitation key (no auth required initially)
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

$error = null;
$success = null;

// Check if any admin already exists (if so, deny further setup)
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `user` WHERE role='admin'");
$adminExists = $stmt->fetch()['cnt'] > 0;

if ($adminExists) {
    $error = 'Admin already exists. Silakan login.';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $invitationKey = trim($_POST['invitation_key'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nama = trim($_POST['nama'] ?? '');

        // The invitation key from environment variable (fallback to default if not set)
        $validKey = getenv('MEYDA_ADMIN_KEY') ?: 'MEYDA_ADMIN_2025_SECRET';

        if (empty($invitationKey) || empty($username) || empty($password) || empty($nama)) {
            $error = 'Semua field harus diisi.';
        } elseif ($invitationKey !== $validKey) {
            $error = 'Kunci undangan salah.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `user` (username, password_hash, nama_lengkap, role) VALUES (:username, :password_hash, :nama, 'admin')");
                $stmt->execute([
                    ':username' => $username,
                    ':password_hash' => $hash,
                    ':nama' => $nama
                ]);
                $success = 'Admin berhasil dibuat! Silakan login.';
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup Admin - MeyDa Collection</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .setup-container { max-width: 500px; margin: 60px auto; padding: 20px; border: 1px solid #eef2f6; border-radius: 6px; background: #f8fafc; }
    .setup-form { background: white; padding: 20px; border-radius: 4px; border: 1px solid #eef2f6; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .form-group button { width: 100%; padding: 10px; background: #1f6feb; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .form-group button:hover { opacity: 0.95; }
    .error-msg { color: #8b1e1e; background: #fff4f4; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .success-msg { color: #11644a; background: #f4fffb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .login-link { text-align: center; margin-top: 15px; }
    .login-link a { color: #1f6feb; text-decoration: none; }
    .warning { background: #fffbec; border: 1px solid #f2cc8f; color: #8b5e00; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection</h1>
      <nav class="nav">
        <a href="../index.php">Home</a>
        <a href="../login.php?mode=staff">Login Staff</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <div class="setup-container">
      <h2>Setup Admin</h2>

      <?php if ($adminExists): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <div class="login-link">
          <a href="../login.php?mode=staff">Login di sini</a>
        </div>
      <?php else: ?>
        <div class="warning">
          ⚠️ Halaman setup admin ini hanya dapat diakses jika belum ada admin yang terdaftar. Setelah admin dibuat, halaman ini akan tertutup.
        </div>

        <?php if (!empty($error)): ?>
          <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
          <div class="login-link">
            <a href="../login.php?mode=staff">Login sekarang</a>
          </div>
        <?php else: ?>
          <form method="post" class="setup-form">
            <p style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
              Buat akun admin pertama untuk sistem MeyDa Collection. Anda memerlukan kunci undangan untuk melanjutkan.
            </p>

            <div class="form-group">
              <label for="invkey">Kunci Undangan *</label>
              <input type="password" id="invkey" name="invitation_key" placeholder="Masukkan kunci undangan" required>
            </div>

            <div class="form-group">
              <label for="username">Username *</label>
              <input type="text" id="username" name="username" placeholder="Contoh: admin_meyda" required>
            </div>

            <div class="form-group">
              <label for="password">Password *</label>
              <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
            </div>

            <div class="form-group">
              <label for="nama">Nama Lengkap *</label>
              <input type="text" id="nama" name="nama" placeholder="Nama Anda" required>
            </div>

            <div class="form-group">
              <button type="submit">Buat Admin</button>
            </div>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>
</body>
</html>
