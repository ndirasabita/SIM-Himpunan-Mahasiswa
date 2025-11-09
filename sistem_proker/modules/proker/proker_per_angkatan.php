<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();

$selectedAngkatan = $_GET['angkatan'] ?? '';

// Ambil daftar angkatan yang tersedia
$stmt = $pdo->query("SELECT DISTINCT angkatan FROM program_kerja WHERE angkatan IS NOT NULL AND angkatan != '' ORDER BY angkatan DESC");
$angkatanList = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Query untuk mengambil program kerja berdasarkan angkatan
$whereClause = '';
$params = [];

if ($selectedAngkatan) {
    $whereClause = 'WHERE pk.angkatan = ?';
    $params = [$selectedAngkatan];
}

$stmt = $pdo->prepare("
    SELECT pk.*, u.nama as ketua_nama,
           COUNT(d.id) as total_dokumentasi,
           COUNT(e.id) as total_evaluasi
    FROM program_kerja pk 
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id 
    LEFT JOIN dokumentasi d ON pk.id = d.proker_id
    LEFT JOIN evaluasi e ON pk.id = e.proker_id
    $whereClause
    GROUP BY pk.id
    ORDER BY pk.angkatan DESC, pk.created_at DESC
");
$stmt->execute($params);
$prokers = $stmt->fetchAll();

// Hitung statistik per angkatan
$statsPerAngkatan = [];
foreach ($angkatanList as $angkatan) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'sedang_berjalan' THEN 1 ELSE 0 END) as berjalan,
            SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum_mulai
        FROM program_kerja 
        WHERE angkatan = ?
    ");
    $stmt->execute([$angkatan]);
    $statsPerAngkatan[$angkatan] = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Kerja Per Angkatan</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
    <style>
        .angkatan-filter {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .angkatan-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .angkatan-tab {
            padding: 10px 20px;
            border: 2px solid #e1e1e1;
            border-radius: 25px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }
        
        .angkatan-tab:hover,
        .angkatan-tab.active {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
        }

        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            color: black;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: rgba(21, 81, 133, 0.3);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: rgb(26, 138, 11);
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        .proker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .proker-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .proker-card:hover {
            transform: translateY(-3px);
        }
        
        .proker-card h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        
        .proker-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .proker-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .proker-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .dokumentasi-info {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state img {
            width: 120px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
    </style>
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
                <a href=".../users/view_users.php">
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
                    <h1><?php if ($selectedAngkatan): ?>
                    Program Kerja Angkatan <?= $selectedAngkatan ?>
                <?php else: ?>
                    Semua Program Kerja
                <?php endif; ?>
                    </h1>
                    
                </div>



        <?php if ($selectedAngkatan && isset($statsPerAngkatan[$selectedAngkatan])): ?>
        <div class="stats-overview">
            <?php 
            $stats = $statsPerAngkatan[$selectedAngkatan];
            $total = $stats['total'];
            ?>
            <div class="stat-card">
                <h4>Total Program</h4>
                <div class="number"><?= $total ?></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <h4>Program Selesai</h4>
                <div class="number"><?= $stats['selesai'] ?></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $total > 0 ? ($stats['selesai'] / $total * 100) : 0 ?>%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <h4>Sedang Berjalan</h4>
                <div class="number"><?= $stats['berjalan'] ?></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $total > 0 ? ($stats['berjalan'] / $total * 100) : 0 ?>%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <h4>Belum Mulai</h4>
                <div class="number"><?= $stats['belum_mulai'] ?></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $total > 0 ? ($stats['belum_mulai'] / $total * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard">
            <h3>
                <?php if ($selectedAngkatan): ?>
                    Program Kerja Angkatan <?= $selectedAngkatan ?>
                <?php else: ?>
                    Semua Program Kerja
                <?php endif; ?>
            </h3>

            <?php if (empty($prokers)): ?>
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 20px;">📋</div>
                <h3>Belum Ada Program Kerja</h3>
                <p>
                    <?php if ($selectedAngkatan): ?>
                        Belum ada program kerja untuk angkatan <?= $selectedAngkatan ?>
                    <?php else: ?>
                        Belum ada program kerja yang terdaftar
                    <?php endif; ?>
                </p>
                <?php if ($_SESSION['role'] != 'ketua_umum'): ?>
                <a href="add_proker.php" class="btn btn-primary" style="margin-top: 20px;">
                    Tambah Program Kerja
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="proker-grid">
                <?php foreach ($prokers as $proker): ?>
                <div class="proker-card">
                    <h4><?= sanitize($proker['nama_proker']) ?></h4>
                    
                    <div class="proker-meta">
                        <span><strong>Angkatan:</strong> <?= sanitize($proker['angkatan'] ?? 'Tidak ditentukan') ?></span>
                        <?= getStatusBadge($proker['status']) ?>
                    </div>
                    
                    <?php if ($proker['deskripsi']): ?>
                    <div class="proker-description">
                        <?= nl2br(sanitize(substr($proker['deskripsi'], 0, 150))) ?>
                        <?= strlen($proker['deskripsi']) > 150 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Ketua Pelaksana:</strong> 
                        <?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?>
                    </div>
                    
                    <?php if ($proker['tanggal_mulai']): ?>
                    <div style="margin-bottom: 15px; font-size: 14px; color: #666;">
                        <strong>Jadwal:</strong> 
                        <?= formatTanggal($proker['tanggal_mulai']) ?>
                        <?php if ($proker['tanggal_selesai']): ?>
                            - <?= formatTanggal($proker['tanggal_selesai']) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="proker-footer">
                        <div class="dokumentasi-info">
                            <span>📄 <?= $proker['total_dokumentasi'] ?> Dokumen</span>
                            <span>📝 <?= $proker['total_evaluasi'] ?> Evaluasi</span>
                        </div>
                        
                        <div>
                            <a href="view_proker.php?id=<?= $proker['id'] ?>" class="btn btn-info">Detail</a>
                            <?php if ($_SESSION['role'] != 'ketua_umum'): ?>
                            <a href="edit_proker.php?id=<?= $proker['id'] ?>" class="btn btn-warning">Edit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($angkatanList) && empty($selectedAngkatan)): ?>
        <div class="dashboard" style="margin-top: 30px;">
            <h3>Ringkasan Per Angkatan</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Angkatan</th>
                            <th>Total Program</th>
                            <th>Selesai</th>
                            <th>Sedang Berjalan</th>
                            <th>Belum Mulai</th>
                            <th>Persentase Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($angkatanList as $angkatan): ?>
                        <?php 
                        $stats = $statsPerAngkatan[$angkatan];
                        $persentase = $stats['total'] > 0 ? round(($stats['selesai'] / $stats['total']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong>Angkatan <?= $angkatan ?></strong></td>
                            <td><?= $stats['total'] ?></td>
                            <td><span class="badge badge-success"><?= $stats['selesai'] ?></span></td>
                            <td><span class="badge badge-info"><?= $stats['berjalan'] ?></span></td>
                            <td><span class="badge badge-warning"><?= $stats['belum_mulai'] ?></span></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="width: 80px; height: 6px; background-color: #eee;">
                                        <div class="progress-fill" 
                                             style="width: <?= $persentase ?>%; background-color: #28a745;"></div>
                                    </div>
                                    <span><?= $persentase ?>%</span>
                                </div>
                            </td>
                            <td>
                                <a href="proker_per_angkatan.php?angkatan=<?= $angkatan ?>" 
                                   class="btn btn-info">Lihat Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
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
    <a href="javascript:history.back()" title="Kembali" style="color: white; text-decoration: none; display: block;">
        <i class="fas fa-arrow-left"></i>
    </a>
</div>

    <script>
        // Auto refresh setiap 5 menit untuk update real-time
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 menit

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !mobileToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Smooth scroll untuk navigasi tab
        document.querySelectorAll('.angkatan-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                // Tambahkan loading state
                this.style.opacity = '0.7';
                this.innerHTML += ' <span style="font-size: 12px;">Loading...</span>';
            });
        });
    </script>
</body>
</html>