<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();

$evaluasi_id = $_GET['id'] ?? 0;

// Ambil evaluasi
$stmt = $pdo->prepare("SELECT * FROM evaluasi WHERE id = ?");
$stmt->execute([$evaluasi_id]);
$evaluasi = $stmt->fetch();

if (!$evaluasi) {
    die('Evaluasi tidak ditemukan.');
}

$proker_id = $evaluasi['proker_id'];

// Ambil data program kerja terkait
$stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ?");
$stmt->execute([$proker_id]);
$proker = $stmt->fetch();

if (!$proker) {
    die('Program kerja tidak ditemukan.');
}

// Hak akses hanya untuk ketua pelaksana & sekretaris
$canDelete = false;

if ($_SESSION['role'] === 'ketua_pelaksana') {
    if ($proker['ketua_pelaksana_id'] == $_SESSION['user_id'] || !$proker['ketua_pelaksana_id']) {
        if ($evaluasi['created_by'] == $_SESSION['user_id']) {
            $canDelete = true;
        }
    }
} elseif ($_SESSION['role'] === 'sekretaris') {
    if ($evaluasi['created_by'] == $_SESSION['user_id']) {
        $canDelete = true;
    }
}

if (!$canDelete) {
    die('Anda tidak memiliki hak untuk menghapus evaluasi ini.');
}

// Lakukan penghapusan
try {
    $stmt = $pdo->prepare("DELETE FROM evaluasi WHERE id = ?");
    $stmt->execute([$evaluasi_id]);

    header('Location: manage_evaluasi.php?proker_id=' . $proker_id);
    exit();
} catch (PDOException $e) {
    die('Gagal menghapus evaluasi: ' . $e->getMessage());
}
