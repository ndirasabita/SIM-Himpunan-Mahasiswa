<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'sekretaris':
            header('Location: dashboard/sekretaris.php');
            break;
        case 'ketua_pelaksana':
            header('Location: dashboard/ketua_pelaksana.php');
            break;
        case 'ketua_umum':
            header('Location: dashboard/ketua_umum.php');
            break;
    }
    exit();
}

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && md5($password) == $user['password']) {
            if ($user['status'] !== 'aktif') {
                $error = 'Akun Anda nonaktif. Silakan hubungi admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                
                switch ($user['role']) {
                    case 'sekretaris':
                        header('Location: dashboard/sekretaris.php');
                        break;
                    case 'ketua_pelaksana':
                        header('Location: dashboard/ketua_pelaksana.php');
                        break;
                    case 'ketua_umum':
                        header('Location: dashboard/ketua_umum.php');
                        break;
                }
                exit();
            }
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Program Kerja</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="jarak">
     <div class="login-wrapper">
    <div class="logo-container">
        <img src="assets/img/logo.svg" alt="Logo" class="login-logo">
    </div>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Log In</h2>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Program Kerja HIMTIF
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
    </div>
    </div>
</body>
</html>