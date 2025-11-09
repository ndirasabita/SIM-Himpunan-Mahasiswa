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

// Ambil semua proposal
$stmt = $pdo->prepare("
    SELECT d.*, u.nama as uploaded_by_name 
    FROM dokumentasi d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    WHERE d.proker_id = ? AND d.jenis = 'proposal'
    ORDER BY d.uploaded_at DESC
");
$stmt->execute([$proker_id]);
$proposals = $stmt->fetchAll();

// Handle upload jika ada
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proposal_file'])) {
    $upload_dir = '../../uploads/proposal/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['proposal_file'];
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validasi file
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file_ext, $allowed_extensions)) {
        $upload_message = '<div class="alert alert-danger">Format file tidak diizinkan. Gunakan PDF, DOC, atau DOCX.</div>';
    } elseif ($file_size > $max_size) {
        $upload_message = '<div class="alert alert-danger">Ukuran file terlalu besar. Maksimal 10MB.</div>';
    } else {
        // Generate unique filename
        $new_filename = 'proposal_' . $proker_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($tmp_name, $file_path)) {
            // Simpan ke database
            $stmt = $pdo->prepare("
                INSERT INTO dokumentasi (proker_id, jenis, nama_file, file_path, uploaded_by, uploaded_at) 
                VALUES (?, 'proposal', ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$proker_id, $filename, 'uploads/proposal/' . $new_filename, $_SESSION['user_id']])) {
                $upload_message = '<div class="alert alert-success">Proposal berhasil diupload!</div>';
                // Refresh data
                $stmt = $pdo->prepare("
                    SELECT d.*, u.nama as uploaded_by_name 
                    FROM dokumentasi d 
                    LEFT JOIN users u ON d.uploaded_by = u.id 
                    WHERE d.proker_id = ? AND d.jenis = 'proposal'
                    ORDER BY d.uploaded_at DESC
                ");
                $stmt->execute([$proker_id]);
                $proposals = $stmt->fetchAll();
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
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
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
    <title>Upload Proposal - <?= sanitize($proker['nama_proker']) ?></title>
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
            <h1>📄 Proposal</h1> 
                  

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
                    <strong><?= count($proposals) ?></strong><br>
                    <small>Proposal tersimpan</small>
                </div>
            </div>



            <!-- Upload Zone -->

           <?php if (in_array($_SESSION['role'], ['sekretaris', 'ketua_pelaksana'])): ?>
            <?= $upload_message ?>
            <div class="upload-zone" onclick="document.getElementById('proposalFile').click();">
                <div class="upload-icon">📤</div>
                <h3>Klik atau Drag & Drop untuk Upload Proposal</h3>
                <p>Format yang didukung: PDF, DOC, DOCX (Maks. 10MB)</p>
                <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: inline;">
                    <input type="file" id="proposalFile" name="proposal_file" class="file-input" 
                           accept=".pdf,.doc,.docx" onchange="handleFileSelect(this)">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('proposalFile').click();">
                        Pilih File
                    </button>
                </form>
            </div>
            <?php endif; ?>
            

            <!-- Proposals Grid -->
            <?php if (count($proposals) > 0): ?>
                <div class="proposals-grid">
                    <?php foreach ($proposals as $proposal): ?>
                        <div class="proposal-card" onclick="previewFile('<?= $proposal['file_path'] ?>', '<?= sanitize($proposal['nama_file']) ?>')">
                            <div class="proposal-icon">
                                <?= getFileIcon($proposal['nama_file']) ?>
                            </div>
                            <div class="proposal-name">
                                <?= sanitize($proposal['nama_file']) ?>
                            </div>
                            <div class="proposal-meta">
                                Diupload oleh: <?= sanitize($proposal['uploaded_by_name']) ?><br>
                                <?= date('d/m/Y H:i', strtotime($proposal['uploaded_at'])) ?>
                            </div>
                            <div class="proposal-actions" onclick="event.stopPropagation();">
                                <a href="download.php?id=<?= $proposal['id'] ?>" 
                                   class="btn btn-success btn-small">Download</a>
                          <a href="delete_dokumentasi.php?id=<?= $proposal['id'] ?>&proker_id=<?= $proker_id ?>&return_to=proposal" 
                            class="btn btn-danger btn-small"
                            onclick="return confirm('Yakin ingin menghapus proposal ini?')">Hapus</a>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>Belum ada proposal</h3>
                    <p>Upload proposal pertama Anda dengan mengklik area upload di atas</p>
                </div>
            <?php endif; ?>
            
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

    <!-- Modal Preview -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="fileName"></h3>
            <p>Klik tombol di bawah untuk membuka file:</p>
            <a id="fileLink" href="#" target="_blank" class="btn btn-primary">Buka File</a>
        </div>
    </div>


    <script>
        // Drag and drop functionality
        const uploadZone = document.querySelector('.upload-zone');
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('proposalFile');
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        });

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileName = file.name;
                const fileSize = file.size;
                
                // Validasi
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak diizinkan. Gunakan PDF, DOC, atau DOCX.');
                    input.value = '';
                    return;
                }
                
                if (fileSize > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 10MB.');
                    input.value = '';
                    return;
                }
                
                // Konfirmasi upload
                if (confirm(`Upload file "${fileName}"?`)) {
                    document.getElementById('uploadForm').submit();
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
        <?php if (isset($_POST['proposal_file'])): ?>
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>   

 
