<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkLogin();
checkRole(['sekretaris','ketua_pelaksana']);

$id = $_GET['id'] ?? 0;
$proker_id = $_GET['proker_id'] ?? 0;

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
    die('Dokumentasi tidak ditemukan');
}

$isSekretaris = $_SESSION['role'] === 'sekretaris';
$isKetuaPelaksana = $doc['ketua_pelaksana_id'] == $_SESSION['user_id'];

if (!($isSekretaris || $isKetuaPelaksana)) {
    die('Akses ditolak');
}


// Hapus file fisik
if (file_exists('../../' . $doc['file_path'])) {
    unlink('../../' . $doc['file_path']);
}

// Hapus dari database
$stmt = $pdo->prepare("DELETE FROM dokumentasi WHERE id = ?");
$stmt->execute([$id]);

$return_to = $_GET['return_to'] ?? 'foto';

switch ($return_to) {
    case 'proposal':
        $redirect_page = "proposal.php?proker_id=$proker_id&success=deleted";
        break;
    case 'surat':
        $redirect_page = "surat.php?proker_id=$proker_id&success=deleted";
        break;
    default:
        $redirect_page = "foto.php?proker_id=$proker_id&success=deleted";
        break;
}

header("Location: $redirect_page");
exit();


?>