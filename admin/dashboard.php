<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pdo = getDB();
$totalUser = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$totalAlat = $pdo->query("SELECT COALESCE(SUM(jumlah_total),0) c FROM alat")->fetch()['c'];
$alatTersedia = $pdo->query("SELECT COALESCE(SUM(jumlah_tersedia),0) c FROM alat")->fetch()['c'];
$menunggu = $pdo->query("SELECT COUNT(*) c FROM peminjaman WHERE status='menunggu'")->fetch()['c'];
$dipinjam = $pdo->query("SELECT COUNT(*) c FROM peminjaman WHERE status='disetujui'")->fetch()['c'];
$totalDenda = $pdo->query("SELECT COALESCE(SUM(denda),0) c FROM pengembalian")->fetch()['c'];

$recent = $pdo->query("
    SELECT p.kode_peminjaman, u.nama_lengkap, p.tanggal_pinjam, p.status
    FROM peminjaman p JOIN users u ON u.id = p.peminjam_id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

$pageTitle = 'Dashboard Admin';
$pageSubtitle = 'Ringkasan aktivitas peminjaman alat';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= $totalUser ?></div><div class="lbl">Total User</div></div>
    <div class="stat-card"><div class="num"><?= $totalAlat ?></div><div class="lbl">Total Unit Alat</div></div>
    <div class="stat-card"><div class="num"><?= $alatTersedia ?></div><div class="lbl">Alat Tersedia</div></div>
    <div class="stat-card"><div class="num"><?= $menunggu ?></div><div class="lbl">Menunggu Persetujuan</div></div>
    <div class="stat-card"><div class="num"><?= $dipinjam ?></div><div class="lbl">Sedang Dipinjam</div></div>
    <div class="stat-card"><div class="num"><?= rupiah($totalDenda) ?></div><div class="lbl">Total Denda Terkumpul</div></div>
</div>

<div class="card">
    <h3>Peminjaman Terbaru</h3>
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Tgl Pinjam</th><th>Status</th></tr>
        <?php if (!$recent): ?>
            <tr><td colspan="4" class="text-muted">Belum ada data peminjaman.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td><?= h($r['kode_peminjaman']) ?></td>
            <td><?= h($r['nama_lengkap']) ?></td>
            <td><?= tglIndo($r['tanggal_pinjam']) ?></td>
            <td><?= badgeStatus($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
