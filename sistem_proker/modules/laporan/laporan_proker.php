<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_umum']);

// Filter
$angkatan = $_GET['angkatan'] ?? '';
$status = $_GET['status'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');

// Query untuk mendapatkan daftar angkatan
$stmtAngkatan = $pdo->query("SELECT DISTINCT angkatan FROM program_kerja WHERE angkatan IS NOT NULL ORDER BY angkatan");
$angkatanList = $stmtAngkatan->fetchAll(PDO::FETCH_COLUMN);

// Query untuk statistik umum
$statsQuery = "
    SELECT 
        COUNT(*) as total_proker,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'sedang_berjalan' THEN 1 ELSE 0 END) as berjalan,
        SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum_mulai,
        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
    FROM program_kerja 
    WHERE YEAR(created_at) = ?
";
$params = [$tahun];

if ($angkatan) {
    $statsQuery .= " AND angkatan = ?";
    $params[] = $angkatan;
}

$stmt = $pdo->prepare($statsQuery);
$stmt->execute($params);
$stats = $stmt->fetch();

// Query untuk laporan detail
$detailQuery = "
    SELECT 
        pk.*,
        u.nama as ketua_nama,
        COUNT(DISTINCT d.id) as total_dokumentasi,
        e.evaluasi
    FROM program_kerja pk
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id
    LEFT JOIN dokumentasi d ON pk.id = d.proker_id
    LEFT JOIN evaluasi e ON pk.id = e.proker_id
    WHERE YEAR(pk.created_at) = ?
";
$detailParams = [$tahun];

if ($angkatan) {
    $detailQuery .= " AND pk.angkatan = ?";
    $detailParams[] = $angkatan;
}

if ($status) {
    $detailQuery .= " AND pk.status = ?";
    $detailParams[] = $status;
}

$detailQuery .= " GROUP BY pk.id ORDER BY pk.created_at DESC";

$stmt = $pdo->prepare($detailQuery);
$stmt->execute($detailParams);
$prokers = $stmt->fetchAll();

// Query untuk laporan per angkatan
$angkatanStatsQuery = "
   SELECT 
        angkatan,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'sedang_berjalan' THEN 1 ELSE 0 END) as berjalan,
        SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum_mulai,
        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
    FROM program_kerja 
    WHERE YEAR(created_at) = ? AND angkatan IS NOT NULL
    GROUP BY angkatan 
    ORDER BY angkatan
";

$stmt = $pdo->prepare($angkatanStatsQuery);
$stmt->execute([$tahun]);
$angkatanStats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Program Kerja</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
    <style>
        body::-webkit-scrollbar {
    display: none;
}
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-number.total { color: #007bff; }
        .stat-number.selesai { color: #28a745; }
        .stat-number.berjalan { color: #17a2b8; }
        .stat-number.belum { color: #ffc107; }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            background-color: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-selesai { background-color: #28a745; }
        .progress-berjalan { background-color: #17a2b8; }
        .progress-belum { background-color: #ffc107; }
        
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .container { max-width: none; padding: 0; }
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
            <span class="role-badge"><?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?></span>
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
                    <i class="fas fa-users"></i>
                    kelola Users
                    </a>
                    <?php endif; ?>
                    </div>
            <div class="menu-item">
                <?php if ($_SESSION['role']): ?>
                <a href="../proker/manage_proker.php">
                    <i class="fas fa-tasks"></i>
                    Kelola Program Kerja
                </a>
                <?php endif; ?>
            </div>
                  <?php if ($_SESSION['role']): ?>
                    <div class="menu-item">
                    <a href="laporan_proker.php">
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
                    <h1>Laporan Program Kerja</h1>
                    <div class="logo-title">
                        <img class="logo" src="../../assets/img/logo.svg" alt="Logo">
                    </div>
                </div>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <h3>Filter Laporan</h3>
            <form method="GET" class="filter-row">
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Angkatan</label>
                    <select name="angkatan" class="form-control">
                        <option value="">Semua Angkatan</option>
                        <?php foreach ($angkatanList as $ang): ?>
                        <option value="<?= $ang ?>" <?= $angkatan == $ang ? 'selected' : '' ?>><?= $ang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="belum_mulai" <?= $status == 'belum_mulai' ? 'selected' : '' ?>>Belum Mulai</option>
                        <option value="sedang_berjalan" <?= $status == 'sedang_berjalan' ? 'selected' : '' ?>>Sedang Berjalan</option>
                        <option value="selesai" <?= $status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="dibatalkan" <?= $status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>

                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="laporan_proker.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

     
        <!-- Statistik Umum -->
        <div class="dashboard">
            <h3>Statistik Program Kerja Tahun <?= $tahun ?></h3>
            <?php if ($angkatan): ?>
                <p><strong>Filter Angkatan:</strong> <?= $angkatan ?></p>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number total"><?= $stats['total_proker'] ?></div>
                    <div>Total Program</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number selesai"><?= $stats['selesai'] ?></div>
                    <div>Selesai</div>
                    <?php if ($stats['total_proker'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill progress-selesai" 
                             style="width: <?= ($stats['selesai'] / $stats['total_proker']) * 100 ?>%"></div>
                    </div>
                    <small><?= round(($stats['selesai'] / $stats['total_proker']) * 100, 1) ?>%</small>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-number berjalan"><?= $stats['berjalan'] ?></div>
                    <div>Sedang Berjalan</div>
                    <?php if ($stats['total_proker'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill progress-berjalan" 
                             style="width: <?= ($stats['berjalan'] / $stats['total_proker']) * 100 ?>%"></div>
                    </div>
                    <small><?= round(($stats['berjalan'] / $stats['total_proker']) * 100, 1) ?>%</small>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-number belum"><?= $stats['belum_mulai'] ?></div>
                    <div>Belum Mulai</div>
                    <?php if ($stats['total_proker'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill progress-belum" 
                             style="width: <?= ($stats['belum_mulai'] / $stats['total_proker']) * 100 ?>%"></div>
                    </div>
                    <small><?= round(($stats['belum_mulai'] / $stats['total_proker']) * 100, 1) ?>%</small>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #dc3545;"><?= $stats['dibatalkan'] ?></div>
                    <div>Dibatalkan</div>
                    <?php if ($stats['total_proker'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= ($stats['dibatalkan'] / $stats['total_proker']) * 100 ?>%; background-color: #dc3545;"></div>
                    </div>
                    <small><?= round(($stats['dibatalkan'] / $stats['total_proker']) * 100, 1) ?>%</small>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Laporan Per Angkatan -->
        <?php if (!$angkatan && count($angkatanStats) > 0): ?>
        <div class="dashboard">
            <h3>Laporan Per Angkatan</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Angkatan</th>
                            <th>Total Program</th>
                            <th>Selesai</th>
                            <th>Sedang Berjalan</th>
                            <th>Belum Mulai</th>
                            <th>Dibatalkan</th>
                            <th>Persentase Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($angkatanStats as $angkStat): ?>
                        <tr>
                            <td><strong><?= sanitize($angkStat['angkatan']) ?></strong></td>
                            <td><?= $angkStat['total'] ?></td>
                            <td><span class="badge badge-success"><?= $angkStat['selesai'] ?></span></td>
                            <td><span class="badge badge-info"><?= $angkStat['berjalan'] ?></span></td>
                            <td><span class="badge badge-warning"><?= $angkStat['belum_mulai'] ?></span></td>
                            <td><span class="badge badge-danger"><?= $angkStat['dibatalkan'] ?></span></td>
                            <td>
                                <?php 
                                $percentage = $angkStat['total'] > 0 ? round(($angkStat['selesai'] / $angkStat['total']) * 100, 1) : 0;
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill progress-selesai" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <small><?= $percentage ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detail Program Kerja -->
        <div class="dashboard">
               <!-- Export Buttons -->
        <div class="export-buttons no-print">
            <button onclick="window.print()" class="btn btn-danger">Print Laporan</button>
            <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success">Export Excel</a>
        </div>

            <h3>Detail Program Kerja</h3>
            <?php if (count($prokers) == 0): ?>
                <div class="alert alert-info">
                    Tidak ada program kerja yang ditemukan dengan filter yang dipilih.
                </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Program</th>
                            <th>Ketua Pelaksana</th>
                            <th>Angkatan</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Dokumentasi</th>
                            <th>Evaluasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prokers as $proker): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($proker['nama_proker']) ?></strong>
                                <?php if ($proker['deskripsi']): ?>
                                <br><small class="text-muted"><?= sanitize(substr($proker['deskripsi'], 0, 100)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?></td>
                            <td><?= sanitize($proker['angkatan'] ?? '-') ?></td>
                            <td>
                                <?php if ($proker['tanggal_mulai']): ?>
                                    <?= formatTanggal($proker['tanggal_mulai']) ?>
                                    <?php if ($proker['tanggal_selesai']): ?>
                                        <br>s/d <?= formatTanggal($proker['tanggal_selesai']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum ditentukan</span>
                                <?php endif; ?>
                            </td>
                            <td><?= getStatusBadge($proker['status']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= $proker['total_dokumentasi'] ?> file</span>
                            </td>
                            <td>
                                <?php if ($proker['evaluasi']): ?>
                                    <span class="badge badge-success">Ada</span>
                                    <br><small><?= sanitize(substr($proker['evaluasi'], 0, 50)) ?>...</small>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>


    <script>
        // Auto refresh setiap 5 menit jika tidak dalam mode print
        if (!window.matchMedia('print').matches) {
            setTimeout(() => {
                location.reload();
            }, 300000); // 5 menit
        }

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


    </script>
</body>
</html>