<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('petugas');
$pdo = getDB();

$previewDenda = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peminjamanId = (int)$_POST['peminjaman_id'];
    $tanggalKembali = $_POST['tanggal_kembali'];
    $kondisi = $_POST['kondisi_alat'];
    $catatan = trim($_POST['catatan'] ?? '');
    $petugasId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("CALL sp_kembalikan_alat(?,?,?,?,?)");
        $stmt->execute([$peminjamanId, $petugasId, $tanggalKembali, $kondisi, $catatan]);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        catatLog($petugasId, "Memproses pengembalian untuk peminjaman ID #$peminjamanId");

        if ($result && $result['denda'] > 0) {
            setFlash('success', "Pengembalian berhasil diproses. Terlambat {$result['telat_hari']} hari, denda: " . rupiah($result['denda']));
        } else {
            setFlash('success', 'Pengembalian berhasil diproses tanpa denda.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
    }

    header('Location: pengembalian.php');
    exit;
}

$data = $pdo->query("
    SELECT p.*, u.nama_lengkap FROM peminjaman p
    JOIN users u ON u.id = p.peminjam_id
    WHERE p.status = 'disetujui'
    ORDER BY p.tanggal_rencana_kembali ASC
")->fetchAll();

$detailStmt = $pdo->prepare("
    SELECT dp.jumlah, a.nama_alat FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id WHERE dp.peminjaman_id = ?
");

$pageTitle = 'Proses Pengembalian';
$pageSubtitle = 'Catat pengembalian alat dan hitung denda keterlambatan otomatis';
$activeMenu = 'pengembalian';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$data): ?>
    <div class="card"><p class="text-muted mt-0">Tidak ada alat yang sedang dipinjam saat ini.</p></div>
<?php endif; ?>

<?php foreach ($data as $p): $detailStmt->execute([$p['id']]); $items = $detailStmt->fetchAll();
    $terlambat = strtotime(date('Y-m-d')) > strtotime($p['tanggal_rencana_kembali']);
?>
<div class="card">
    <div class="flex-between">
        <div>
            <h3 class="mt-0"><?= h($p['kode_peminjaman']) ?> — <?= h($p['nama_lengkap']) ?></h3>
            <p class="text-muted" style="margin-top:-10px;">
                Dipinjam: <?= tglIndo($p['tanggal_pinjam']) ?> | Rencana kembali: <?= tglIndo($p['tanggal_rencana_kembali']) ?>
            </p>
        </div>
        <?= $terlambat ? '<span class="badge badge-danger">Terlambat</span>' : '<span class="badge badge-info">Dalam Batas Waktu</span>' ?>
    </div>

    <table style="margin-bottom:16px;">
        <tr><th>Alat Dipinjam</th><th>Jumlah</th></tr>
        <?php foreach ($items as $it): ?>
            <tr><td><?= h($it['nama_alat']) ?></td><td><?= $it['jumlah'] ?></td></tr>
        <?php endforeach; ?>
    </table>

    <form method="POST" class="form-row" style="align-items:flex-end;">
        <input type="hidden" name="peminjaman_id" value="<?= $p['id'] ?>">
        <div class="form-group">
            <label>Tanggal Kembali</label>
            <input type="date" name="tanggal_kembali" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label>Kondisi Alat</label>
            <select name="kondisi_alat">
                <option value="baik">Baik</option>
                <option value="rusak_ringan">Rusak Ringan</option>
                <option value="rusak_berat">Rusak Berat</option>
                <option value="hilang">Hilang</option>
            </select>
        </div>
        <div class="form-group">
            <label>Catatan</label>
            <input type="text" name="catatan" placeholder="Opsional">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Proses Pengembalian</button>
        </div>
    </form>
    <p class="text-muted" style="font-size:12px;">Denda dihitung otomatis oleh sistem: Rp5.000 x jumlah alat x hari keterlambatan (dihitung dari tanggal rencana kembali).</p>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
