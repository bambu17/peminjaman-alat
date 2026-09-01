<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('petugas');
$pdo = getDB();

$data = $pdo->query("
    SELECT p.*, u.nama_lengkap, u.no_hp,
        DATEDIFF(CURDATE(), p.tanggal_rencana_kembali) AS telat_hari
    FROM peminjaman p JOIN users u ON u.id = p.peminjam_id
    WHERE p.status = 'disetujui'
    ORDER BY p.tanggal_rencana_kembali ASC
")->fetchAll();

$detailStmt = $pdo->prepare("
    SELECT dp.jumlah, a.nama_alat FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id WHERE dp.peminjaman_id = ?
");

$pageTitle = 'Pantau Peminjaman Aktif';
$pageSubtitle = 'Semua alat yang sedang dipinjam dan status keterlambatannya';
$activeMenu = 'pantau';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>No HP</th><th>Alat</th><th>Rencana Kembali</th><th>Status Waktu</th></tr>
        <?php if (!$data): ?><tr><td colspan="6" class="text-muted">Tidak ada peminjaman aktif.</td></tr><?php endif; ?>
        <?php foreach ($data as $p): $detailStmt->execute([$p['id']]); $items = $detailStmt->fetchAll();
            $itemStr = implode(', ', array_map(fn($i) => $i['nama_alat'].' ('.$i['jumlah'].')', $items));
        ?>
        <tr>
            <td><?= h($p['kode_peminjaman']) ?></td>
            <td><?= h($p['nama_lengkap']) ?></td>
            <td><?= h($p['no_hp']) ?></td>
            <td><?= h($itemStr) ?></td>
            <td><?= tglIndo($p['tanggal_rencana_kembali']) ?></td>
            <td>
                <?php if ($p['telat_hari'] > 0): ?>
                    <span class="badge badge-danger">Terlambat <?= $p['telat_hari'] ?> hari</span>
                <?php else: ?>
                    <span class="badge badge-success">Tepat Waktu</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
