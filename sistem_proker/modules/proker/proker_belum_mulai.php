<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_pelaksana', 'ketua_umum']);

$search = $_GET['search'] ?? '';
$whereClause = 'WHERE pk.status = ?';
$params = ['belum_mulai']; // Sesuaikan dengan nilai status di database jika berbeda (misal: 'cancelled')

if ($_SESSION['role'] == 'ketua_pelaksana') {
    $whereClause .= ' AND pk.ketua_pelaksana_id = ?';
    $params[] = $_SESSION['user_id'];
}

if ($search) {
    $searchClause = '(pk.nama_proker LIKE ? OR pk.angkatan LIKE ?)';
    $whereClause .= " AND $searchClause";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT pk.*, u.nama as ketua_nama 
    FROM program_kerja pk 
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id 
    $whereClause
    ORDER BY pk.created_at DESC
");
$stmt->execute($params);
$prokers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Kerja Dibatalkan</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
</head>
<body>
    
<div class="dashboard-container">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

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
                <h1>Program Kerja Dibatalkan</h1>
            </div>

            <div class="dashboard">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Daftar Program Kerja</h3>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Cari program kerja..." 
                               value="<?= htmlspecialchars($search) ?>" class="form-control" style="width: 200px;">
                        <button type="submit" class="btn btn-info">Cari</button>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Program</th>
                                <th>Ketua Pelaksana</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Angkatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prokers as $proker): ?>
                            <tr>
                                <td><?= sanitize($proker['nama_proker']) ?></td>
                                <td><?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?></td>
                                <td>
                                    <?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : '-' ?>
                                    <?php if ($proker['tanggal_selesai']): ?>
                                        <br><small>s/d <?= formatTanggal($proker['tanggal_selesai']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($proker['status']) ?></td>
                                <td><?= sanitize($proker['angkatan'] ?? '-') ?></td>
                                <td>
                                    <a href="view_proker.php?id=<?= $proker['id'] ?>" class="btn btn-info">Detail</a>
                                    <a href="edit_proker.php?id=<?= $proker['id'] ?>" class="btn btn-warning">Edit</a>
                                    <?php if ($_SESSION['role'] == 'sekretaris'): ?>
                                    <a href="delete_proker.php?id=<?= $proker['id'] ?>" class="btn btn-danger"
                                       onclick="return confirm('Yakin ingin menghapus program kerja ini?')">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($prokers)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">
                                    <?= $search ? 'Tidak ada program kerja dibatalkan yang sesuai pencarian.' : 'Belum ada program kerja yang dibatalkan.' ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
    <a href="javascript:history.back()" title="Kembali" style="color: white; text-decoration: none; display: block;">
        <i class="fas fa-arrow-left"></i>
    </a>
</div>
</body>
</html>
