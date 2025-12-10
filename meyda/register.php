<?php
require_once __DIR__ . '/auth.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $password_confirm = trim($_POST['password_confirm'] ?? '');
  $telepon = trim($_POST['telepon'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email) || empty($password)) {
      $error = 'Nama, email dan password harus diisi.';
    } elseif ($password !== $password_confirm) {
      $error = 'Password dan konfirmasi password tidak cocok.';
    } else {
        $pdo = getPDO();
        // Check if email already exists
        $check = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            try {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, email, password_hash, telepon, alamat) VALUES (:nama, :email, :password_hash, :telepon, :alamat)");
          $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password_hash' => $hash,
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
    /* Use auth styles from styles.css; only minor adjustments here */
    .register-container { max-width: 740px; margin: 40px auto; }
    .register-card { background: var(--card); border: 1px solid var(--md-sys-color-outline); border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.6); overflow: hidden; width: 520px; max-width: 92%; margin: 0 auto; }
    .register-card h2 { margin: 0; padding: 16px 20px; color: var(--md-sys-color-on-surface); }
    .register-form { padding: 20px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--muted); }
    .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--md-sys-color-outline); border-radius: 8px; background: #0f0f0f; color: var(--md-sys-color-on-surface); }
    .form-group textarea { min-height: 90px; resize: vertical; }
    .form-actions { text-align: center; margin-top: 8px; }
    .btn-primary { display:inline-block; width: 220px; max-width: 100%; padding: 10px; background: var(--accent); color: var(--md-sys-color-on-primary); border-radius: 10px; border: none; font-weight: 600; cursor: pointer; }
    .btn-primary:hover { background: #e55d00; transform: translateY(-1px); }
    .error-msg { color: #ffb4b4; background: #3b1f1f; padding: 10px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #662a2a; }
    .success-msg { color: #99ff99; background: #163b2b; padding: 10px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #2a6a4a; }
    .show-pass { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--muted); cursor:pointer; }
    .show-pass input[type="checkbox"] { width:16px; height:16px; }
    /* place the show-pass on the right under the confirm field */
    .show-pass-wrap { text-align: right; margin-top: 6px; }
    .login-link { text-align: center; margin-top: 12px; color: var(--muted); }
    .login-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
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

  <main class="container auth-center">
    <div class="register-container">
      <div class="register-card">
        <h2>Pendaftaran Pelanggan</h2>
        <div class="register-form">

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
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
          </div>

          <div class="form-group">
            <label for="password_confirm">Konfirmasi Password *</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
            <div class="show-pass-wrap">
              <label class="show-pass"><input type="checkbox" id="showPasswords"> Tampilkan password</label>
            </div>
          </div>

          <div class="form-group">
            <label for="telepon">Telepon</label>
            <input type="tel" id="telepon" name="telepon">
          </div>

          <div class="form-group">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat"></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">Daftar</button>
          </div>

          <div class="login-link">
            Sudah punya akun? <a href="login.php?mode=customer">Login di sini</a>
          </div>
        </form>
      <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>

  <script>
    // Toggle show/hide passwords
    (function(){
      var cb = document.getElementById('showPasswords');
      if (!cb) return;
      var p1 = document.getElementById('password');
      var p2 = document.getElementById('password_confirm');
      cb.addEventListener('change', function(){
        var t = cb.checked ? 'text' : 'password';
        if (p1) p1.type = t;
        if (p2) p2.type = t;
      });
      // Client-side match check
      var form = document.querySelector('form');
      if (form) {
        form.addEventListener('submit', function(e){
          var v1 = p1 ? p1.value : '';
          var v2 = p2 ? p2.value : '';
          if (v1 !== v2) {
            e.preventDefault();
            alert('Password dan konfirmasi password tidak cocok.');
            if (p2) p2.focus();
          }
        });
      }
    })();
  </script>
</body>
</html>
