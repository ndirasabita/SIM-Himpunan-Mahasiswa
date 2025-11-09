<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_pelaksana']);

// Ambil daftar ketua pelaksana
$stmt = $pdo->query("SELECT id, nama FROM users WHERE role = 'ketua_pelaksana' ORDER BY nama");
$ketuaPelaksanas = $stmt->fetchAll();

$error = '';
$success = '';

if ($_POST) {
    $nama_proker = sanitize($_POST['nama_proker'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $status = $_POST['status'] ?? 'belum_mulai';
    $angkatan = sanitize($_POST['angkatan'] ?? '');
    $ketua_pelaksana_id = $_POST['ketua_pelaksana_id'] ?? null;

    // Validasi
    if (empty($nama_proker)) {
        $error = 'Nama program kerja harus diisi';
    } elseif ($tanggal_mulai && $tanggal_selesai && $tanggal_mulai > $tanggal_selesai) {
        $error = 'Tanggal mulai tidak boleh lebih dari tanggal selesai';
    } elseif (empty($angkatan) || strlen($angkatan) != 4) {
        $error = 'Tahun angkatan harus diisi dengan 4 digit (misal: 2023)';
    } else {
        try {
            // Generate ID unik: 2 digit akhir tahun saat ini + 2 digit akhir tahun angkatan + 4 digit urutan
            $tahunBuat = date('Y');
            $tahunAkhir = substr($tahunBuat, -2);
            $angkatanAkhir = substr($angkatan, -2);

            // Hitung urutan terakhir untuk kombinasi ini
            $prefix = $tahunAkhir . $angkatanAkhir;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM program_kerja WHERE id LIKE ?");
            $stmt->execute([$prefix . '%']);
            $count = $stmt->fetchColumn() + 1;
            $urutan = str_pad($count, 4, '0', STR_PAD_LEFT);

            $id_proker = $prefix . $urutan;

            // Simpan data
            $stmt = $pdo->prepare("
                INSERT INTO program_kerja 
                (id, nama_proker, deskripsi, tanggal_mulai, tanggal_selesai, status, angkatan, ketua_pelaksana_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_proker,
                $nama_proker,
                $deskripsi ?: null,
                $tanggal_mulai ?: null,
                $tanggal_selesai ?: null,
                $status,
                $angkatan,
                $ketua_pelaksana_id ?: null,
                $_SESSION['user_id']
            ]);

            $_SESSION['success'] = 'Program kerja berhasil ditambahkan';
            header('Location: manage_proker.php');
            exit();

        } catch (Exception $e) {
            $error = 'Gagal menambah program kerja: ' . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Program Kerja</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
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
                    <h1>Tambah Program Kerja</h1>
                </div>


        <div class="dashboard">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" style="max-width: 800px; margin: 0 auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="nama_proker">Nama Program Kerja *</label>
                        <input type="text" class="form-control" id="nama_proker" name="nama_proker" 
                               value="<?= htmlspecialchars($_POST['nama_proker'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="angkatan">Angkatan</label>
                        <input type="text" class="form-control" id="angkatan" name="angkatan" 
                               placeholder="Contoh: 2023, 2024" 
                               value="<?= htmlspecialchars($_POST['angkatan'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Program Kerja</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" 
                              placeholder="Jelaskan tujuan dan detail program kerja..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" 
                               value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" 
                               value="<?= htmlspecialchars($_POST['tanggal_selesai'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="ketua_pelaksana_id">Ketua Pelaksana</label>
                        <select class="form-control" id="ketua_pelaksana_id" name="ketua_pelaksana_id">
                            <option value="">Pilih Ketua Pelaksana</option>
                            <?php foreach ($ketuaPelaksanas as $ketua): ?>
                                <option value="<?= $ketua['id'] ?>" 
                                        <?= (($_POST['ketua_pelaksana_id'] ?? '') == $ketua['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($ketua['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                    <button type="submit" class="btn btn-success">Tambah Program Kerja</button>
                    <a href="manage_proker.php" class="btn btn-info">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !mobileToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
        // Auto update tanggal selesai ketika tanggal mulai diubah
        document.getElementById('tanggal_mulai').addEventListener('change', function() {
            const tanggalMulai = this.value;
            const tanggalSelesai = document.getElementById('tanggal_selesai');
            
            if (tanggalMulai && !tanggalSelesai.value) {
                // Set tanggal selesai 30 hari setelah tanggal mulai sebagai default
                const mulai = new Date(tanggalMulai);
                mulai.setDate(mulai.getDate() + 30);
                tanggalSelesai.value = mulai.toISOString().split('T')[0];
            }
        });

        // Validasi tanggal
        document.getElementById('tanggal_selesai').addEventListener('change', function() {
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const tanggalSelesai = this.value;
            
            if (tanggalMulai && tanggalSelesai && tanggalMulai > tanggalSelesai) {
                alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai');
                this.value = '';
            }
        });
    </script>
</body>
</html>