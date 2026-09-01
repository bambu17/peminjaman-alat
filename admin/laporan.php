<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT p.kode_peminjaman, u.nama_lengkap AS peminjam, p.tanggal_pinjam, p.tanggal_rencana_kembali,
           p.status, pg.tanggal_kembali, pg.telat_hari, pg.denda,
           GROUP_CONCAT(CONCAT(a.nama_alat,' (',dp.jumlah,')') SEPARATOR ', ') AS daftar_alat
    FROM peminjaman p
    JOIN users u ON u.id = p.peminjam_id
    JOIN detail_peminjaman dp ON dp.peminjaman_id = p.id
    JOIN alat a ON a.id = dp.alat_id
    LEFT JOIN pengembalian pg ON pg.peminjaman_id = p.id
    WHERE p.tanggal_pinjam BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY p.tanggal_pinjam DESC
");
$stmt->execute([$dari, $sampai]);
$data = $stmt->fetchAll();

$totalDenda = array_sum(array_column($data, 'denda'));
$totalTransaksi = count($data);

$pageTitle = 'Cetak Laporan';
$pageSubtitle = 'Laporan rekap peminjaman & pengembalian per periode';
$activeMenu = 'laporan';
require __DIR__ . '/../includes/header.php';
?>

<div class="card no-print">
    <h3 class="mt-0">Filter Periode</h3>
    <form method="GET" class="form-row" style="align-items:flex-end;">
        <div class="form-group">
            <label>Dari Tanggal</label>
            <input type="date" name="dari" value="<?= h($dari) ?>">
        </div>
        <div class="form-group">
            <label>Sampai Tanggal</label>
            <input type="date" name="sampai" value="<?= h($sampai) ?>">
        </div>
        <div class="form-group">
            <button class="btn btn-primary" type="submit">Tampilkan</button>
            <button class="btn btn-secondary" type="button" onclick="window.print()">🖨️ Cetak</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Laporan Peminjaman Alat</h3>
    <p class="text-muted">Periode: <?= tglIndo($dari) ?> s/d <?= tglIndo($sampai) ?></p>
    <p><strong>Total Transaksi:</strong> <?= $totalTransaksi ?> &nbsp;|&nbsp; <strong>Total Denda:</strong> <?= rupiah($totalDenda) ?></p>

    <div class="table-wrap">
    <table>
        <tr>
            <th>Kode</th><th>Peminjam</th><th>Alat</th><th>Tgl Pinjam</th>
            <th>Rencana Kembali</th><th>Tgl Kembali</th><th>Status</th><th>Telat</th><th>Denda</th>
        </tr>
        <?php if (!$data): ?><tr><td colspan="9" class="text-muted">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
        <?php foreach ($data as $d): ?>
        <tr>
            <td><?= h($d['kode_peminjaman']) ?></td>
            <td><?= h($d['peminjam']) ?></td>
            <td><?= h($d['daftar_alat']) ?></td>
            <td><?= tglIndo($d['tanggal_pinjam']) ?></td>
            <td><?= tglIndo($d['tanggal_rencana_kembali']) ?></td>
            <td><?= $d['tanggal_kembali'] ? tglIndo($d['tanggal_kembali']) : '-' ?></td>
            <td><?= badgeStatus($d['status']) ?></td>
            <td><?= $d['telat_hari'] ?? 0 ?> hari</td>
            <td><?= rupiah($d['denda'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .content { padding: 0; }
    body { background: #fff; }
}
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>
