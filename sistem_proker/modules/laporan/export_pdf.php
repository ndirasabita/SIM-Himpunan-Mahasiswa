<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_umum']);

// Filter sama seperti laporan_proker.php
$angkatan = $_GET['angkatan'] ?? '';
$status = $_GET['status'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');

// Query untuk data laporan
$detailQuery = "
    SELECT 
        pk.*,
        u.nama as ketua_nama,
        COUNT(DISTINCT d.id) as total_dokumentasi
    FROM program_kerja pk
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id
    LEFT JOIN dokumentasi d ON pk.id = d.proker_id
    WHERE YEAR(pk.created_at) = ?
";
$detailParams = [$tahun];

if ($angkatan) {
    $detailQuery .= " AND pk.angkatan = ?";
    $detailParams[] = $angkatan;
}

if ($status) {
    $detailQuery .= " AND pk.status = ?";
    $detailParams[] = $status;
}

$detailQuery .= " GROUP BY pk.id ORDER BY pk.created_at DESC";

$stmt = $pdo->prepare($detailQuery);
$stmt->execute($detailParams);
$prokers = $stmt->fetchAll();

// Set header untuk download PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="laporan_proker_' . $tahun . '.pdf"');

// Untuk implementasi sederhana, kita export sebagai HTML yang bisa diprint ke PDF
// Dalam implementasi nyata, gunakan library seperti TCPDF atau mPDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Program Kerja <?= $tahun ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .status-selesai { color: green; font-weight: bold; }
        .status-berjalan { color: blue; font-weight: bold; }
        .status-belum { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN PROGRAM KERJA HIMPUNAN MAHASISWA</h2>
        <h3>TAHUN <?= $tahun ?></h3>
        <?php if ($angkatan): ?>
        <p>Angkatan: <?= $angkatan ?></p>
        <?php endif; ?>
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Program</th>
                <th>Ketua Pelaksana</th>
                <th>Angkatan</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
                <th>Dokumentasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prokers as $index => $proker): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= sanitize($proker['nama_proker']) ?></td>
                <td><?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?></td>
                <td><?= sanitize($proker['angkatan'] ?? '-') ?></td>
                <td><?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : '-' ?></td>
                <td><?= $proker['tanggal_selesai'] ? formatTanggal($proker['tanggal_selesai']) : '-' ?></td>
                <td>
                    <span class="status-<?= $proker['status'] == 'selesai' ? 'selesai' : ($proker['status'] == 'sedang_berjalan' ? 'berjalan' : 'belum') ?>">
                        <?= ucfirst(str_replace('_', ' ', $proker['status'])) ?>
                    </span>
                </td>
                <td><?= $proker['total_dokumentasi'] ?> file</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <p><strong>Total Program Kerja:</strong> <?= count($prokers) ?></p>
        <p><strong>Dicetak oleh:</strong> <?= $_SESSION['nama'] ?> (<?= ucfirst($_SESSION['role']) ?>)</p>
    </div>
</body>
</html>