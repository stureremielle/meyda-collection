<?php
require_once __DIR__ . '/auth.php';

$token = $_GET['token'] ?? '';
$error = null;
$success = null;

if (empty($token)) {
    header('Location: login');
    exit;
}

$pdo = getPDO();
// Validate token
$stmt = $pdo->prepare("SELECT id_pelanggan, reset_expires_at FROM pelanggan WHERE reset_token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires_at']) < time()) {
    $error = 'Invalid or expired reset token. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($password)) {
            $error = 'Please enter a new password.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } else {
            $complexity = validatePasswordComplexity($password);
            if (!$complexity['success']) {
                $error = $complexity['error'];
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE pelanggan SET password_hash = :hash, reset_token = NULL, reset_expires_at = NULL WHERE id_pelanggan = :id");
                    $stmt->execute([
                        ':hash' => $hash,
                        ':id' => $user['id_pelanggan']
                    ]);
                    $success = 'Your password has been reset successfully. You can now login.';
                } catch (Exception $e) {
                    $error = 'An error occurred: ' . $e->getMessage();
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password - MeyDa Collection</title>
    <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
</head>

<body class="auth-page">
    <main class="auth-center">
        <a href="login" class="back-button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Login
        </a>
        <div class="login-container">
            <div class="login-card">
                <div class="login-card-title">
                    <h2>Reset Password</h2>
                    <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Enter your new password below</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo h($error); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo h($success); ?>
                    </div>
                    <div style="margin-top: 32px; text-align: center;">
                        <a href="login" class="btn-primary">Go to Login</a>
                    </div>
                <?php elseif (!$error): ?>
                    <form method="post" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <div class="password-field-wrapper">
                                <input type="password" id="password" name="password" required placeholder="••••••••"
                                    pattern="(?=.*\d)(?=.*[!@#$%^&*(),.?\&quot;:{}|<>]).{8,}"
                                    title="Minimum 8 characters, at least one number and one special character">
                                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirm New Password</label>
                            <div class="password-field-wrapper">
                                <input type="password" id="password_confirm" name="password_confirm" required
                                    placeholder="••••••••">
                                <button type="button" class="toggle-password"
                                    onclick="togglePassword('password_confirm', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div id="password-error"
                                style="color: #ff6b6b; font-size: 12px; margin-top: 4px; display: none;">
                                Passwords do not match
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

                        <button type="submit" id="reset-btn" class="btn-primary" style="margin-top: 8px;">Reset
                            Password</button>
                    </form>
                <?php endif; ?>
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
        const resetBtn = document.getElementById('reset-btn');
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
                resetBtn.disabled = true;
                resetBtn.style.opacity = '0.5';
                resetBtn.style.cursor = 'not-allowed';
            } else {
                resetBtn.disabled = false;
                resetBtn.style.opacity = '1';
                resetBtn.style.cursor = 'pointer';
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