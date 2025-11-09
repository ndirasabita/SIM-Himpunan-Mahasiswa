<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris']); // Hanya sekretaris yang bisa menghapus user

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    header('Location: manage_users.php?error=not_found');
    exit();
}

// Cek apakah user yang akan dihapus ada dan bukan sekretaris yang sedang login
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND id != ?");
$stmt->execute([$user_id, $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage_users.php?error=not_found');
    exit();
}

// Jika form dikonfirmasi
if ($_POST['confirm'] ?? '' === 'yes') {
    try {
        $pdo->beginTransaction();
        
        // Hapus evaluasi yang dibuat oleh user ini
        $stmt = $pdo->prepare("DELETE FROM evaluasi WHERE created_by = ?");
        $stmt->execute([$user_id]);
        
        // Update program kerja yang dia buat (set created_by ke NULL)
        $stmt = $pdo->prepare("UPDATE program_kerja SET created_by = NULL WHERE created_by = ?");
        $stmt->execute([$user_id]);
        
        // Update program kerja yang dia pimpin (set ketua_pelaksana_id ke NULL)
        $stmt = $pdo->prepare("UPDATE program_kerja SET ketua_pelaksana_id = NULL WHERE ketua_pelaksana_id = ?");
        $stmt->execute([$user_id]);
        
        // Update dokumentasi yang dia upload (set uploaded_by ke NULL)
        $stmt = $pdo->prepare("UPDATE dokumentasi SET uploaded_by = NULL WHERE uploaded_by = ?");
        $stmt->execute([$user_id]);
        
        // Hapus user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        header('Location: manage_users.php?success=deleted');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: manage_users.php?error=delete_failed');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus User</title>
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
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content" id="mainContent">
            <div class="container">
                <div class="header">
                    <h1>Hapus User</h1>
                    <div class="logo-title">
                        <img class="logo" src="../../assets/img/logo.svg" alt="Logo">
                    </div>
                </div>

                <div class="dashboard">
                    <div class="nav-breadcrumb" style="margin-bottom: 20px;">
                        <a href="../../dashboard/sekretaris.php">Dashboard</a> > 
                        <a href="manage_users.php">Kelola User</a> > 
                        <span>Hapus User</span>
                    </div>

                    <div class="delete-confirmation">
                        <div class="alert alert-danger">
                            <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus User</h3>
                            <p>Anda akan menghapus user dengan data berikut:</p>
                        </div>

                        <div class="user-info-card">
                            <h4>Data User yang Akan Dihapus:</h4>
                            <table class="info-table">
                                <tr>
                                    <td><strong>Nama</strong></td>
                                    <td><?= sanitize($user['nama']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>NIK</strong></td>
                                    <td><?= sanitize($user['nik']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Username</strong></td>
                                    <td><?= sanitize($user['username']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Role</strong></td>
                                    <td>
                                        <?php
                                        $roles = [
                                            'sekretaris' => 'Sekretaris',
                                            'ketua_pelaksana' => 'Ketua Pelaksana',
                                            'ketua_umum' => 'Ketua Umum'
                                        ];
                                        echo $roles[$user['role']] ?? $user['role'];
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Dibuat</strong></td>
                                    <td><?= formatTanggal($user['created_at']) ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="warning-info">
                            <div class="alert alert-warning">
                                <h4><i class="fas fa-info-circle"></i> Perhatian!</h4>
                                <p>Menghapus user ini akan berdampak pada:</p>
                                <ul>
                                    <li>Program kerja yang dipimpin user ini akan kehilangan ketua pelaksana</li>
                                    <li>Evaluasi yang dibuat user ini akan ikut terhapus</li>
                                    <li>Dokumentasi yang diupload user ini akan kehilangan info uploader</li>
                                    <li><strong>Aksi ini tidak dapat dibatalkan!</strong></li>
                                </ul>
                            </div>
                        </div>

                        <form method="POST" class="confirmation-form">
                            <div class="form-actions">
                                <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Ya, Hapus User
                                </button>
                                <a href="manage_users.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .delete-confirmation {
        max-width: 800px;
        margin: 0 auto;
    }

    .user-info-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        margin: 20px 0;
    }

    .user-info-card h4 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }

    .info-table td:first-child {
        width: 30%;
        color: #666;
    }

    .info-table td:last-child {
        font-weight: 500;
        color: #333;
    }

    .warning-info {
        margin: 20px 0;
    }

    .warning-info ul {
        margin: 15px 0;
        padding-left: 20px;
    }

    .warning-info li {
        margin: 8px 0;
        color: #856404;
    }

    .confirmation-form {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        text-decoration: none;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        color: white;
    }

    .nav-breadcrumb {
        color: #666;
        font-size: 14px;
    }

    .nav-breadcrumb a {
        color: #667eea;
        text-decoration: none;
    }

    .nav-breadcrumb a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .info-table td:first-child {
            width: 40%;
        }
        
        .form-actions {
            flex-direction: column;
            align-items: center;
        }
        
        .form-actions .btn {
            width: 200px;
        }
    }
    </style>

    <script>
    // Konfirmasi tambahan saat submit
    document.querySelector('.confirmation-form').addEventListener('submit', function(e) {
        if (!confirm('Apakah Anda benar-benar yakin ingin menghapus user <?= sanitize($user['nama']) ?>? Aksi ini tidak dapat dibatalkan!')) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>