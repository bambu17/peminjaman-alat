<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('peminjam');
$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id = a.kategori_id";
if ($search !== '') $sql .= " WHERE a.nama_alat LIKE :q";
$sql .= " ORDER BY k.nama_kategori, a.nama_alat";
$stmt = $pdo->prepare($sql);
if ($search !== '') $stmt->execute(['q' => "%$search%"]); else $stmt->execute();
$alat = $stmt->fetchAll();

$pageTitle = 'Daftar Alat';
$pageSubtitle = 'Lihat ketersediaan alat sebelum mengajukan peminjaman';
$activeMenu = 'daftar_alat';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Cari Alat</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="q" placeholder="Cari nama alat..." value="<?= h($search) ?>" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;">
            <button class="btn btn-outline btn-sm" type="submit">Cari</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th>Tersedia</th><th>Kondisi</th><th>Lokasi</th></tr>
        <?php if (!$alat): ?><tr><td colspan="6" class="text-muted">Alat tidak ditemukan.</td></tr><?php endif; ?>
        <?php foreach ($alat as $a): ?>
        <tr>
            <td><?= h($a['kode_alat']) ?></td>
            <td><?= h($a['nama_alat']) ?></td>
            <td><?= h($a['nama_kategori']) ?></td>
            <td><?= $a['jumlah_tersedia'] > 0 ? '<span class="badge badge-success">'.$a['jumlah_tersedia'].' unit</span>' : '<span class="badge badge-danger">Habis</span>' ?></td>
            <td><?= h(ucwords(str_replace('_',' ',$a['kondisi']))) ?></td>
            <td><?= h($a['lokasi_simpan']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<a href="ajukan_peminjaman.php" class="btn btn-primary">+ Ajukan Peminjaman</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>
