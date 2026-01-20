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
<body>
  <main class="auth-center">
    <a href="index.php" class="back-button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Back to Home
    </a>
    <div class="register-container">
      <div class="register-card">
        <div class="login-card-title">
          <h2>Pendaftaran Pelanggan</h2>
          <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Fill in your details to create a new account</p>
        </div>

        <div class="register-form-content" style="margin-top: 24px;">
          <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align: center; margin-top: 24px;">
              <a href="login.php" class="btn-primary" style="text-decoration: none; display: inline-block;">Login now</a>
            </div>
          <?php else: ?>
            <form method="post" class="register-form">
              <div class="form-group">
                <label for="nama">Full Name *</label>
                <input type="text" id="nama" name="nama" required placeholder="Your Name">
              </div>

              <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required placeholder="name@example.com">
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label for="password">Password *</label>
                  <div class="password-field-wrapper">
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label for="password_confirm">Confirm *</label>
                  <div class="password-field-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password_confirm', this)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="telepon">Phone Number</label>
                <input type="tel" id="telepon" name="telepon" placeholder="08123456789">
              </div>

              <div class="form-group">
                <label for="alamat">Address</label>
                <textarea id="alamat" name="alamat" placeholder="Your shipping address" style="width: 100%; padding: 12px; background: #0f0f0f; color: var(--md-sys-color-on-surface); border: 1px solid var(--md-sys-color-outline); border-radius: 10px; min-height: 80px;"></textarea>
              </div>

              <button type="submit" class="btn-primary" style="margin-top: 8px;">Daftar Sekarang</button>

              <div style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--muted);">
                Sudah punya akun? <a href="login.php" class="link">Login di sini</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('svg');
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }

    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function(e){
        const p1 = document.getElementById('password').value;
        const p2 = document.getElementById('password_confirm').value;
        if (p1 !== p2) {
          e.preventDefault();
          alert('Password dan konfirmasi password tidak cocok.');
        }
      });
    }
  </script>
</body>
</html>
