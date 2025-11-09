<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris']);

$error = '';
$success = '';

if ($_POST) {
    $nama = sanitize($_POST['nama']);
    $nik = sanitize($_POST['nik']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    
    // Validasi
   if (empty($nama) || empty($nik) || empty($username) || empty($password) || empty($role) || empty($status)) {
    $error = 'Semua field harus diisi';
    }elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Cek apakah username atau NIK sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR nik = ?");
        $stmt->execute([$username, $nik]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username atau NIK sudah digunakan';
        } else {
            // Insert user baru
            try {
               $stmt = $pdo->prepare("INSERT INTO users (nama, nik, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $nik, $username, md5($password), $role, $status]);

                
                header('Location: manage_users.php?success=added');
                exit();
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan user: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
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
                    <a href="../../dashboard/sekretaris.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="menu-item">
                 <?php if ($_SESSION['role']): ?>
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        Kelola User
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
                    <h1>Tambah User</h1>
                </div>

        <div class="dashboard">
            <div style="max-width: 600px;">
                <h3>Form Tambah User</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= $_POST['nama'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" 
                               value="<?= $_POST['nik'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= $_POST['username'] ?? '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small style="color: #666;">Minimal 6 karakter</small>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="ketua_pelaksana" <?= ($_POST['role'] ?? '') == 'ketua_pelaksana' ? 'selected' : '' ?>>
                                Ketua Pelaksana
                            </option>
                            <option value="ketua_umum" <?= ($_POST['role'] ?? '') == 'ketua_umum' ? 'selected' : '' ?>>
                                Ketua Umum
                            </option>
                            <option value="sekretaris" <?= ($_POST['role'] ?? '') == 'sekretaris' ? 'selected' : '' ?>>
                                Sekretaris
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="">Pilih Status</option>
                            <option value="aktif" <?= ($_POST['status'] ?? '') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($_POST['status'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="manage_users.php" class="btn btn-warning">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>