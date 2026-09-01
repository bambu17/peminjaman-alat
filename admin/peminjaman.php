<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT p.*, u.nama_lengkap AS nama_peminjam, pt.nama_lengkap AS nama_petugas
    FROM peminjaman p
    JOIN users u ON u.id = p.peminjam_id
    LEFT JOIN users pt ON pt.id = p.petugas_id
";
$params = [];
if ($statusFilter) {
    $sql .= " WHERE p.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Ambil detail alat per peminjaman
$detailStmt = $pdo->prepare("
    SELECT dp.jumlah, a.nama_alat FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id WHERE dp.peminjaman_id = ?
");

$pageTitle = 'Data Peminjaman';
$pageSubtitle = 'Seluruh transaksi peminjaman alat (semua status)';
$activeMenu = 'peminjaman';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Filter Status</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <select name="status" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;">
                <option value="">Semua Status</option>
                <?php foreach (['menunggu','disetujui','ditolak','dikembalikan'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Alat</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Petugas</th><th>Status</th></tr>
        <?php if (!$data): ?><tr><td colspan="7" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
        <?php foreach ($data as $p): ?>
        <?php
            $detailStmt->execute([$p['id']]);
            $items = $detailStmt->fetchAll();
            $itemStr = implode(', ', array_map(fn($i) => $i['nama_alat'] . ' (' . $i['jumlah'] . ')', $items));
        ?>
        <tr>
            <td><?= h($p['kode_peminjaman']) ?></td>
            <td><?= h($p['nama_peminjam']) ?></td>
            <td><?= h($itemStr) ?></td>
            <td><?= tglIndo($p['tanggal_pinjam']) ?></td>
            <td><?= tglIndo($p['tanggal_rencana_kembali']) ?></td>
            <td><?= h($p['nama_petugas'] ?? '-') ?></td>
            <td><?= badgeStatus($p['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
