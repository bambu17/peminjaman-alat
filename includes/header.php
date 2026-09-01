<?php
/**
 * Dipanggil setelah requireRole() di setiap halaman.
 * Variabel opsional yang bisa di-set sebelum include:
 *   $pageTitle, $pageSubtitle, $activeMenu
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$activeMenu = $activeMenu ?? '';
$role = $_SESSION['role'] ?? '';

$menus = [
    'admin' => [
        'dashboard'   => ['label' => 'Dashboard',        'url' => 'dashboard.php'],
        'users'       => ['label' => 'Data User',        'url' => 'users.php'],
        'kategori'    => ['label' => 'Kategori Alat',     'url' => 'kategori.php'],
        'alat'        => ['label' => 'Data Alat',        'url' => 'alat.php'],
        'peminjaman'  => ['label' => 'Data Peminjaman',  'url' => 'peminjaman.php'],
        'pengembalian'=> ['label' => 'Data Pengembalian','url' => 'pengembalian.php'],
        'laporan'     => ['label' => 'Cetak Laporan',    'url' => 'laporan.php'],
        'log'         => ['label' => 'Log Aktifitas',    'url' => 'log.php'],
    ],
    'petugas' => [
        'dashboard'    => ['label' => 'Dashboard',              'url' => 'dashboard.php'],
        'approval'     => ['label' => 'Setujui Peminjaman',     'url' => 'approval.php'],
        'pengembalian' => ['label' => 'Proses Pengembalian',    'url' => 'pengembalian.php'],
        'pantau'       => ['label' => 'Pantau Peminjaman Aktif','url' => 'pantau.php'],
    ],
    'peminjam' => [
        'dashboard'   => ['label' => 'Dashboard',          'url' => 'dashboard.php'],
        'daftar_alat' => ['label' => 'Daftar Alat',        'url' => 'daftar_alat.php'],
        'ajukan'      => ['label' => 'Ajukan Peminjaman',  'url' => 'ajukan_peminjaman.php'],
        'riwayat'     => ['label' => 'Riwayat Peminjaman', 'url' => 'riwayat.php'],
    ],
];
$roleLabel = ['admin' => 'Administrator', 'petugas' => 'Petugas', 'peminjam' => 'Peminjam'][$role] ?? $role;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> - Aplikasi Peminjaman Alat</title>
<link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body>
<div class="app">
    <div class="sidebar">
        <div class="brand">
            🛠️ Peminjaman Alat
            <small><?= h($roleLabel) ?> Panel</small>
        </div>
        <?php foreach ($menus[$role] ?? [] as $key => $item): ?>
            <a class="menu-item <?= $activeMenu === $key ? 'active' : '' ?>" href="<?= h($item['url']) ?>">
                <?= h($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="main">
        <div class="topbar">
            <div><strong style="font-size:14px;"><?= h($pageTitle) ?></strong></div>
            <div class="user-info">
                <span><strong><?= h($_SESSION['nama_lengkap'] ?? '') ?></strong> (<?= h($roleLabel) ?>)</span>
                <a class="logout-btn" href="<?= baseUrl('logout.php') ?>">Logout</a>
            </div>
        </div>
        <div class="content">
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?>">
                    <?= h($flash['message']) ?>
                </div>
            <?php endif; ?>
            <h1 class="page-title"><?= h($pageTitle) ?></h1>
            <?php if ($pageSubtitle): ?><p class="page-subtitle"><?= h($pageSubtitle) ?></p><?php endif; ?>
