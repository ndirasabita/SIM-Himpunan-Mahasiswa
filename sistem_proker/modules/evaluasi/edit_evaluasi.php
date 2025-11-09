<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['ketua_pelaksana', 'sekretaris']);

$evaluasi_id = $_GET['id'] ?? 0;

// Cek apakah evaluasi milik user yang login
$stmt = $pdo->prepare("
    SELECT e.*, pk.nama_proker, pk.angkatan, pk.status, pk.tanggal_mulai, pk.tanggal_selesai
    FROM evaluasi e 
    JOIN program_kerja pk ON e.proker_id = pk.id 
    WHERE e.id = ? AND e.created_by = ?
");
$stmt->execute([$evaluasi_id, $_SESSION['user_id']]);
$evaluasi = $stmt->fetch();

if (!$evaluasi) {
    die('Evaluasi tidak ditemukan atau bukan milik Anda.');
}

$proker_id = $evaluasi['proker_id'];


$success = '';
$error = '';

if ($_POST) {
    $evaluasi_text = trim($_POST['evaluasi'] ?? '');
    
    if (empty($evaluasi_text)) {
        $error = 'Evaluasi harus diisi';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE evaluasi SET evaluasi = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$evaluasi_text, $evaluasi_id]);
            
            $success = 'Evaluasi berhasil diupdate';
            
            // Update data evaluasi untuk ditampilkan
            $evaluasi['evaluasi'] = $evaluasi_text;
            
            // Redirect after 2 seconds
            header("refresh:2;url=manage_evaluasi.php");
        } catch (Exception $e) {
            $error = 'Gagal mengupdate evaluasi: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Evaluasi</title>
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
                <a href="../users/view_users.php">
                    <i class="fas fa-chart-bar"></i>
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
                    <h1>Edit Evaluasi</h1>                   
                </div>


        <div class="dashboard">
            <div class="card">
                <h3>Program Kerja: <?= sanitize($evaluasi['nama_proker']) ?></h3>
                <p style="color: #666;">
                    Status: <?= getStatusBadge($evaluasi['status']) ?> | 
                    Angkatan: <?= sanitize($evaluasi['angkatan'] ?? '-') ?>
                </p>
                <p style="color: #666; font-size: 14px;">
                    Evaluasi dibuat: <?= date('d/m/Y H:i', strtotime($evaluasi['created_at'])) ?>
                    <?php if ($evaluasi['updated_at'] != $evaluasi['created_at']): ?>
                        | Terakhir diupdate: <?= date('d/m/Y H:i', strtotime($evaluasi['updated_at'])) ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="card">
                <h3>Edit Evaluasi</h3>
                
                <div class="form-group">
                    <label for="evaluasi">Isi Evaluasi <span style="color: red;">*</span></label>
                    <textarea class="form-control" id="evaluasi" name="evaluasi" rows="10" 
                              placeholder="Tuliskan evaluasi program kerja Anda di sini..."><?= sanitize($evaluasi['evaluasi']) ?></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Update Evaluasi</button>
                   <a href="manage_evaluasi.php?proker_id=<?= $proker_id ?>" class="btn btn-danger">
                                <i class="fas fa-times"></i> Batal
                            </a> 
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-resize textarea
        const textarea = document.getElementById('evaluasi');
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>