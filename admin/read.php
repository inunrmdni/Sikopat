<?php
session_start();
include '../koneksi.php';

// Cek login dan role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if(!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$notif_id = intval($_GET['id']);

// Ambil detail notifikasi untuk mendapatkan ID pengaduan
$query = "SELECT * FROM notifikasi WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $notif_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notif = mysqli_fetch_assoc($result);

if($notif) {
    // Update status notifikasi menjadi dibaca
    $update_query = "UPDATE notifikasi SET status = 'dibaca' WHERE id = ?";
    $update_stmt = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $notif_id);
    mysqli_stmt_execute($update_stmt);
    
    // Cek apakah notifikasi ini terkait dengan pengaduan
    if(isset($notif['pengaduan_id']) && $notif['pengaduan_id'] > 0) {
        // Redirect ke halaman pengaduan dengan detail
        header("Location: pengaduan.php?id=" . $notif['pengaduan_id']);
        exit;
    } else {
        // Jika tidak ada pengaduan_id, redirect ke halaman pengaduan
        header("Location: pengaduan.php");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}

mysqli_close($koneksi);
?>