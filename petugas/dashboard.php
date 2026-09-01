<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('petugas');
$pdo = getDB();

$menunggu = $pdo->query("SELECT COUNT(*) c FROM peminjaman WHERE status='menunggu'")->fetch()['c'];
$aktif = $pdo->query("SELECT COUNT(*) c FROM peminjaman WHERE status='disetujui'")->fetch()['c'];
$telat = $pdo->query("SELECT COUNT(*) c FROM peminjaman WHERE status='disetujui' AND tanggal_rencana_kembali < CURDATE()")->fetch()['c'];
$dikembalikanHariIni = $pdo->query("SELECT COUNT(*) c FROM pengembalian WHERE tanggal_kembali = CURDATE()")->fetch()['c'];

$antrian = $pdo->query("
    SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN users u ON u.id=p.peminjam_id
    WHERE p.status='menunggu' ORDER BY p.created_at ASC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard Petugas';
$pageSubtitle = 'Ringkasan tugas hari ini';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= $menunggu ?></div><div class="lbl">Menunggu Persetujuan</div></div>
    <div class="stat-card"><div class="num"><?= $aktif ?></div><div class="lbl">Sedang Dipinjam</div></div>
    <div class="stat-card"><div class="num"><?= $telat ?></div><div class="lbl">Peminjaman Terlambat</div></div>
    <div class="stat-card"><div class="num"><?= $dikembalikanHariIni ?></div><div class="lbl">Dikembalikan Hari Ini</div></div>
</div>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Antrian Menunggu Persetujuan</h3>
        <a href="approval.php" class="btn btn-primary btn-sm">Lihat Semua</a>
    </div>
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Tgl Pinjam</th><th>Rencana Kembali</th></tr>
        <?php if (!$antrian): ?><tr><td colspan="4" class="text-muted">Tidak ada antrian.</td></tr><?php endif; ?>
        <?php foreach ($antrian as $a): ?>
        <tr>
            <td><?= h($a['kode_peminjaman']) ?></td>
            <td><?= h($a['nama_lengkap']) ?></td>
            <td><?= tglIndo($a['tanggal_pinjam']) ?></td>
            <td><?= tglIndo($a['tanggal_rencana_kembali']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
