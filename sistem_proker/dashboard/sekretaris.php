<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
checkRole(['sekretaris']);

// Ambil statistik
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'ketua_pelaksana'");
$totalKetua = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja");
$totalProker = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja WHERE status = 'selesai'");
$prokerSelesai = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja WHERE status = 'sedang_berjalan'");
$prokerBerjalan = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja WHERE status = 'dibatalkan'");
$prokerDibatalkan = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja WHERE status = 'belum_mulai'");
$prokerBelum_mulai = $stmt->fetchColumn();

// Ambil data program kerja per angkatan
$stmt = $pdo->query("
    SELECT 
        angkatan,
        COUNT(*) as total_proker,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'sedang_berjalan' THEN 1 ELSE 0 END) as berjalan,
        SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum_mulai
    FROM program_kerja 
    WHERE angkatan IS NOT NULL AND angkatan != ''
    GROUP BY angkatan 
    ORDER BY angkatan DESC
");
$angkatanData = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sekretaris</title>
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
                <span class="role-badge">Sekretaris</span>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item">
                    <a href="sekretaris.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="menu-item">
                    <a href="../modules/users/manage_users.php">
                        <i class="fas fa-users"></i>
                        Kelola Users
                    </a>
                </div>
                <div class="menu-item">
                    <a href="../modules/proker/manage_proker.php">
                        <i class="fas fa-tasks"></i>
                        Kelola Program Kerja
                    </a>
                </div>
                <div class="menu-item">
                    <a href="../modules/laporan/laporan_proker.php">
                        <i class="fas fa-chart-bar"></i>
                        Laporan
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

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="container">
                <div class="header">
                    <h1>Dashboard Sekretaris</h1>
                    <div class="logo-title">
                        <img class="logo" src="../assets/img/logo.svg" alt="Logo">
                    </div>
                </div>

                <!-- Statistik Cards -->
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-number"><?= $totalProker ?></div>
                        <h3>Total Program Kerja</h3>
                        <p>Semua program kerja yang ada</p>
                        <a href="../modules/proker/manage_proker.php" class="btn btn-info">Kelola Proker</a>
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
                        <div class="card-number"><?= $prokerBelum_mulai ?></div>
                        <h3>Program Belum Dimulai</h3>
                        <p>Program kerja yang Dibatalkan</p>
                        <a href="../modules/proker/proker_belum_mulai.php" class="btn btn-warning">Lihat</a>
                    </div>

                    <div class="card">
                        <div class="card-number"><?= $prokerDibatalkan ?></div>
                        <h3>Program Dibatalkan</h3>
                        <p>Program kerja yang Dibatalkan</p>
                        <a href="../modules/proker/proker_dibatalkan.php" class="btn btn-danger">Lihat</a>
                    </div>
                </div>

                <!-- Program Kerja per Angkatan -->
                <div class="dashboard">
                    <h3>Program Kerja per Angkatan</h3>
                    <p style="color: #666; margin-bottom: 20px;">Klik pada card angkatan untuk melihat detail program kerja</p>
                    
                    <div class="angkatan-grid">
                    <?php foreach ($angkatanData as $data): ?>
                    <a class="angkatan-card" href="../modules/proker/proker_per_angkatan.php?angkatan=<?= $data['angkatan'] ?>">
                    <h3>
                        Angkatan <?= $data['angkatan'] ?>
                    </h3>
                </a>

                    <?php endforeach; ?>
                </div>


                    
                </div>
            </div>
        </div>
    </div>
</body>
</html>