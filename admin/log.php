<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pdo = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = $pdo->query("SELECT COUNT(*) c FROM log_aktifitas")->fetch()['c'];
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT l.*, u.nama_lengkap, u.username FROM log_aktifitas l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.waktu DESC LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$pageTitle = 'Log Aktifitas';
$pageSubtitle = 'Riwayat aktivitas seluruh pengguna sistem';
$activeMenu = 'log';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="table-wrap">
    <table>
        <tr><th>Waktu</th><th>User</th><th>Aktivitas</th></tr>
        <?php if (!$logs): ?><tr><td colspan="3" class="text-muted">Belum ada log.</td></tr><?php endif; ?>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td style="white-space:nowrap;"><?= date('d/m/Y H:i:s', strtotime($l['waktu'])) ?></td>
            <td><?= h($l['nama_lengkap'] ?? 'Sistem/Tidak dikenal') ?></td>
            <td><?= h($l['aktivitas']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="margin-top:16px;display:flex;gap:6px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
