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

  if (empty($nama) || empty($email) || empty($password)) {
    $error = 'Name, email and password are required.';
  } elseif ($password !== $password_confirm) {
    $error = 'Password and password confirmation do not match.';
  } else {
    $complexity = validatePasswordComplexity($password);
    if (!$complexity['success']) {
      $error = $complexity['error'];
    } else {
      $pdo = getPDO();
      // Check if email already exists
      $check = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = :email");
      $check->execute([':email' => $email]);
      if ($check->fetch()) {
        $error = 'Email is already registered.';
      } else {
        try {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $activation_token = bin2hex(random_bytes(32));

          $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, email, password_hash, telepon, is_active, activation_token) VALUES (:nama, :email, :password_hash, :telepon, 0, :token)");
          $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password_hash' => $hash,
            ':telepon' => $telepon,
            ':token' => $activation_token
          ]);

          // Send Activation Email
          require_once __DIR__ . '/SimpleSMTP.php';
          $activationLink = asset("activate.php?token=$activation_token");
          $subject = "Activate Your MeyDa Collection Account";
          $message = "
              <html>
              <head><title>Activate Your Account</title></head>
              <body>
                  <h1>Welcome to MeyDa Collection!</h1>
                  <p>Please click the link below to activate your account and start shopping:</p>
                  <p><a href='$activationLink' style='padding: 12px 24px; background: #ff6d00; color: #fff; text-decoration: none; border-radius: 8px; display: inline-block;'>Activate Account</a></p>
                  <p>If the link doesn't work, copy and paste this URL into your browser:</p>
                  <p>$activationLink</p>
              </body>
              </html>
          ";

          $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
          $mailResult = $smtp->send($email, $subject, $message);

          if ($mailResult['success']) {
            $success = 'Registration successful! Please check your email to activate your account.';
          } else {
            $success = 'Registration successful! However, we could not send the activation email. Please contact support. (Error: ' . $mailResult['error'] . ')';
          }
        } catch (Exception $e) {
          $error = 'An error occurred: ' . $e->getMessage();
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register - MeyDa Collection</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
</head>

<body class="auth-page">
  <main class="auth-center">
    <a href="index" class="back-button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Back to Home
    </a>
    <div class="register-container">
      <div class="register-card">
        <div class="login-card-title">
          <h2>Customer Registration</h2>
          <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Fill in your details to create a new account
          </p>
        </div>

        <div class="register-form-content" style="margin-top: 24px;">
          <?php if (!empty($error)): ?>
            <div class="alert alert-error">
              <?php echo h($error); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success">
              <?php echo h($success); ?>
            </div>
            <div style="margin-top: 32px;">
              <a href="login" class="btn-primary">Login now</a>
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
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                      pattern="(?=.*\d)(?=.*[!@#$%^&*(),.?\&quot;:{}|<>]).{8,}"
                      title="Minimum 8 characters, at least one number and one special character">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label for="password_confirm">Confirm *</label>
                  <div class="password-field-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password_confirm', this)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                  </div>
                  <div id="password-error" style="color: #ff6b6b; font-size: 12px; margin-top: 4px; display: none;">
                    Passwords do not match
                  </div>
                </div>
              </div>

              <div id="password-requirements"
                style="margin-bottom: 20px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; font-size: 13px;">
                <p style="margin-bottom: 8px; color: var(--muted); font-weight: 600;">Password Requirements:</p>
                <ul style="list-style: none; padding: 0; margin: 0;">
                  <li id="req-length"
                    style="color: #ff6b6b; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <span class="icon">○</span> Minimum 8 characters
                  </li>
                  <li id="req-number"
                    style="color: #ff6b6b; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <span class="icon">○</span> At least one number
                  </li>
                  <li id="req-special" style="color: #ff6b6b; display: flex; align-items: center; gap: 8px;">
                    <span class="icon">○</span> At least one special character
                  </li>
                </ul>
              </div>

              <div class="form-group">
                <label for="telepon">Phone Number</label>
                <input type="tel" id="telepon" name="telepon" placeholder="08123456789">
              </div>

              <button type="submit" id="register-btn" class="btn-primary" style="margin-top: 8px;">Register Now</button>

              <div style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--muted);">
                Already have an account? <a href="login" class="link">Login here</a>
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

    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const registerBtn = document.getElementById('register-btn');
    const errorMsg = document.getElementById('password-error');

    function checkPasswords() {
      const p1 = passwordInput.value;
      const p2 = confirmInput.value;

      // Update requirements indicators
      const hasLength = p1.length >= 8;
      const hasNumber = /[0-9]/.test(p1);
      const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(p1);

      updateRequirement('req-length', hasLength);
      updateRequirement('req-number', hasNumber);
      updateRequirement('req-special', hasSpecial);

      const isComplex = hasLength && hasNumber && hasSpecial;
      const match = !p2 || p1 === p2;

      if (!match) {
        errorMsg.style.display = 'block';
      } else {
        errorMsg.style.display = 'none';
      }

      if (!isComplex || !match) {
        registerBtn.disabled = true;
        registerBtn.style.opacity = '0.5';
        registerBtn.style.cursor = 'not-allowed';
      } else {
        registerBtn.disabled = false;
        registerBtn.style.opacity = '1';
        registerBtn.style.cursor = 'pointer';
      }
    }

    function updateRequirement(id, valid) {
      const el = document.getElementById(id);
      if (valid) {
        el.style.color = '#51cf66';
        el.querySelector('.icon').textContent = '●';
      } else {
        el.style.color = '#ff6b6b';
        el.querySelector('.icon').textContent = '○';
      }
    }

    if (passwordInput && confirmInput) {
      passwordInput.addEventListener('input', checkPasswords);
      confirmInput.addEventListener('input', checkPasswords);
      // Initialize on load
      checkPasswords();
    }

    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function (e) {
        const p1 = passwordInput.value;
        const p2 = confirmInput.value;
        if (p1 !== p2) {
          e.preventDefault();
        }
      });
    }
  </script>
</body>

</html>