<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
checkRole(['ketua_pelaksana']);

// Ambil program kerja yang dikelola ketua pelaksana ini
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM program_kerja 
    WHERE ketua_pelaksana_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalProker = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM program_kerja 
    WHERE ketua_pelaksana_id = ? AND status = 'selesai'
");
$stmt->execute([$_SESSION['user_id']]);
$prokerSelesai = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM program_kerja 
    WHERE ketua_pelaksana_id = ? AND status = 'belum_mulai'
");
$stmt->execute([$_SESSION['user_id']]);
$prokerBelum_mulai = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM program_kerja 
    WHERE ketua_pelaksana_id = ? AND status = 'sedang_berjalan'
");
$stmt->execute([$_SESSION['user_id']]);
$prokerBerjalan = $stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM program_kerja 
    WHERE ketua_pelaksana_id = ? AND status = 'dibatalkan'
");
$stmt->execute([$_SESSION['user_id']]);
$prokerDibatalkan = $stmt->fetchColumn();

// Ambil dokumentasi yang diupload
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM dokumentasi d
    JOIN program_kerja pk ON d.proker_id = pk.id
    WHERE pk.ketua_pelaksana_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalDokumentasi = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ketua Pelaksana</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../js/script.js"></script>
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
                <span class="role-badge">Ketua Pelaksana</span>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item">
                    <a href="ketua_pelaksana.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="menu-item">
                    <a href="../modules/proker/manage_proker.php">
                        <i class="fas fa-tasks"></i>
                        Kelola Program Kerja
                    </a>
                </div>
        
                <div class="menu-item">
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content" id="mainContent">
            <div class="container">
                <div class="header">
                    <h1>Dashboard Ketua Pelaksana</h1>
                    <div class="logo-title">
                        <img class="logo" src="../assets/img/logo.svg" alt="Logo">
                    </div>
                </div>

        
        <div class="cards-grid">
            <div class="card">
                <div class="card-number"><?= $totalProker ?></div>
                <h3>Program Kerja Saya</h3>
                <p>Program kerja yang Anda kelola</p>
                <a href="../modules/proker/manage_proker.php" class="btn btn-info">Kelola</a>
            </div>
            
            <div class="card">
                <div class="card-number"><?= $prokerSelesai ?></div>
                <h3>Program Selesai</h3>
                <p>Program kerja yang sudah selesai</p>
                <a href="../modules/proker/proker_selesai.php" class="btn btn-success">Lihat</a>
            </div>
            
            <div class="card">
                <div class="card-number"><?= $prokerBerjalan ?></div>
                <h3>Program Berjalan</h3>
                <p>Program kerja yang sedang berjalan</p>
                <a href="../modules/proker/proker_berjalan.php" class="btn btn-warning">Lihat</a>
            </div>

            <div class="card">
                <div class="card-number"><?= $prokerDibatalkan ?></div>
                <h3>Program Dibatalkan</h3>
                <p>Program kerja yang Dibatalkan</p>
                <a href="../modules/proker/proker_dibatalkan.php" class="btn btn-warning">Lihat</a>
            </div>

            <div class="card">
                <div class="card-number"><?= $prokerBelum_mulai ?></div>
                <h3>Program Belum Mulai</h3>
                <p>Program kerja yang Belum dimulai</p>
                <a href="../modules/proker/proker_belum_mulai.php" class="btn btn-danger">Lihat</a>
            </div>

        </div>
        

        <div class="dashboard">
            <h3>Program Kerja Terbaru</h3>
            <?php
            $stmt = $pdo->prepare("
                SELECT pk.* 
                FROM program_kerja pk 
                WHERE pk.ketua_pelaksana_id = ?
                ORDER BY pk.created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $prokers = $stmt->fetchAll();
            ?>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Program</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prokers as $proker): ?>
                        <tr>
                            <td><?= sanitize($proker['nama_proker']) ?></td>
                            <td><?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : '-' ?></td>
                            <td><?= $proker['tanggal_selesai'] ? formatTanggal($proker['tanggal_selesai']) : '-' ?></td>
                            <td><?= getStatusBadge($proker['status']) ?></td>
                            <td>
                                <a href="../modules/proker/view_proker.php?id=<?= $proker['id'] ?>" class="btn btn-info">Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($prokers)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                Anda belum memiliki program kerja
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>