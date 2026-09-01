<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'petugas':
        header('Location: petugas/dashboard.php');
        break;
    case 'peminjam':
        header('Location: peminjam/dashboard.php');
        break;
    default:
        header('Location: login.php');
}
exit;
