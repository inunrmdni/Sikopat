<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

if(!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$notif_id = intval($_GET['id']);

// Update status notifikasi menjadi sudah dibaca
$query = "UPDATE notifikasi SET status = 'dibaca' WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $notif_id);

if(mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
?>