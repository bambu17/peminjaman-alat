<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_alat']);
    $nama = trim($_POST['nama_alat']);
    $kategori_id = $_POST['kategori_id'];
    $jumlah_total = (int)$_POST['jumlah_total'];
    $kondisi = $_POST['kondisi'];
    $lokasi = trim($_POST['lokasi_simpan']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($_POST['form_action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO alat (kode_alat,nama_alat,kategori_id,jumlah_total,jumlah_tersedia,kondisi,lokasi_simpan,deskripsi) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$kode, $nama, $kategori_id, $jumlah_total, $jumlah_total, $kondisi, $lokasi, $deskripsi]);
        catatLog($_SESSION['user_id'], "Menambah alat: $nama ($kode)");
        setFlash('success', 'Alat berhasil ditambahkan.');
    } else {
        // Saat update, sesuaikan jumlah_tersedia proporsional terhadap perubahan jumlah_total
        $old = $pdo->prepare("SELECT jumlah_total, jumlah_tersedia FROM alat WHERE id=?");
        $old->execute([$_POST['id']]);
        $oldData = $old->fetch();
        $selisih = $jumlah_total - $oldData['jumlah_total'];
        $jumlah_tersedia_baru = max(0, $oldData['jumlah_tersedia'] + $selisih);

        $stmt = $pdo->prepare("UPDATE alat SET kode_alat=?, nama_alat=?, kategori_id=?, jumlah_total=?, jumlah_tersedia=?, kondisi=?, lokasi_simpan=?, deskripsi=? WHERE id=?");
        $stmt->execute([$kode, $nama, $kategori_id, $jumlah_total, $jumlah_tersedia_baru, $kondisi, $lokasi, $deskripsi, $_POST['id']]);
        catatLog($_SESSION['user_id'], "Mengubah alat: $nama ($kode)");
        setFlash('success', 'Alat berhasil diperbarui.');
    }
    header('Location: alat.php');
    exit;
}

if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM alat WHERE id=?");
        $stmt->execute([$id]);
        setFlash('success', 'Alat berhasil dihapus.');
    } catch (PDOException $e) {
        setFlash('error', 'Alat tidak dapat dihapus karena memiliki riwayat transaksi peminjaman.');
    }
    header('Location: alat.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM alat WHERE id=?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id=a.kategori_id";
if ($search !== '') {
    $sql .= " WHERE a.nama_alat LIKE :q OR a.kode_alat LIKE :q";
}
$sql .= " ORDER BY a.nama_alat";
$stmt = $pdo->prepare($sql);
if ($search !== '') $stmt->execute(['q' => "%$search%"]); else $stmt->execute();
$alatList = $stmt->fetchAll();

$pageTitle = 'Data Alat';
$activeMenu = 'alat';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h3><?= $editData ? 'Edit Alat' : 'Tambah Alat Baru' ?></h3>
    <?php if (empty($kategoriList)): ?>
        <div class="alert alert-info">Tambahkan kategori terlebih dahulu sebelum menambah data alat.</div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="form_action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>Kode Alat</label>
                <input type="text" name="kode_alat" required value="<?= h($editData['kode_alat'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Nama Alat</label>
                <input type="text" name="nama_alat" required value="<?= h($editData['nama_alat'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori_id" required>
                    <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($editData['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= h($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah Total Unit</label>
                <input type="number" min="0" name="jumlah_total" required value="<?= h($editData['jumlah_total'] ?? 1) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kondisi</label>
                <select name="kondisi">
                    <?php foreach (['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_berat'=>'Rusak Berat'] as $val=>$lbl): ?>
                    <option value="<?= $val ?>" <?= ($editData['kondisi'] ?? 'baik') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Lokasi Simpan</label>
                <input type="text" name="lokasi_simpan" value="<?= h($editData['lokasi_simpan'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="2"><?= h($editData['deskripsi'] ?? '') ?></textarea>
        </div>
        <?php if ($editData): ?>
        <div class="alert alert-info">Alat tersedia saat ini: <strong><?= $editData['jumlah_tersedia'] ?></strong> dari <?= $editData['jumlah_total'] ?> unit. Mengubah "Jumlah Total" akan menyesuaikan stok tersedia secara proporsional.</div>
        <?php endif; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $editData ? 'Simpan' : 'Tambah Alat' ?></button>
            <?php if ($editData): ?><a href="alat.php" class="btn btn-secondary">Batal</a><?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Daftar Alat</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="q" placeholder="Cari kode/nama alat..." value="<?= h($search) ?>" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;">
            <button class="btn btn-outline btn-sm" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
    <table>
        <tr><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th>Total</th><th>Tersedia</th><th>Kondisi</th><th>Lokasi</th><th>Aksi</th></tr>
        <?php foreach ($alatList as $a): ?>
        <tr>
            <td><?= h($a['kode_alat']) ?></td>
            <td><?= h($a['nama_alat']) ?></td>
            <td><?= h($a['nama_kategori']) ?></td>
            <td><?= $a['jumlah_total'] ?></td>
            <td><?= $a['jumlah_tersedia'] ?></td>
            <td><?= h(ucwords(str_replace('_',' ',$a['kondisi']))) ?></td>
            <td><?= h($a['lokasi_simpan']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline" href="?action=edit&id=<?= $a['id'] ?>">Edit</a>
                <a class="btn btn-sm btn-danger" href="?action=delete&id=<?= $a['id'] ?>" onclick="return confirm('Hapus alat ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
