<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

// ==== HANDLE ACTIONS ====
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $hp = trim($_POST['no_hp']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'] ?? '';

    if ($_POST['form_action'] === 'create') {
        $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $check->execute([$username]);
        if ($check->fetch()) {
            setFlash('error', 'Username sudah digunakan.');
        } elseif ($password === '') {
            setFlash('error', 'Password wajib diisi untuk user baru.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username,password,nama_lengkap,email,no_hp,role,status) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $nama, $email, $hp, $role, $status]);
            catatLog($_SESSION['user_id'], "Menambah user baru: $username ($role)");
            setFlash('success', 'User berhasil ditambahkan.');
        }
    } elseif ($_POST['form_action'] === 'update') {
        $uid = $_POST['id'];
        if ($password !== '') {
            $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, no_hp=?, role=?, status=? WHERE id=?");
            $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $nama, $email, $hp, $role, $status, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, email=?, no_hp=?, role=?, status=? WHERE id=?");
            $stmt->execute([$username, $nama, $email, $hp, $role, $status, $uid]);
        }
        catatLog($_SESSION['user_id'], "Mengubah data user: $username");
        setFlash('success', 'User berhasil diperbarui.');
    }
    header('Location: users.php');
    exit;
}

if ($action === 'delete' && $id) {
    if ((int)$id === (int)$_SESSION['user_id']) {
        setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            catatLog($_SESSION['user_id'], "Menghapus user ID #$id");
            setFlash('success', 'User berhasil dihapus.');
        } catch (PDOException $e) {
            setFlash('error', 'User tidak dapat dihapus karena masih memiliki data transaksi terkait.');
        }
    }
    header('Location: users.php');
    exit;
}

$editUser = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR nama_lengkap LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}
$users = $stmt->fetchAll();

$pageTitle = 'Data User';
$pageSubtitle = 'Kelola akun Admin, Petugas, dan Peminjam';
$activeMenu = 'users';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h3><?= $editUser ? 'Edit User' : 'Tambah User Baru' ?></h3>
    <form method="POST" action="users.php">
        <input type="hidden" name="form_action" value="<?= $editUser ? 'update' : 'create' ?>">
        <?php if ($editUser): ?><input type="hidden" name="id" value="<?= h($editUser['id']) ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="<?= h($editUser['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password <?= $editUser ? '(kosongkan jika tidak diubah)' : '' ?></label>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required value="<?= h($editUser['nama_lengkap'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($editUser['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>No HP</label>
                <input type="text" name="no_hp" value="<?= h($editUser['no_hp'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <?php foreach (['admin'=>'Admin','petugas'=>'Petugas','peminjam'=>'Peminjam'] as $val=>$lbl): ?>
                        <option value="<?= $val ?>" <?= ($editUser['role'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group" style="max-width:220px;">
            <label>Status</label>
            <select name="status">
                <option value="aktif" <?= ($editUser['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= ($editUser['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editUser ? 'Simpan Perubahan' : 'Tambah User' ?></button>
            <?php if ($editUser): ?><a href="users.php" class="btn btn-secondary">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="flex-between">
        <h3 class="mt-0">Daftar User</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="q" placeholder="Cari nama/username..." value="<?= h($search) ?>" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;">
            <button class="btn btn-outline btn-sm" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
    <table>
        <tr><th>Username</th><th>Nama</th><th>Email</th><th>No HP</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= h($u['username']) ?></td>
            <td><?= h($u['nama_lengkap']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['no_hp']) ?></td>
            <td><span class="badge badge-secondary"><?= h(ucfirst($u['role'])) ?></span></td>
            <td><?= $u['status'] === 'aktif' ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>' ?></td>
            <td>
                <a class="btn btn-sm btn-outline" href="?action=edit&id=<?= $u['id'] ?>">Edit</a>
                <a class="btn btn-sm btn-danger" href="?action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
