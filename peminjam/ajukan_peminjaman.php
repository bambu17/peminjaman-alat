<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('peminjam');
$pdo = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tglPinjam = $_POST['tanggal_pinjam'];
    $tglKembali = $_POST['tanggal_rencana_kembali'];
    $keperluan = trim($_POST['keperluan']);
    $alatIds = $_POST['alat_id'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];

    // Validasi dasar
    $items = [];
    foreach ($alatIds as $i => $aid) {
        $jml = (int)($jumlahs[$i] ?? 0);
        if ($aid && $jml > 0) {
            $items[$aid] = ($items[$aid] ?? 0) + $jml;
        }
    }

    if (empty($items)) {
        setFlash('error', 'Pilih minimal satu alat dengan jumlah lebih dari 0.');
        header('Location: ajukan_peminjaman.php'); exit;
    }
    if (strtotime($tglKembali) < strtotime($tglPinjam)) {
        setFlash('error', 'Tanggal rencana kembali tidak boleh sebelum tanggal pinjam.');
        header('Location: ajukan_peminjaman.php'); exit;
    }

    // Cek stok cukup
    $cekStmt = $pdo->prepare("SELECT jumlah_tersedia, nama_alat FROM alat WHERE id=?");
    foreach ($items as $aid => $jml) {
        $cekStmt->execute([$aid]);
        $a = $cekStmt->fetch();
        if (!$a || $a['jumlah_tersedia'] < $jml) {
            setFlash('error', 'Stok alat "' . ($a['nama_alat'] ?? '') . '" tidak mencukupi (tersedia ' . ($a['jumlah_tersedia'] ?? 0) . ').');
            header('Location: ajukan_peminjaman.php'); exit;
        }
    }

    try {
        $pdo->beginTransaction();

        $kode = generateKode('PJM', 'peminjaman', 'kode_peminjaman');
        $stmt = $pdo->prepare("INSERT INTO peminjaman (kode_peminjaman, peminjam_id, tanggal_pinjam, tanggal_rencana_kembali, keperluan, status) VALUES (?,?,?,?,?,'menunggu')");
        $stmt->execute([$kode, $uid, $tglPinjam, $tglKembali, $keperluan]);
        $peminjamanId = $pdo->lastInsertId();

        $detailStmt = $pdo->prepare("INSERT INTO detail_peminjaman (peminjaman_id, alat_id, jumlah) VALUES (?,?,?)");
        foreach ($items as $aid => $jml) {
            $detailStmt->execute([$peminjamanId, $aid, $jml]);
        }

        $pdo->commit();
        setFlash('success', "Pengajuan peminjaman berhasil dibuat dengan kode $kode. Menunggu persetujuan petugas.");
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Gagal menyimpan pengajuan: ' . $e->getMessage());
    }

    header('Location: riwayat.php');
    exit;
}

$alatList = $pdo->query("
    SELECT a.id, a.kode_alat, a.nama_alat, a.jumlah_tersedia, k.nama_kategori
    FROM alat a JOIN kategori k ON k.id=a.kategori_id
    WHERE a.jumlah_tersedia > 0 ORDER BY a.nama_alat
")->fetchAll();

$pageTitle = 'Ajukan Peminjaman';
$pageSubtitle = 'Pilih alat yang ingin dipinjam dan tentukan periode peminjaman';
$activeMenu = 'ajukan';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <?php if (!$alatList): ?>
        <p class="text-muted mt-0">Saat ini tidak ada alat yang tersedia untuk dipinjam.</p>
    <?php else: ?>
    <form method="POST" id="formPinjam">
        <div class="form-row">
            <div class="form-group">
                <label>Tanggal Pinjam</label>
                <input type="date" name="tanggal_pinjam" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Rencana Tanggal Kembali</label>
                <input type="date" name="tanggal_rencana_kembali" required min="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Keperluan</label>
            <input type="text" name="keperluan" placeholder="Contoh: Praktik jaringan kelas XII RPL" required>
        </div>

        <h3>Pilih Alat</h3>
        <div id="itemRows">
            <div class="form-row item-row">
                <div class="form-group">
                    <label>Alat</label>
                    <select name="alat_id[]">
                        <option value="">- Pilih Alat -</option>
                        <?php foreach ($alatList as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= h($a['nama_alat']) ?> — tersedia <?= $a['jumlah_tersedia'] ?> (<?= h($a['nama_kategori']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah[]" min="1" value="1">
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="tambahBaris()">+ Tambah Alat Lain</button>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function tambahBaris() {
    const container = document.getElementById('itemRows');
    const clone = container.firstElementChild.cloneNode(true);
    clone.querySelector('select').value = '';
    clone.querySelector('input').value = 1;
    container.appendChild(clone);
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
