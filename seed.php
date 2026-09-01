<?php
/**
 * SEED AKUN DEFAULT
 * Jalankan file ini SATU KALI lewat browser setelah import database.sql:
 *   http://localhost/peminjaman_alat/seed.php
 * Setelah berhasil, sebaiknya file ini dihapus atau diberi proteksi.
 */
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

$users = [
    ['admin',    'admin123',    'Administrator Sistem', 'admin@sekolah.sch.id',    '081200000001', 'admin'],
    ['petugas1', 'petugas123',  'Rina Petugas Gudang',  'petugas1@sekolah.sch.id', '081200000002', 'petugas'],
    ['budi',     'peminjam123', 'Budi Santoso',          'budi@siswa.sch.id',      '081200000003', 'peminjam'],
    ['siti',     'peminjam123', 'Siti Aminah',           'siti@siswa.sch.id',      '081200000004', 'peminjam'],
];

$created = [];
$skipped = [];

foreach ($users as [$username, $password, $nama, $email, $hp, $role]) {
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $skipped[] = $username;
        continue;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, no_hp, role) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$username, $hash, $nama, $email, $hp, $role]);
    $created[] = $username;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Seed Akun Default</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="padding:40px;font-family:sans-serif;max-width:600px;margin:0 auto;">
<h2>Seeder Akun Default</h2>

<?php if ($created): ?>
    <div class="alert alert-success">
        Berhasil membuat akun: <strong><?= implode(', ', $created) ?></strong>
    </div>
<?php endif; ?>

<?php if ($skipped): ?>
    <div class="alert alert-info">
        Sudah ada sebelumnya (dilewati): <strong><?= implode(', ', $skipped) ?></strong>
    </div>
<?php endif; ?>

<table>
<tr><th>Username</th><th>Password</th><th>Role</th></tr>
<tr><td>admin</td><td>admin123</td><td>Admin</td></tr>
<tr><td>petugas1</td><td>petugas123</td><td>Petugas</td></tr>
<tr><td>budi</td><td>peminjam123</td><td>Peminjam</td></tr>
<tr><td>siti</td><td>peminjam123</td><td>Peminjam</td></tr>
</table>

<p style="margin-top:20px;">
    <a class="btn btn-primary" href="login.php">Lanjut ke Halaman Login</a>
</p>
<p class="text-muted" style="font-size:12px;">
    Demi keamanan, hapus atau kunci file <code>seed.php</code> ini setelah selesai digunakan.
</p>
</body>
</html>
