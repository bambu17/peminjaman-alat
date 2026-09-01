<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$data = $pdo->query("
    SELECT pg.*, p.kode_peminjaman, u.nama_lengkap AS nama_peminjam, pt.nama_lengkap AS nama_petugas
    FROM pengembalian pg
    JOIN peminjaman p ON p.id = pg.peminjaman_id
    JOIN users u ON u.id = p.peminjam_id
    LEFT JOIN users pt ON pt.id = pg.petugas_id
    ORDER BY pg.created_at DESC
")->fetchAll();

$totalDenda = array_sum(array_column($data, 'denda'));

$pageTitle = 'Data Pengembalian';
$pageSubtitle = 'Riwayat pengembalian alat & denda keterlambatan';
$activeMenu = 'pengembalian';
require __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card"><div class="num"><?= count($data) ?></div><div class="lbl">Total Transaksi Pengembalian</div></div>
    <div class="stat-card"><div class="num"><?= rupiah($totalDenda) ?></div><div class="lbl">Total Denda</div></div>
</div>

<div class="card">
    <div class="table-wrap">
    <table>
        <tr><th>Kode Peminjaman</th><th>Peminjam</th><th>Tgl Kembali</th><th>Kondisi</th><th>Telat (hari)</th><th>Denda</th><th>Diproses Oleh</th></tr>
        <?php if (!$data): ?><tr><td colspan="7" class="text-muted">Belum ada data pengembalian.</td></tr><?php endif; ?>
        <?php foreach ($data as $d): ?>
        <tr>
            <td><?= h($d['kode_peminjaman']) ?></td>
            <td><?= h($d['nama_peminjam']) ?></td>
            <td><?= tglIndo($d['tanggal_kembali']) ?></td>
            <td><?= h(ucwords(str_replace('_',' ',$d['kondisi_alat']))) ?></td>
            <td><?= $d['telat_hari'] ?></td>
            <td><?= rupiah($d['denda']) ?></td>
            <td><?= h($d['nama_petugas'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
