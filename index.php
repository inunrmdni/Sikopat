<?php
// index.php
session_start();

// Jika sudah login, langsung ke dashboard sesuai role
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($role === 'pemilik') {
        header("Location: pemilik/dashboard.php");
        exit;
    } elseif ($role === 'penghuni') {
        header("Location: penghuni/dashboard.php");
        exit;
    }
}

// Jika belum login -> ke login.php (tidak memanggil index lagi)
header("Location: login.php");
exit;
?>
