<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }

    if (isset($_SESSION['status']) && $_SESSION['status'] !== 'aktif') {
        session_destroy();
        header('Location: ../index.php?error=nonaktif');
        exit();
        }
}

function checkRole($allowedRoles) {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        die('Akses ditolak. Anda tidak memiliki permission untuk halaman ini.');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>