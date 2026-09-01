<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('petugas');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peminjamanId = (int)$_POST['peminjaman_id'];
    $catatan = trim($_POST['catatan'] ?? '');
    $petugasId = $_SESSION['user_id'];

    try {
        if ($_POST['keputusan'] === 'setuju') {
            $stmt = $pdo->prepare("CALL sp_setujui_peminjaman(?,?,?)");
            $stmt->execute([$peminjamanId, $petugasId, $catatan]);
            catatLog($petugasId, "Menyetujui peminjaman ID #$peminjamanId");
            setFlash('success', 'Peminjaman berhasil disetujui. Stok alat telah dikurangi.');
        } else {
            $stmt = $pdo->prepare("CALL sp_tolak_peminjaman(?,?,?)");
            $stmt->execute([$peminjamanId, $petugasId, $catatan]);
            catatLog($petugasId, "Menolak peminjaman ID #$peminjamanId");
            setFlash('success', 'Peminjaman berhasil ditolak.');
        }
    } catch (PDOException $e) {
        // Menangkap pesan dari SIGNAL SQLSTATE pada stored procedure (misal stok tidak cukup)
        setFlash('error', 'Gagal memproses: ' . $e->getMessage());
    }

    header('Location: approval.php');
    exit;
}

$data = $pdo->query("
    SELECT p.*, u.nama_lengkap, u.no_hp FROM peminjaman p
    JOIN users u ON u.id = p.peminjam_id
    WHERE p.status = 'menunggu'
    ORDER BY p.created_at ASC
")->fetchAll();

$detailStmt = $pdo->prepare("
    SELECT dp.jumlah, a.nama_alat, a.jumlah_tersedia FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id WHERE dp.peminjaman_id = ?
");

$pageTitle = 'Setujui Peminjaman';
$pageSubtitle = 'Tinjau dan proses pengajuan peminjaman alat';
$activeMenu = 'approval';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$data): ?>
    <div class="card"><p class="text-muted mt-0">Tidak ada pengajuan peminjaman yang menunggu persetujuan saat ini.</p></div>
<?php endif; ?>

<?php foreach ($data as $p): $detailStmt->execute([$p['id']]); $items = $detailStmt->fetchAll(); ?>
<div class="card">
    <div class="flex-between">
        <div>
            <h3 class="mt-0"><?= h($p['kode_peminjaman']) ?> — <?= h($p['nama_lengkap']) ?></h3>
            <p class="text-muted" style="margin-top:-10px;">
                Diajukan: <?= tglIndo($p['tanggal_pinjam']) ?> &rarr; Rencana kembali: <?= tglIndo($p['tanggal_rencana_kembali']) ?>
                <?php if ($p['no_hp']): ?> | HP: <?= h($p['no_hp']) ?><?php endif; ?>
            </p>
        </div>
        <?= badgeStatus($p['status']) ?>
    </div>

    <p><strong>Keperluan:</strong> <?= h($p['keperluan'] ?: '-') ?></p>

    <table style="margin-bottom:16px;">
        <tr><th>Alat</th><th>Jumlah Diminta</th><th>Stok Tersedia</th></tr>
        <?php foreach ($items as $it): ?>
        <tr>
            <td><?= h($it['nama_alat']) ?></td>
            <td><?= $it['jumlah'] ?></td>
            <td><?= $it['jumlah_tersedia'] < $it['jumlah'] ? '<span class="badge badge-danger">'.$it['jumlah_tersedia'].' (kurang)</span>' : $it['jumlah_tersedia'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="peminjaman_id" value="<?= $p['id'] ?>">
        <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
            <label>Catatan (opsional)</label>
            <input type="text" name="catatan" placeholder="Catatan untuk peminjam...">
        </div>
        <button type="submit" name="keputusan" value="setuju" class="btn btn-success">✔ Setujui</button>
        <button type="submit" name="keputusan" value="tolak" class="btn btn-danger" onclick="return confirm('Tolak peminjaman ini?')">✘ Tolak</button>
    </form>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
