<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();

$id = $_GET['id'] ?? 0;

// Ambil detail program kerja
$stmt = $pdo->prepare("
    SELECT pk.*, u1.nama as ketua_nama, u2.nama as created_by_nama
    FROM program_kerja pk 
    LEFT JOIN users u1 ON pk.ketua_pelaksana_id = u1.id 
    LEFT JOIN users u2 ON pk.created_by = u2.id 
    WHERE pk.id = ?
");
$stmt->execute([$id]);
$proker = $stmt->fetch();

if (!$proker) {
    header('Location: manage_proker.php');
    exit();
}

// Ambil dokumentasi
$stmt = $pdo->prepare("
    SELECT d.*, u.nama as uploaded_by_nama 
    FROM dokumentasi d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    WHERE d.proker_id = ? 
    ORDER BY d.jenis, d.uploaded_at DESC
");
$stmt->execute([$id]);
$dokumentasi_list = $stmt->fetchAll();

// Ambil evaluasi
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM program_kerja WHERE ketua_pelaksana_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalProker = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM program_kerja WHERE ketua_pelaksana_id = ? AND status = 'selesai'");
$stmt->execute([$_SESSION['user_id']]);
$prokerSelesai = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM program_kerja WHERE ketua_pelaksana_id = ? AND status = 'sedang_berjalan'");
$stmt->execute([$_SESSION['user_id']]);
$prokerBerjalan = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM evaluasi e JOIN program_kerja pk ON e.proker_id = pk.id WHERE pk.ketua_pelaksana_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalEvaluasi = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM dokumentasi d JOIN program_kerja pk ON d.proker_id = pk.id WHERE pk.ketua_pelaksana_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalDokumentasi = $stmt->fetchColumn();
$stmt->execute([$id]);
$evaluasi_list = $stmt->fetchAll();

// Group dokumentasi by jenis
$dokumentasi_grouped = [];
foreach ($dokumentasi_list as $doc) {
    $dokumentasi_grouped[$doc['jenis']][] = $doc;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Program Kerja</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
</head>
<body>
        <div class="dashboard-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <h3><?= $_SESSION['nama'] ?></h3>
                        <span class="role-badge">
                        <?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?></span>
                </div>
            
            <div class="sidebar-menu">
                <div class="menu-item">
                <a href="../../dashboard/<?= $_SESSION['role'] ?>.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
             <div class="menu-item">
            <?php if ($_SESSION['role'] == 'sekretaris'): ?>
                    <a href="../users/manage_users.php">
                    <i class="fas fa-users"></i>
                    Kelola Users
                    </a>
            <?php elseif ($_SESSION['role'] == 'ketua_umum'): ?>
                <a href="../users/view_users.php">
                    <i class="fas fa-chart-bar"></i>
                    kelola Users
                    </a>
                    <?php endif; ?>
                    </div>
            <div class="menu-item">
                <?php if ($_SESSION['role']): ?>
                <a href="manage_proker.php">
                    <i class="fas fa-tasks"></i>
                    Kelola Program Kerja
                </a>
                <?php endif; ?>
            </div>
                    <div class="menu-item">
                        <?php if ($_SESSION['role'] !== 'ketua_pelaksana'): ?>
                    <a href="../laporan/laporan_proker.php">
                        <i class="fas fa-chart-bar"></i>
                        Laporan
                    </a>
                    <?php endif; ?>
                </div>

            <div class="menu-item">
                  <?php if ($_SESSION['role']): ?>
                    <a href="../../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <div class="main-content" id="mainContent">
            <div class="container">
                <div class="header">
                    <h1>Detail Program Kerja</h1>                   
                </div>

  

        <div class="dashboard">
            <!-- Informasi Program Kerja -->
            <div class="card" style="margin-bottom: 20px;">
                <h3><?= sanitize($proker['nama_proker']) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div>
                        <strong>ID Program Kerja</strong><br>
                        <?= sanitize( $proker['id'] ?? '-') ?>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <?= getStatusBadge($proker['status']) ?>
                    </div>
                    <div>
                        <strong>Ketua Pelaksana:</strong><br>
                        <?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?>
                    </div>
                    <div>
                        <strong>Angkatan:</strong><br>
                        <?= sanitize($proker['angkatan'] ?? '-') ?>
                    </div>
                    <div>
                        <strong>Tanggal Mulai:</strong><br>
                        <?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : '-' ?>
                    </div>
                    <div>
                        <strong>Tanggal Selesai:</strong><br>
                        <?= $proker['tanggal_selesai'] ? formatTanggal($proker['tanggal_selesai']) : '-' ?>
                    </div>
                    <div>
                        <strong>Dibuat oleh:</strong><br>
                        <?= sanitize($proker['created_by_nama']) ?>
                    </div>
                    </div>
                <?php if ($proker['deskripsi']): ?>
                <div style="margin-top: 20px;">
                    <strong>Deskripsi:</strong><br>
                    <p style="margin-top: 10px; line-height: 1.6;">
                        <?= nl2br(sanitize($proker['deskripsi'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            


            <!-- Dokumentasi -->
            <div class="card-grid">
                    <a href="../dokumentasi/proposal.php?proker_id=<?= $id ?>" class="card-folder">
                        <i class="fas fa-file-alt"></i>
                        <h3>Proposal</h3>
                    </a>        
                    <a href="../dokumentasi/surat.php?proker_id=<?= $id ?>" class="card-folder">
                        <i class="fas fa-file-alt"></i>
                        <h3>Surat</h3>
                    </a>
               
                    <a href="../dokumentasi/foto.php?proker_id=<?= $id ?>" class="card-folder">               
                        <i class="fas fa-camera"></i>
                        <h3>Dokumentasi Kegiatan</h3>
                    </a>
                    <a href="../evaluasi/manage_evaluasi.php?proker_id=<?= $id ?>" class="card-folder">               
                            <i class="fas fa-check-circle"></i>
                        <h3>Evaluasi</h3>
                    </a>
                </div>
            </div>
        </div>
        <div style="
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color:rgb(75, 139, 195);
            color: white;
            border-radius: 50%;
            text-align: center;
            font-size: 20px;
            line-height: 50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transition: background-color 0.3s ease;
        ">
            <a href="manage_proker.php" title="Kembali" style="color: white; text-decoration: none; display: block;">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </body>
</html>