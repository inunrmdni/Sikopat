<?php
include '../../koneksi.php';
include '../../cek_login.php';

if (isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);

    // Ambil data pengaduan & user_id
    $q = mysqli_query($koneksi, "SELECT user_id, isi FROM pengaduan WHERE id=$id");
    $data = mysqli_fetch_assoc($q);

    if ($data) {
        $user_id = $data['user_id'];
        $isi = substr($data['isi'], 0, 50); // potong isi pengaduan agar pendek

        // Update status
        $update = mysqli_query($koneksi, "UPDATE pengaduan SET status='$status' WHERE id=$id");

        if ($update && $user_id) {
            // Buat pesan notifikasi otomatis
            $judul = "Status Pengaduan Diperbarui";
            $pesan = "Status pengaduan Anda: \"$isi...\" telah berubah menjadi <b>$status</b>.";

            // Simpan notifikasi ke tabel
            mysqli_query($koneksi, "
                INSERT INTO notifikasi (user_id, judul, pesan)
                VALUES ('$user_id', '$judul', '$pesan')
            ");
        }

        header("Location: ../admin/pengaduan.php?status_update=success");
        exit;
    } else {
        echo "Data pengaduan tidak ditemukan!";
    }
} else {
    header("Location: ../admin/pengaduan.php");
    exit;
}
?>
