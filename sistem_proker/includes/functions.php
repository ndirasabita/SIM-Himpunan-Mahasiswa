<?php
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatTanggal($date) {
    return date('d/m/Y', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'belum_mulai' => '<span class="badge badge-warning">Belum Mulai</span>',
        'sedang_berjalan' => '<span class="badge badge-info">Sedang Berjalan</span>',
        'selesai' => '<span class="badge badge-success">Selesai</span>',
        'dibatalkan' => '<span class="badge badge-danger">Dibatalkan</span>',
    ];
    return $badges[$status] ?? $status;
}

function uploadFile($file, $uploadDir) {
    $allowedTypes = ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'doc', 'docx'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error saat upload file'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }

    $fileName = time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'fileName' => $fileName, 'filePath' => $filePath];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// Fungsi untuk membuat ID Program Kerja unik
function generateProkerID($pdo, $angkatan) {
    $year = date('y'); // Tahun sekarang (2 digit)
    $angkatan = substr($angkatan, -2); // 2 digit terakhir dari angkatan

    // Hitung jumlah proker yang sudah ada untuk kombinasi tahun & angkatan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM program_kerja WHERE id LIKE ?");
    $likePattern = $year . $angkatan . '%';
    $stmt->execute([$likePattern]);
    $count = $stmt->fetchColumn();

    $nextNumber = str_pad($count + 1, 4, '0', STR_PAD_LEFT); // 0001, 0002, dst.

    return $year . $angkatan . $nextNumber;
}
?>
