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
        COUNT(DISTINCT d.id) as total_dokumentasi,
        e.evaluasi
    FROM program_kerja pk
    LEFT JOIN users u ON pk.ketua_pelaksana_id = u.id
    LEFT JOIN dokumentasi d ON pk.id = d.proker_id
    LEFT JOIN evaluasi e ON pk.id = e.proker_id
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

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_proker_' . $tahun . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Program Kerja <?= $tahun ?></title>
</head>
<body>
    <table border="1">
        <tr>
            <td colspan="9" style="text-align: center; font-weight: bold; font-size: 16px;">
                LAPORAN PROGRAM KERJA HIMPUNAN MAHASISWA TAHUN <?= $tahun ?>
            </td>
        </tr>
        <tr>
            <td colspan="9" style="text-align: center;">
                <?php if ($angkatan): ?>Angkatan: <?= $angkatan ?><br><?php endif; ?>
                Dicetak pada: <?= date('d/m/Y H:i:s') ?>
            </td>
        </tr>
        <tr><td colspan="9"></td></tr>
        
        <tr style="font-weight: bold; background-color: #f2f2f2;">
            <td>No</td>
            <td>Nama Program</td>
            <td>Deskripsi</td>
            <td>Ketua Pelaksana</td>
            <td>Angkatan</td>
            <td>Tanggal Mulai</td>
            <td>Tanggal Selesai</td>
            <td>Status</td>
            <td>Total Dokumentasi</td>
            <td>Evaluasi</td>
        </tr>
        
        <?php foreach ($prokers as $index => $proker): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= sanitize($proker['nama_proker']) ?></td>
            <td><?= sanitize($proker['deskripsi'] ?? '') ?></td>
            <td><?= sanitize($proker['ketua_nama'] ?? 'Belum ditentukan') ?></td>
            <td><?= sanitize($proker['angkatan'] ?? '') ?></td>
            <td><?= $proker['tanggal_mulai'] ? formatTanggal($proker['tanggal_mulai']) : '' ?></td>
            <td><?= $proker['tanggal_selesai'] ? formatTanggal($proker['tanggal_selesai']) : '' ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $proker['status'])) ?></td>
            <td><?= $proker['total_dokumentasi'] ?></td>
            <td><?= sanitize($proker['evaluasi'] ?? 'Belum ada evaluasi') ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr><td colspan="9"></td></tr>
        <tr>
            <td colspan="9" style="font-weight: bold;">
                Total Program Kerja: <?= count($prokers) ?> | 
                Dicetak oleh: <?= $_SESSION['nama'] ?> (<?= ucfirst($_SESSION['role']) ?>)
            </td>
        </tr>
    </table>
</body>
</html>