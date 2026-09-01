<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('peminjam');
$pdo = getDB();
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM peminjaman WHERE peminjam_id=? AND status='menunggu'");
$stmt->execute([$uid]); $menunggu = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM peminjaman WHERE peminjam_id=? AND status='disetujui'");
$stmt->execute([$uid]); $dipinjam = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM peminjaman WHERE peminjam_id=? AND status='dikembalikan'");
$stmt->execute([$uid]); $selesai = $stmt->fetch()['c'];

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(pg.denda),0) c FROM pengembalian pg
    JOIN peminjaman p ON p.id = pg.peminjaman_id WHERE p.peminjam_id=?
");
$stmt->execute([$uid]); $totalDenda = $stmt->fetch()['c'];

$stmt = $pdo->prepare("
    SELECT p.* FROM peminjaman p WHERE p.peminjam_id=? ORDER BY p.created_at DESC LIMIT 5
");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Selamat datang, ' . $_SESSION['nama_lengkap'];
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= $menunggu ?></div><div class="lbl">Menunggu Persetujuan</div></div>
    <div class="stat-card"><div class="num"><?= $dipinjam ?></div><div class="lbl">Sedang Dipinjam</div></div>
    <div class="stat-card"><div class="num"><?= $selesai ?></div><div class="lbl">Selesai Dikembalikan</div></div>
    <div class="stat-card"><div class="num"><?= rupiah($totalDenda) ?></div><div class="lbl">Total Denda Saya</div></div>
</div>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Riwayat Terbaru</h3>
        <a href="ajukan_peminjaman.php" class="btn btn-primary btn-sm">+ Ajukan Peminjaman</a>
    </div>
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Status</th></tr>
        <?php if (!$recent): ?><tr><td colspan="4" class="text-muted">Belum ada riwayat peminjaman.</td></tr><?php endif; ?>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td><?= h($r['kode_peminjaman']) ?></td>
            <td><?= tglIndo($r['tanggal_pinjam']) ?></td>
            <td><?= tglIndo($r['tanggal_rencana_kembali']) ?></td>
            <td><?= badgeStatus($r['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
