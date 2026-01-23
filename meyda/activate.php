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
$stmt = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE activation_token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if ($user) {
    // Activate account
    $stmt = $pdo->prepare("UPDATE pelanggan SET is_active = 1, activation_token = NULL WHERE id_pelanggan = :id");
    $stmt->execute([':id' => $user['id_pelanggan']]);
    $success = "Your account has been activated! you can now login.";
} else {
    $error = "Invalid or expired activation link. Maybe you already activated?";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Account Activation - MeyDa Collection</title>
    <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
</head>

<body class="auth-page">
    <main class="auth-center">
        <div class="auth-card">
            <h2 style="margin-bottom: 24px; text-align: center;">Account Activation</h2>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
                <div style="text-align: center;">
                    <a href="login" class="btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo $success; ?>
                </div>
                <div style="text-align: center;">
                    <a href="login" class="btn-primary">Login Now</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>