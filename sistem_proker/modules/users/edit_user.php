<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_umum']);

$id = $_GET['id'] ?? 0;
$error = '';

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage_users.php?error=not_found');
    exit();
}

if ($_POST) {
    $nama = sanitize($_POST['nama']);
    $nik = sanitize($_POST['nik']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validasi
    if (empty($nama) || empty($nik) || empty($username) || empty($role)) {
        $error = 'Nama, NIK, Username, dan Role harus diisi';
    } else {
        // Cek apakah username atau NIK sudah ada (kecuali untuk user ini)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR nik = ?) AND id != ?");
        $stmt->execute([$username, $nik, $id]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username atau NIK sudah digunakan';
        } else {
            try {
                // Update user
                if (!empty($password)) {
                    // Update dengan password baru
                    if (strlen($password) < 6) {
                        $error = 'Password minimal 6 karakter';
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET nama = ?, nik = ?, username = ?, password = ?, role = ?, status = ? WHERE id = ?");
                        $stmt->execute([$nama, $nik, $username, md5($password), $role, $status, $id]);

                    }
                } else {
                    // Update tanpa mengubah password
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, nik = ?, username = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nama, $nik, $username, $role, $status, $id]);
                }
                
                if (!$error) {
                    header('Location: manage_users.php?success=updated');
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'Gagal mengupdate user: ' . $e->getMessage();
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
    <title>Edit User</title>
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
                    <h1>Edit User</h1>
                </div>

        <div class="dashboard">
            <div style="max-width: 600px;">
                <h3>Form Edit User: <?= sanitize($user['nama']) ?></h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= sanitize($_POST['nama'] ?? $user['nama']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" 
                               value="<?= sanitize($_POST['nik'] ?? $user['nik']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= sanitize($_POST['username'] ?? $user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small style="color: #666;">Kosongkan jika tidak ingin mengubah password</small>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="ketua_pelaksana" <?= ($user['role'] == 'ketua_pelaksana') ? 'selected' : '' ?>>
                                Ketua Pelaksana
                            </option>
                            <option value="ketua_umum" <?= ($user['role'] == 'ketua_umum') ? 'selected' : '' ?>>
                                Ketua Umum
                            </option>
                            <option value="sekretaris" <?= ($user['role'] == 'sekretaris') ? 'selected' : '' ?>>
                                Sekretaris
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="aktif" <?= ($user['status'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($user['status'] == 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>


                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success">Update</button>
                        <a href="manage_users.php" class="btn btn-warning">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
     
</body>
</html>