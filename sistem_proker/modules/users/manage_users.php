<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris']); // Hanya sekretaris yang bisa kelola user

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Hitung jumlah user berdasarkan role
$stmtSekretaris = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'sekretaris'");
$totalSekretaris = $stmtSekretaris->fetchColumn();

$stmtKetuaUmum = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ketua_umum'");
$totalKetuaUmum = $stmtKetuaUmum->fetchColumn();

$stmtKetuaPelaksana = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ketua_pelaksana'");
$totalKetuaPelaksana = $stmtKetuaPelaksana->fetchColumn();


// Ambil semua user kecuali sekretaris yang sedang login
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<h>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
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
                <span class="role-badge">Sekretaris</span>
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
                    <a href="manage_users.php">
                    <i class="fas fa-users"></i>
                    Kelola Users
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
                    <h1>Kelola User</h1>
                    <div class="logo-title">
                        <img class="logo" src="../../assets/img/logo.svg" alt="Logo">
                    </div>
                </div>
    <!--             <div class="cards-grid">
                    <div class="card">
                        <h3>Sekretaris</h3>
                        <div class="card-number"><?= $totalSekretaris ?></div>
                    </div>
                    
                    <div class="card">
                        <h3>Ketua Umum</h3>
                        <div class="card-number"><?= $totalKetuaUmum ?></div>
                        </div>
                    <div class="card">
                        <h3>Ketua Pelaksana</h3>
                        <div class="card-number"><?= $totalKetuaPelaksana ?></div>
                    </div>
                </div> -->


        <div class="dashboard">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php
                    switch ($success) {
                        case 'added':
                            echo 'User berhasil ditambahkan!';
                            break;
                        case 'updated':
                            echo 'User berhasil diupdate!';
                            break;
                        case 'deleted':
                            echo 'User berhasil dihapus!';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php
                    switch ($error) {
                        case 'not_found':
                            echo 'User tidak ditemukan!';
                            break;
                        case 'delete_failed':
                            echo 'Gagal menghapus user!';
                            break;
                        default:
                            echo htmlspecialchars($error);
                    }
                    ?>
                </div>
            <?php endif; ?>

            <h3>Daftar User</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= sanitize($user['nama']) ?></td>
                            <td><?= sanitize($user['nik']) ?></td>
                            <td><?= sanitize($user['username']) ?></td>
                            <td>
                                <?php
                                $roles = [
                                    'sekretaris' => '<span class="badge badge-info">Sekretaris</span>',
                                    'ketua_pelaksana' => '<span class="badge badge-warning">Ketua Pelaksana</span>',
                                    'ketua_umum' => '<span class="badge badge-success">Ketua Umum</span>'
                                ];
                                echo $roles[$user['role']] ?? $user['role'];
                                ?>
                            </td>
                            <td>
                            <?php if ($user['status'] == 'aktif'): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>


                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-warning">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" 
                                   class="btn btn-danger">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                Belum ada user yang terdaftar
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

                <div class="add-button-container" style="margin-top: 20px; display: flex; justify-content: flex-start;">
                    <a href="add_user.php" class="btn btn-success">Tambah User</a>
                </div>
            
            </div>
            </div>
        </div>
    </div>
</body>
</html>