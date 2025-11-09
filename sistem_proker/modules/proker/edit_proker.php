<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_pelaksana']);

$id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Ambil data program kerja
$stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ?");
$stmt->execute([$id]);
$proker = $stmt->fetch();

if (!$proker) {
    header('Location: manage_proker.php');
    exit();
}

// Cek hak akses ketua pelaksana hanya bisa edit proker miliknya sendiri
if ($_SESSION['role'] == 'ketua_pelaksana' && $proker['ketua_pelaksana_id'] != $_SESSION['user_id']) {
    die('Akses ditolak. Anda hanya bisa mengedit program kerja yang Anda pimpin.');
}

// Ambil daftar ketua pelaksana
$stmt = $pdo->query("SELECT id, nama FROM users WHERE role = 'ketua_pelaksana' ORDER BY nama");
$ketua_pelaksana_list = $stmt->fetchAll();

if ($_POST) {
    $nama_proker = sanitize($_POST['nama_proker']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $status = $_POST['status'];
    $angkatan = sanitize($_POST['angkatan']);
    $ketua_pelaksana_id = $_POST['ketua_pelaksana_id'] ?: null;

    if (empty($nama_proker)) {
        $error = 'Nama program kerja harus diisi';
    } elseif ($tanggal_selesai && $tanggal_selesai < $tanggal_mulai) {
        $error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE program_kerja SET 
                nama_proker = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, 
                status = ?, angkatan = ?, ketua_pelaksana_id = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $nama_proker,
                $deskripsi,
                $tanggal_mulai ?: null,
                $tanggal_selesai ?: null,
                $status,
                $angkatan,
                $ketua_pelaksana_id,
                $id
            ]);
            
            $success = 'Program kerja berhasil diperbarui';
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ?");
            $stmt->execute([$id]);
            $proker = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui program kerja: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Program Kerja</title>
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
                 <?php if ($_SESSION['role']): ?>
                    <a href="../users/manage_users.php">
                        <i class="fas fa-users"></i>
                        Kelola User
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
                    <h1>Edit Program Kerja</h1>
                </div>

        <div class="dashboard">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" style="max-width: 800px; margin: 0 auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="nama_proker">Nama Program Kerja *</label>
                  <input type="text" class="form-control" id="nama_proker" name="nama_proker" 
                           value="<?= sanitize($proker['nama_proker']) ?>" required>
                    </div>

                    <div class="form-group">
                            <label for="angkatan">Angkatan</label>
                    <input type="text" class="form-control" id="angkatan" name="angkatan" 
                           value="<?= sanitize($proker['angkatan']) ?>" placeholder="Contoh: 2021, 2022">
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Program Kerja</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" 
                              rows="4" style="resize: vertical;"><?= sanitize($proker['deskripsi']) ?></textarea>
                </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" 
                               value="<?= $proker['tanggal_mulai'] ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" 
                               value="<?= $proker['tanggal_selesai'] ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                    <label for="ketua_pelaksana_id">Ketua Pelaksana</label>
                    <select class="form-control" id="ketua_pelaksana_id" name="ketua_pelaksana_id"
                            <?= $_SESSION['role'] == 'ketua_pelaksana' ? 'disabled' : '' ?>>
                        <option value="">Pilih Ketua Pelaksana</option>
                        <?php foreach ($ketua_pelaksana_list as $ketua): ?>
                        <option value="<?= $ketua['id'] ?>" 
                                <?= $proker['ketua_pelaksana_id'] == $ketua['id'] ? 'selected' : '' ?>>
                            <?= sanitize($ketua['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($_SESSION['role'] == 'ketua_pelaksana'): ?>
                        <input type="hidden" name="ketua_pelaksana_id" value="<?= $proker['ketua_pelaksana_id'] ?>">
                    <?php endif; ?>
                    </div>

            
                <div class="form-group">
                    <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="belum_mulai" <?= (($_POST['status'] ?? 'belum_mulai') == 'belum_mulai') ? 'selected' : '' ?>>
                                Belum Mulai
                            </option>
                            <option value="sedang_berjalan" <?= (($_POST['status'] ?? '') == 'sedang_berjalan') ? 'selected' : '' ?>>
                                Sedang Berjalan
                            </option>
                            <option value="selesai" <?= (($_POST['status'] ?? '') == 'selesai') ? 'selected' : '' ?>>
                                Selesai
                            </option>
                            <option value="dibatalkan" <?= (($_POST['status'] ?? '') == 'dibatalkan') ? 'selected' : '' ?>>
                                Dibatalkan
                            </option>
                        </select>            
                </div>

                </div>
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">Update Program Kerja</button>
                    <a href="manage_proker.php" class="btn btn-info">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>