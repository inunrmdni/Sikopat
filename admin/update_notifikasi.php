<?php
include '../koneksi.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($koneksi, "UPDATE notifikasi SET status='dibaca' WHERE id=$id");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
