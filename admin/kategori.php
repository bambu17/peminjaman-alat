<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($_POST['form_action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?,?)");
        $stmt->execute([$nama, $deskripsi]);
        catatLog($_SESSION['user_id'], "Menambah kategori: $nama");
        setFlash('success', 'Kategori berhasil ditambahkan.');
    } else {
        $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori=?, deskripsi=? WHERE id=?");
        $stmt->execute([$nama, $deskripsi, $_POST['id']]);
        catatLog($_SESSION['user_id'], "Mengubah kategori: $nama");
        setFlash('success', 'Kategori berhasil diperbarui.');
    }
    header('Location: kategori.php');
    exit;
}

if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id=?");
        $stmt->execute([$id]);
        setFlash('success', 'Kategori berhasil dihapus.');
    } catch (PDOException $e) {
        setFlash('error', 'Kategori tidak dapat dihapus karena masih dipakai oleh data alat.');
    }
    header('Location: kategori.php');
    exit;
}

$editData = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id=?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$list = $pdo->query("
    SELECT k.*, COUNT(a.id) AS jumlah_alat
    FROM kategori k LEFT JOIN alat a ON a.kategori_id = k.id
    GROUP BY k.id ORDER BY k.nama_kategori
")->fetchAll();

$pageTitle = 'Kategori Alat';
$activeMenu = 'kategori';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h3><?= $editData ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
    <form method="POST">
        <input type="hidden" name="form_action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" required value="<?= h($editData['nama_kategori'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="deskripsi" value="<?= h($editData['deskripsi'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $editData ? 'Simpan' : 'Tambah' ?></button>
            <?php if ($editData): ?><a href="kategori.php" class="btn btn-secondary">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="mt-0">Daftar Kategori</h3>
    <div class="table-wrap">
    <table>
        <tr><th>Nama Kategori</th><th>Deskripsi</th><th>Jumlah Jenis Alat</th><th>Aksi</th></tr>
        <?php foreach ($list as $k): ?>
        <tr>
            <td><?= h($k['nama_kategori']) ?></td>
            <td><?= h($k['deskripsi']) ?></td>
            <td><?= $k['jumlah_alat'] ?></td>
            <td>
                <a class="btn btn-sm btn-outline" href="?action=edit&id=<?= $k['id'] ?>">Edit</a>
                <a class="btn btn-sm btn-danger" href="?action=delete&id=<?= $k['id'] ?>" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
