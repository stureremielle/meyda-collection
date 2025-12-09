<?php
require_once __DIR__ . '/auth.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email harus diisi.';
    } else {
        $pdo = getPDO();
        // Check if email already exists
        $check = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, email, telepon, alamat) VALUES (:nama, :email, :telepon, :alamat)");
                $stmt->execute([
                    ':nama' => $nama,
                    ':email' => $email,
                    ':telepon' => $telepon,
                    ':alamat' => $alamat
                ]);
                $success = 'Pendaftaran berhasil! Silakan login dengan email Anda.';
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
  <title>Register - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .register-container { max-width: 500px; margin: 60px auto; padding: 20px; border: 1px solid #eef2f6; border-radius: 6px; background: #f8fafc; }
    .register-form { background: white; padding: 20px; border-radius: 4px; border: 1px solid #eef2f6; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group button { width: 100%; padding: 10px; background: #1f6feb; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .form-group button:hover { opacity: 0.95; }
    .error-msg { color: #8b1e1e; background: #fff4f4; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .success-msg { color: #11644a; background: #f4fffb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .login-link { text-align: center; margin-top: 15px; }
    .login-link a { color: #1f6feb; text-decoration: none; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection</h1>
      <nav class="nav">
        <a href="index.php">Home</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <div class="register-container">
      <h2>Pendaftaran Pelanggan</h2>

      <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <div class="login-link">
          <a href="login.php?mode=customer">Login sekarang</a>
        </div>
      <?php else: ?>
        <form method="post" class="register-form">
          <div class="form-group">
            <label for="nama">Nama Lengkap *</label>
            <input type="text" id="nama" name="nama" required>
          </div>

          <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
          </div>

          <div class="form-group">
            <label for="telepon">Telepon</label>
            <input type="tel" id="telepon" name="telepon">
          </div>

          <div class="form-group">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat"></textarea>
          </div>

          <div class="form-group">
            <button type="submit">Daftar</button>
          </div>

          <div class="login-link">
            Sudah punya akun? <a href="login.php?mode=customer">Login di sini</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>
</body>
</html>
