<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkLogin();
checkRole(['sekretaris', 'ketua_pelaksana', 'ketua_umum']);

$proker_id = $_GET['proker_id'] ?? 0;


// Ambil data program kerja
if ($_SESSION['role'] === 'ketua_pelaksana') {
    $stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ? AND ketua_pelaksana_id = ?");
    $stmt->execute([$proker_id, $_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id = ?");
    $stmt->execute([$proker_id]);
}
$proker = $stmt->fetch();

if (!$proker) {
    die('Program kerja tidak ditemukan atau Anda tidak memiliki akses.');
}

// Ambil semua foto
$stmt = $pdo->prepare("
    SELECT d.*, u.nama as uploaded_by_name 
    FROM dokumentasi d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    WHERE d.proker_id = ? AND d.jenis = 'foto'
    ORDER BY d.uploaded_at DESC
");
$stmt->execute([$proker_id]);
$fotos = $stmt->fetchAll();

// Handle upload jika ada
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_file'])) {
    $upload_dir = '../../uploads/foto/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['foto_file'];
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validasi file
    $allowed_extensions = ['jpg', 'jpeg', 'png','mp4'];
    $max_size = 1024 * 1024 * 1024; 
    
    if (!in_array($file_ext, $allowed_extensions)) {
        $upload_message = '<div class="alert alert-danger">Format file tidak diizinkan. Gunakan JPG, JPEG, PNG, MP4.</div>';
    } elseif ($file_size > $max_size) {
        $upload_message = '<div class="alert alert-danger">Ukuran file terlalu besar. Maksimal 1GB.</div>';
    } else {
        // Generate unique filename
        $new_filename = 'foto_' . $proker_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($tmp_name, $file_path)) {
            // Simpan ke database
            $stmt = $pdo->prepare("
                INSERT INTO dokumentasi (proker_id, jenis, nama_file, file_path, uploaded_by, uploaded_at) 
                VALUES (?, 'foto', ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$proker_id, $filename, 'uploads/foto/' . $new_filename, $_SESSION['user_id']])) {
                $upload_message = '<div class="alert alert-success">Foto berhasil diupload!</div>';
                // Refresh data
                $stmt = $pdo->prepare("
                    SELECT d.*, u.nama as uploaded_by_name 
                    FROM dokumentasi d 
                    LEFT JOIN users u ON d.uploaded_by = u.id 
                    WHERE d.proker_id = ? AND d.jenis = 'foto'
                    ORDER BY d.uploaded_at DESC
                ");
                $stmt->execute([$proker_id]);
                $fotos = $stmt->fetchAll();
            } else {
                $upload_message = '<div class="alert alert-danger">Gagal menyimpan data ke database.</div>';
                unlink($file_path);
            }
        } else {
            $upload_message = '<div class="alert alert-danger">Gagal mengupload file.</div>';
        }
    }
}

// Function untuk mendapatkan icon berdasarkan extension
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
            return '📷';
        case 'mp4':
            return '🎞️';
        default:
            return '📎';
    }
}



// Function untuk format ukuran file
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload dokuemtasi kegiatan - <?= sanitize($proker['nama_proker']) ?></title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../../js/script.js"></script>
    <style>
        body::-webkit-scrollbar {
    display: none;
}
        </style>
    
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
            <h1>📄 Dokuemntasi Kegaitan</h1>
                  <div style="display: flex; gap: 15px; justify-content: center">
                </div>
        </div>

        <div class="dashboard">
            <div class="stats-bar">
                <div>
                    <h3 style="margin: 0;"><?= sanitize($proker['nama_proker']) ?></h3>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        Status: <?= getStatusBadge($proker['status']) ?> | 
                        Angkatan: <?= sanitize($proker['angkatan'] ?? '-') ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <strong><?= count($fotos) ?></strong><br>
                    <small>Dokumentasi tersimpan</small>
                </div>
            </div>

            <?= $upload_message ?>

            <!-- Upload Zone -->
             <?php if (in_array($_SESSION['role'], ['sekretaris', 'ketua_pelaksana'])): ?>
            <div class="upload-zone" onclick="document.getElementById('fotoFile').click();">
                <div class="upload-icon">📤</div>
                <h3>Klik atau Drag & Drop untuk Upload Foto dan Video</h3>
                <p>Format yang didukung: JPG, JPEG, PNG. MP4 (Maks. 1GB)</p>
                <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: inline;">
                   <input type="file" id="fotoFile" name="foto_file" class="file-input" 
                       accept=".jpg,.jpeg,.png, .mp4" onchange="handleFileSelect(this)">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fotoFile').click();">
                        Pilih File
                    </button>
                </form>
            </div>
            <?php endif?>

            <!-- Fotos Grid -->
            <?php if (count($fotos) > 0): ?>
                <div class="fotos-grid">
                    <?php foreach ($fotos as $foto): ?>
                        <div class="foto-card" onclick="previewFile('<?= $foto['file_path'] ?>', '<?= sanitize($foto['nama_file']) ?>')">
                         <div class="foto-preview">
                                <?php
                                $ext = strtolower(pathinfo($foto['nama_file'], PATHINFO_EXTENSION));
                                $file_src = "../../" . $foto['file_path'];
                                ?>

                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="<?= $file_src ?>" alt="<?= sanitize($foto['nama_file']) ?>" style="max-width: 100%; max-height: 150px; border-radius: 5px;">
                                
                                <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                    <video width="100%" height="150" style="border-radius: 5px;" controls muted>
                                        <source src="<?= $file_src ?>" type="video/<?= $ext ?>">
                                        Browser tidak mendukung pemutaran video.
                                    </video>
                                
                                <?php else: ?>
                                    <div class="foto-icon"><?= getFileIcon($foto['nama_file']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="foto-meta">
                                Diupload oleh: <?= sanitize($foto['uploaded_by_name']) ?><br>
                                <?= date('d/m/Y H:i', strtotime($foto['uploaded_at'])) ?>
                             </div>
                            <div class="foto-actions" onclick="event.stopPropagation();">
                                <a href="download.php?id=<?= $foto['id'] ?>" 
                                   class="btn btn-success btn-small">Download</a>
                                <a href="delete_dokumentasi.php?id=<?= $foto['id'] ?>&proker_id=<?= $proker_id ?>&return_to=foto" 
                                        class="btn btn-danger btn-small"
                                        onclick="return confirm('Yakin ingin menghapus foto ini?')">Hapus</a>

                            </div>
                        </div>

                        <?php
                            $success = $success ?? ($_GET['success'] ?? '');
                            $error = $error ?? ($_GET['error'] ?? '');
                            ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>Belum ada foto</h3>
                    <p>Upload foto pertama Anda dengan mengklik area upload di atas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Preview -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="fileName"></h3>
            <p>Klik tombol di bawah untuk membuka file:</p>
            <a id="fileLink" href="#" target="_blank" class="btn btn-primary">Buka File</a>
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

    <script>
        // Drag and drop functionality
       uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = document.getElementById('fotoFile');
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(files[0]); // hanya file pertama
        fileInput.files = dataTransfer.files;

        handleFileSelect(fileInput);
    }
});


function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        const fileName = file.name;
        const fileSize = file.size;

        const allowedTypes = ['image/jpeg', 'image/png', 'video/mp4'];
        const maxSize = 1 * 1024 * 1024 * 1024; // 1GB

        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak diizinkan. Gunakan JPEG, PNG, atau MP4.');
            return;
        }

        if (fileSize > maxSize) {
            alert(`Ukuran file terlalu besar (${(fileSize / (1024 * 1024)).toFixed(2)} MB). Maksimal 1GB.`);
            return;
        }

        if (confirm(`Upload file "${fileName}"?`)) {
            document.getElementById('uploadForm').submit(); // langsung kirim form
        }
    }
}


        function previewFile(filePath, fileName) {
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('fileLink').href = '../../' + filePath;
            document.getElementById('previewModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('previewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Auto refresh setelah upload
        <?php if (isset($_POST['foto_file'])): ?>
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>   

