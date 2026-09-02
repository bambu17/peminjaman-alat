<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Username tidak ditemukan.';
        } elseif ($user['status'] !== 'aktif') {
            $error = 'Akun Anda nonaktif. Hubungi admin.';
        } else {
            $storedPassword = (string)($user['password'] ?? '');
            $passwordMatches = ($storedPassword === $password) || password_verify($password, $storedPassword);

            if (!$passwordMatches) {
                $error = 'Password salah.';
            } else {
                $role = $user['role'] ?? 'peminjam';
                if ($user['username'] === 'admin' && $role === 'peminjam') {
                    $role = 'admin';
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $role;

                catatLog($user['id'], 'Login ke sistem');

                header('Location: index.php');
                exit;
            }
        }
    }

    if ($error) {
        catatLog(null, "Percobaan login gagal untuk username '$username'");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Aplikasi Peminjaman Alat</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <h1>🛠️ Aplikasi Peminjaman Alat</h1>
        <p class="subtitle">Silakan masuk untuk melanjutkan</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Username</label>
            <input type="text" name="username" required autofocus value="<?= h($_POST['username'] ?? '') ?>">

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Masuk</button>
        </form>
    </div>
</div>
</body>
</html>
