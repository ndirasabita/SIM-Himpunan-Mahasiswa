<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_pelaksana', 'ketua_umum']);

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$whereClause = '';
$params = [];

// Filter berdasarkan role
if ($_SESSION['role'] == 'ketua_pelaksana') {
    $whereClause = 'WHERE pk.ketua_pelaksana_id = ?';
    $params[] = $_SESSION['user_id'];
}

// Filter pencarian teks
if ($search) {
    $searchClause = '(pk.nama_proker LIKE ? OR pk.angkatan LIKE ?)';
    if ($whereClause) {
        $whereClause .= " AND $searchClause";
    } else {
        $whereClause = "WHERE $searchClause";
    }
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filter status
if ($status) {
    $statusClause = 'pk.status = ?';
    if ($whereClause) {
        $whereClause .= " AND $statusClause";
    } else {
        $whereClause = "WHERE $statusClause";
    }
    $params[] = $status;
}

// Query data
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
    <title>Kelola Program Kerja</title>
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
                <h1>Daftar Program Kerja</h1>
                <img class="logo" src="../../assets/img/logo.svg" alt="Logo">
            </div>

            <div class="dashboard">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; " >
                    <h3>Daftar Program Kerja</h3>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Cari program kerja..." 
                               value="<?= htmlspecialchars($search) ?>" class="form-control" style="width: 250px;">

                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="belum_selesai" <?= $status == 'belum_selesai' ? 'selected' : '' ?>>Belum Selesai</option>
                            <option value="sedang_berjalan" <?= $status == 'sedang_berjalan' ? 'selected' : '' ?>>Sedang Berjalan</option>
                            <option value="selesai" <?= $status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= $status == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>

                        <button type="submit" class="btn btn-info">Filter</button>
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

                                <?php if ($_SESSION['role'] == 'sekretaris' || $_SESSION['role'] == 'ketua_pelaksana'): ?>
                                    <a href="edit_proker.php?id=<?= $proker['id'] ?>" class="btn btn-warning">Edit</a>
                                <?php endif; ?>

                                <?php if ($_SESSION['role'] == 'sekretaris'): ?>
                                    <a href="delete_proker.php?id=<?= $proker['id'] ?>" class="btn btn-danger">Hapus</a>
                                <?php endif; ?>
                            </td>

                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($prokers)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">
                                    <?= $search || $status ? 'Tidak ada program kerja yang sesuai dengan filter.' : 'Belum ada program kerja.' ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($_SESSION['role'] != 'ketua_umum'): ?>
<div class="add-button-container" style="margin-top: 20px; display: flex; justify-content: flex-start;">
    <a href="add_proker.php" class="btn btn-success">Tambah Program Kerja</a>
</div>
<?php endif; ?>

            </div>
        </div>
    
</div>


</body>
</html>
