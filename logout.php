<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    catatLog($_SESSION['user_id'], 'Logout dari sistem');
}

session_unset();
session_destroy();

header('Location: login.php');
exit;
