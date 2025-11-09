<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();

$proker_id = $_GET['proker_id'] ?? 0;

// Validasi program kerja
$stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ?");
$stmt->execute([$proker_id]);
$proker = $stmt->fetch();

if (!$proker) {
    header('Location: ../proker/manage_proker.php');
    exit();
}

// Check permission
if ($_SESSION['role'] == 'ketua_pelaksana') {
    if ($proker['ketua_pelaksana_id'] != $_SESSION['user_id'] && $proker['ketua_pelaksana_id'] != null) {
        die('Anda tidak memiliki akses ke program kerja ini.');
    }
} elseif ($_SESSION['role'] == 'ketua_umum') {
    // Ketua umum hanya bisa lihat
} elseif ($_SESSION['role'] != 'sekretaris') {
    die('Akses ditolak.');
}

// Ambil evaluasi
$stmt = $pdo->prepare("
    SELECT e.*, u.nama as created_by_nama 
    FROM evaluasi e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.proker_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$proker_id]);
$evaluasi_list = $stmt->fetchAll();

$canEdit = ($_SESSION['role'] == 'ketua_pelaksana' && 
           ($proker['ketua_pelaksana_id'] == $_SESSION['user_id'] || !$proker['ketua_pelaksana_id'])) ||
           $_SESSION['role'] == 'sekretaris';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi - <?= sanitize($proker['nama_proker']) ?></title>
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
                <span class="role-badge">
                    <?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?>
                </span>
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

        <div class="main-content">
            <div class="container">
                <div class="header">
                    <h1>Evaluasi Program Kerja</h1>
                </div>

                <div class="card" style="margin-bottom: 20px;">
                    <h3><?= sanitize($proker['nama_proker']) ?></h3>
                    <p style="color: #666; margin: 10px 0;">
                        <?= getStatusBadge($proker['status']) ?>
                        <?php if ($proker['angkatan']): ?>
                            | Angkatan: <?= sanitize($proker['angkatan']) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="card" style="margin-bottom: 20px;">

                <?php if ($canEdit): ?>
                <div style="margin-bottom: 20px;">
                    <a href="add_evaluasi.php?proker_id=<?= $proker_id ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Evaluasi
                    </a>
                </div>
                <?php endif; ?>

                <?php if (empty($evaluasi_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Belum Ada Evaluasi</h3>
                    <p>Program kerja ini belum memiliki evaluasi.</p>
                    <?php if ($canEdit): ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php foreach ($evaluasi_list as $evaluasi): ?>
                <div class="evaluasi-card">
                    <div class="evaluasi-header">
                        <div>
                            <strong>Evaluasi #<?= $evaluasi['id'] ?></strong>
                        </div>
                        <div>
                        <?php if ($canEdit && $evaluasi['created_by'] == $_SESSION['user_id']): ?>
                            <a href="edit_evaluasi.php?id=<?= $evaluasi['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_evaluasi.php?id=<?= $evaluasi['id'] ?>" 
                            class="btn btn-danger btn-sm"
                            onclick="return confirmDelete('Apakah Anda yakin ingin menghapus evaluasi ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        <?php endif; ?>

                        </div>
                    </div>
                    
                    <div class="evaluasi-content">
                        <?= nl2br(sanitize($evaluasi['evaluasi'])) ?>
                    </div>
                    
                    <div class="evaluasi-meta">
                        <i class="fas fa-user"></i> <?= sanitize($evaluasi['created_by_nama']) ?>
                        <i class="fas fa-calendar" style="margin-left: 15px;"></i> 
                        <?= date('d/m/Y H:i', strtotime($evaluasi['created_at'])) ?>
                        <?php if ($evaluasi['updated_at'] != $evaluasi['created_at']): ?>
                        <span style="margin-left: 15px;">
                            <i class="fas fa-edit"></i> Diupdate: <?= date('d/m/Y H:i', strtotime($evaluasi['updated_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
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
    <a href="../proker/view_proker.php?id=<?= $proker_id ?>" title="Kembali" style="color: white; text-decoration: none; display: block;">
        <i class="fas fa-arrow-left"></i>
    </a>
</div>


    <script src="../../js/script.js"></script>
</body>
</html>