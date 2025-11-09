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
} elseif ($_SESSION['role'] != 'sekretaris') {
    die('Akses ditolak.');
}

$message = '';

if ($_POST) {
    $evaluasi = $_POST['evaluasi'] ?? '';
    
    if (empty($evaluasi)) {
        $message = '<div class="alert alert-danger">Evaluasi harus diisi!</div>';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO evaluasi (proker_id, evaluasi, created_by) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$proker_id, $evaluasi, $_SESSION['user_id']]);
            
            header('Location: manage_evaluasi.php?proker_id=' . $proker_id);
            exit();
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Gagal menambah evaluasi: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Evaluasi</title>
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
                    <h1>Tambah Evaluasi</h1>
                </div>

                <div class="card" style="margin-bottom: 20px;">
                    <h3><?= sanitize($proker['nama_proker']) ?></h3>
                    <p style="color: #666;">
                        <?= getStatusBadge($proker['status']) ?>
                        <?php if ($proker['angkatan']): ?>
                            | Angkatan: <?= sanitize($proker['angkatan']) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="dashboard">
                    <?= $message ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="evaluasi">Evaluasi Program Kerja</label>
                            <textarea name="evaluasi" id="evaluasi" class="form-control" rows="10" 
                                      placeholder="Tuliskan evaluasi program kerja, termasuk:&#10;- Pencapaian tujuan&#10;- Kendala yang dihadapi&#10;- Saran perbaikan&#10;- Lesson learned&#10;- dll." required><?= htmlspecialchars($_POST['evaluasi'] ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Simpan</button>
                   <a href="manage_evaluasi.php?proker_id=<?= $proker_id ?>" class="btn btn-info">
                                <i class="fas fa-times"></i> Batal
                            </a> 
                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/script.js"></script>
</body>
</html>