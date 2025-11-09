<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris']); // Hanya sekretaris yang bisa menghapus

$message = '';
$error = '';

// Ambil ID program kerja
$proker_id = $_GET['id'] ?? '';


if (!$proker_id) {
    header('Location: manage_proker.php');
    exit();
}

// Ambil data program kerja
$stmt = $pdo->prepare("
    SELECT pk.*, u.nama as ketua_nama 
    FROM program_kerja pk 
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id 
    WHERE pk.id = ?
");
$stmt->execute([$proker_id]);
$proker = $stmt->fetch();

if (!$proker) {
    header('Location: manage_proker.php');
    exit();
}

// Proses penghapusan
if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        
        // Hapus file dokumentasi terkait
        $stmt = $pdo->prepare("SELECT file_path FROM dokumentasi WHERE proker_id = ?");
        $stmt->execute([$proker_id]);
        $files = $stmt->fetchAll();
        
        foreach ($files as $file) {
            if (file_exists('../../' . $file['file_path'])) {
                unlink('../../' . $file['file_path']);
            }
        }
        
        // Hapus dokumentasi dari database (CASCADE akan menghapus otomatis)
        // Hapus evaluasi dari database (CASCADE akan menghapus otomatis)
        
        // Hapus program kerja
        $stmt = $pdo->prepare("DELETE FROM program_kerja WHERE id = ?");
        $stmt->execute([$proker_id]);
        
        $pdo->commit();
        
        // Redirect dengan pesan sukses
        header('Location: manage_proker.php?deleted=1');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Gagal menghapus program kerja: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Program Kerja</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
    <style>
        .delete-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .delete-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .delete-warning i {
            font-size: 24px;
            color: #856404;
        }
        
        .proker-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-delete-confirm {
            background-color: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-delete-confirm:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!


        <div class="main-content" id="mainContent">
            <div class="container">
                

                <div class="delete-container">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="delete-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Peringatan!</strong><br>
                            Anda akan menghapus program kerja secara permanen. Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait termasuk dokumentasi dan evaluasi.
                        </div>
                    </div>

                    <h3 style="text-align: center; margin-bottom: 20px; color: #dc3545;">
                        Detail Program Kerja yang akan dihapus:
                    </h3>

                    <div class="proker-details">
                        <div class="detail-row">
                            <span class="detail-label">Nama Program:</span>
                            <span class="detail-value"><?= htmlspecialchars($proker['nama_proker']) ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Ketua Pelaksana:</span>
                            <span class="detail-value"><?= htmlspecialchars($proker['ketua_nama'] ?? 'Belum ditentukan') ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Mulai:</span>
                            <span class="detail-value"><?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : 'Belum ditentukan' ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Selesai:</span>
                            <span class="detail-value"><?= $proker['tanggal_selesai'] ? formatTanggal($proker['tanggal_selesai']) : 'Belum ditentukan' ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value"><?= getStatusBadge($proker['status']) ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Angkatan:</span>
                            <span class="detail-value"><?= htmlspecialchars($proker['angkatan'] ?? 'Tidak ditentukan') ?></span>
                        </div>
                        
                        <?php if ($proker['deskripsi']): ?>
                        <div class="detail-row">
                            <span class="detail-label">Deskripsi:</span>
                            <span class="detail-value"><?= htmlspecialchars(substr($proker['deskripsi'], 0, 100)) ?><?= strlen($proker['deskripsi']) > 100 ? '...' : '' ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">Dibuat:</span>
                            <span class="detail-value"><?= date('d/m/Y H:i', strtotime($proker['created_at'])) ?></span>
                        </div>
                    </div>

                    <?php
                    // Cek apakah ada dokumentasi terkait
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM dokumentasi WHERE proker_id = ?");
                    $stmt->execute([$proker_id]);
                    $totalDokumentasi = $stmt->fetchColumn();

                    // Cek apakah ada evaluasi terkait
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM evaluasi WHERE proker_id = ?");
                    $stmt->execute([$proker_id]);
                    $totalEvaluasi = $stmt->fetchColumn();
                    ?>

                    <?php if ($totalDokumentasi > 0 || $totalEvaluasi > 0): ?>
                    <div class="alert alert-info" style="margin: 20px 0;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Data terkait yang akan ikut terhapus:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php if ($totalDokumentasi > 0): ?>
                            <li><?= $totalDokumentasi ?> dokumentasi (proposal, surat, foto)</li>
                            <?php endif; ?>
                            <?php if ($totalEvaluasi > 0): ?>
                            <li><?= $totalEvaluasi ?> evaluasi</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return confirmDelete()">
                        <div class="button-group">
                            <a href="manage_proker.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" name="confirm_delete" class="btn-delete-confirm">
                                <i class="fas fa-trash"></i> Ya, Hapus Program Kerja
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>