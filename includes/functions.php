<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Cek apakah user sudah login */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Wajib login, kalau tidak redirect ke login.php */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('login.php'));
        exit;
    }
}

/** Wajib role tertentu. $roles bisa string atau array */
function requireRole($roles) {
    requireLogin();
    if (is_string($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        die('<h2>403 - Anda tidak memiliki akses ke halaman ini.</h2><a href="' . baseUrl('index.php') . '">Kembali</a>');
    }
}

/** Base URL relatif sederhana (project ditaruh di root folder htdocs Laragon) */
function baseUrl($path = '') {
    // Ambil folder root project secara otomatis
    $root = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Jika berada di subfolder (admin/petugas/peminjam), naik satu level
    $parts = explode('/', trim($root, '/'));
    $last = end($parts);
    if (in_array($last, ['admin', 'petugas', 'peminjam'])) {
        array_pop($parts);
    }
    $base = '/' . implode('/', $parts);
    $base = rtrim($base, '/');
    return $base . '/' . ltrim($path, '/');
}

/** Simpan pesan flash (notifikasi sekali tampil) */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Catat log aktifitas manual (di luar trigger DB) */
function catatLog($userId, $aktivitas) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO log_aktifitas (user_id, aktivitas) VALUES (?, ?)");
    $stmt->execute([$userId, $aktivitas]);
}

/** Generate kode unik, contoh: PJM-20260824-0001 */
function generateKode($prefix, $table, $column) {
    $pdo = getDB();
    $tanggal = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS jml FROM $table WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $jml = $stmt->fetch()['jml'] + 1;
    return sprintf('%s-%s-%04d', $prefix, $tanggal, $jml);
}

/** Format rupiah */
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/** Format tanggal Indonesia */
function tglIndo($tanggal) {
    if (!$tanggal) return '-';
    $bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Sanitize output HTML */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Badge status peminjaman (untuk tampilan) */
function badgeStatus($status) {
    $map = [
        'menunggu'    => 'badge-warning',
        'disetujui'   => 'badge-success',
        'ditolak'     => 'badge-danger',
        'dikembalikan'=> 'badge-info',
    ];
    $class = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
}
