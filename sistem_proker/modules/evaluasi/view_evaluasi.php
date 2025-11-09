<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['ketua_pelaksana', 'sekretaris', 'ketua_umum']);

$proker_id = $_GET['proker_id'] ?? 0;

// Ambil data program kerja dan evaluasi
$stmt = $pdo->prepare("
    SELECT pk.*, e.evaluasi, e.created_at as evaluasi_created, e.updated_at as evaluasi_updated,
           u.nama as ketua_nama, creator.nama as evaluator_nama
    FROM program_kerja pk 
    LEFT JOIN evaluasi e ON pk.id = e.proker_id
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id
    LEFT JOIN users creator ON e.created_by = creator.id
    WHERE pk.id = ?
");
$stmt->execute([$proker_id]);
$data = $stmt->fetch();

if (!$data) {
    die('Program kerja tidak ditemukan.');
}

// Cek akses untuk ketua pelaksana
if ($_SESSION['role'] == 'ketua_pelaksana' && $data['ketua_pelaksana_id'] != $_SESSION['user_id']) {
    die('Anda tidak memiliki akses untuk melihat evaluasi program kerja ini.');
}

if (!$data['evaluasi']) {
    die('Evaluasi untuk program kerja ini belum tersedia.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Program Kerja</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .evaluasi-content {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
            line-height: 1.8;
            white-space: pre-wrap;
            font-size: 16px;
        }
        
        .print-btn {
            float: right;
            margin-bottom: 20px;
        }
        
        @media print {
            .no-print { display: none !important; }
            .evaluasi-content { background: white !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <h1>Evaluasi Program Kerja</h1>
            <div class="user-info">
                <span><?= $_SESSION['nama'] ?></span>
                <a href="../../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="nav-menu no-print">
            <ul>
                <li><a href="../../dashboard/<?= $_SESSION['role'] ?>.php">Dashboard</a></li>
                <?php if ($_SESSION['role'] == 'ketua_pelaksana'): ?>
                <li><a href="manage_evaluasi.php">Kelola Evaluasi</a></li>
                <?php endif; ?>
                <li><a href="../proker/view_proker.php?id=<?= $proker_id ?>">Detail Program Kerja</a></li>
            </ul>
        </div>

        <div class="dashboard">
            <button onclick="window.print()" class="btn btn-info print-btn no-print">Cetak Evaluasi</button>
            
            <div class="card">
                <h3><?= sanitize($data['nama_proker']) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div>
                        <strong>Ketua Pelaksana:</strong><br>
                        <?= sanitize($data['ketua_nama'] ?? 'Belum ditentukan') ?>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <?= getStatusBadge($data['status']) ?>
                    </div>
                    <div>
                        <strong>Angkatan:</strong><br>
                        <?= sanitize($data['angkatan'] ?? '-') ?>
                    </div>
                    <div>
                        <strong>Periode:</strong><br>
                        <?= $data['tanggal_mulai'] ? formatTanggal($data['tanggal_mulai']) : '-' ?>
                        <?php if ($data['tanggal_selesai']): ?>
                            s/d <?= formatTanggal($data['tanggal_selesai']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Evaluasi Program Kerja</h3>
                    <?php if ($_SESSION['role'] == 'ketua_pelaksana' && $data['ketua_pelaksana_id'] == $_SESSION['user_id']): ?>
                        <a href="edit_evaluasi.php?proker_id=<?= $proker_id ?>" class="btn btn-warning no-print">Edit Evaluasi</a>
                    <?php endif; ?>
                </div>

                <div class="evaluasi-content">
<?= sanitize($data['evaluasi']) ?>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;">
                    <strong>Informasi Evaluasi:</strong><br>
                    Dibuat oleh: <?= sanitize($data['evaluator_nama']) ?><br>
                    Tanggal dibuat: <?= date('d/m/Y H:i', strtotime($data['evaluasi_created'])) ?><br>
                    <?php if ($data['evaluasi_updated'] != $data['evaluasi_created']): ?>
                        Terakhir diupdate: <?= date('d/m/Y H:i', strtotime($data['evaluasi_updated'])) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="no-print" style="text-align: center; margin-top: 30px;">
                <?php if ($_SESSION['role'] == 'ketua_pelaksana'): ?>
                    <a href="manage_evaluasi.php" class="btn btn-info">Kembali ke Kelola Evaluasi</a>
                <?php else: ?>
                    <a href="../../dashboard/<?= $_SESSION['role'] ?>.php" class="btn btn-info">Kembali ke Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>