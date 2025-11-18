<?php
session_start();

// include koneksi.php (cek di folder ini atau parent)
if (file_exists(__DIR__ . '/koneksi.php')) {
    include __DIR__ . '/koneksi.php';
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    include __DIR__ . '/../koneksi.php';
} else {
    die("<strong>Error:</strong> koneksi.php tidak ditemukan. Pastikan file ada di folder project.");
}

if (!isset($koneksi) || $koneksi === false) {
    die("<strong>Error:</strong> Koneksi database tidak tersedia.");
}

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header("Location: admin/dashboard.php");
    elseif ($role === 'pemilik') header("Location: pemilik/dashboard.php");
    else header("Location: penghuni/dashboard.php");
    exit;
}

$error = '';
$timeout_message = '';

// tampilkan pesan jika sesi berakhir
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $timeout_message = "Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit. Silakan login kembali.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password harus diisi.";
    } else {
        $sql = "SELECT id, username, password, role, status FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($user) {
                if ($user['password'] === $password) {
                    if (isset($user['status']) && $user['status'] !== 'aktif') {
                        $error = "Akun Anda tidak aktif. Hubungi admin.";
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();

                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } elseif ($user['role'] === 'pemilik') {
                            header("Location: pemilik/dashboard.php");
                        } else {
                            header("Location: penghuni/dashboard.php");
                        }
                        exit;
                    }
                } else {
                    $error = "Username atau password salah.";
                }
            } else {
                $error = "Username atau password salah.";
            }
        } else {
            $error = "Kesalahan server: gagal menyiapkan query.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "Poppins", sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #333;
    }
    .login-container {
        background: white;
        width: 100%;
        max-width: 420px;
        padding: 40px 35px;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        animation: fadeIn 0.8s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .login-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .login-header i {
        font-size: 40px;
        color: #667eea;
        margin-bottom: 10px;
    }
    .login-header h2 {
        font-weight: 700;
        color: #1e1b4b;
        margin: 0;
    }
    .login-header p {
        font-size: 13px;
        color: #64748b;
        margin-top: 5px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        font-weight: 500;
        font-size: 14px;
        color: #1e1b4b;
        display: block;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 15px;
    }
    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border-left: 4px solid #ef4444;
    }
    .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border-left: 4px solid #f59e0b;
    }
    .footer-text {
        text-align: center;
        margin-top: 20px;
        color: #94a3b8;
        font-size: 12px;
    }
</style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <i class="fa-solid fa-building"></i>
        <h2>Login SIKOPAT</h2>
        <p>Sistem Kost Pintar</p>
    </div>

    <?php if (!empty($timeout_message)): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($timeout_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
        </div>
        <button type="submit" name="login" class="btn"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
    </form>

    <div class="footer-text">© <?= date('Y') ?> SIKOPAT — Sistem Manajemen Kos</div>
</div>
</body>
</html>
