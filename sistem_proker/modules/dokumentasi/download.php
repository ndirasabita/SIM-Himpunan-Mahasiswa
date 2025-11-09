<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkLogin();

$id = $_GET['id'] ?? 0;

// Ambil data dokumentasi
$stmt = $pdo->prepare("
    SELECT d.*, pk.ketua_pelaksana_id 
    FROM dokumentasi d 
    JOIN program_kerja pk ON d.proker_id = pk.id 
    WHERE d.id = ?
");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('File tidak ditemukan');
}

// Cek akses
$hasAccess = false;
if ($_SESSION['role'] == 'sekretaris' || $_SESSION['role'] == 'ketua_umum') {
    $hasAccess = true;
} elseif ($_SESSION['role'] == 'ketua_pelaksana' && $doc['ketua_pelaksana_id'] == $_SESSION['user_id']) {
    $hasAccess = true;
}

if (!$hasAccess) {
    die('Akses ditolak');
}

$filePath = '../../' . $doc['file_path'];

if (file_exists($filePath)) {
    // Set headers untuk download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $doc['nama_file'] . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);
    exit();
} else {
    die('File tidak ditemukan di server');
}
?>