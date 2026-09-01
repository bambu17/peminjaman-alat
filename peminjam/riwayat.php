<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('peminjam');
$pdo = getDB();
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.*, pg.tanggal_kembali, pg.telat_hari, pg.denda, pg.kondisi_alat
    FROM peminjaman p
    LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
    WHERE p.peminjam_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$uid]);
$data = $stmt->fetchAll();

$detailStmt = $pdo->prepare("
    SELECT dp.jumlah, a.nama_alat FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id WHERE dp.peminjaman_id = ?
");

$pageTitle = 'Riwayat Peminjaman';
$pageSubtitle = 'Seluruh riwayat pengajuan peminjaman Anda';
$activeMenu = 'riwayat';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$data): ?>
    <div class="card"><p class="text-muted mt-0">Anda belum pernah mengajukan peminjaman.</p></div>
<?php endif; ?>

<?php foreach ($data as $p): $detailStmt->execute([$p['id']]); $items = $detailStmt->fetchAll(); ?>
<div class="card">
    <div class="flex-between">
        <div>
            <h3 class="mt-0"><?= h($p['kode_peminjaman']) ?></h3>
            <p class="text-muted" style="margin-top:-10px;">
                <?= tglIndo($p['tanggal_pinjam']) ?> &rarr; <?= tglIndo($p['tanggal_rencana_kembali']) ?>
            </p>
        </div>
        <?= badgeStatus($p['status']) ?>
    </div>

    <table style="margin-bottom:10px;">
        <tr><th>Alat</th><th>Jumlah</th></tr>
        <?php foreach ($items as $it): ?>
            <tr><td><?= h($it['nama_alat']) ?></td><td><?= $it['jumlah'] ?></td></tr>
        <?php endforeach; ?>
    </table>

    <?php if ($p['keperluan']): ?><p><strong>Keperluan:</strong> <?= h($p['keperluan']) ?></p><?php endif; ?>
    <?php if ($p['catatan_petugas']): ?><p><strong>Catatan petugas:</strong> <?= h($p['catatan_petugas']) ?></p><?php endif; ?>

    <?php if ($p['status'] === 'dikembalikan'): ?>
        <div class="alert alert-info" style="margin-bottom:0;">
            Dikembalikan pada <?= tglIndo($p['tanggal_kembali']) ?>, kondisi: <?= h(ucwords(str_replace('_',' ',$p['kondisi_alat']))) ?>.
            <?php if ($p['denda'] > 0): ?>
                Terlambat <strong><?= $p['telat_hari'] ?> hari</strong>, denda: <strong><?= rupiah($p['denda']) ?></strong>.
            <?php else: ?>
                Tidak ada denda.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
