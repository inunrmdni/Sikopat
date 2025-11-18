<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Pastikan hanya admin yang bisa hapus
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil ID dari URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Hapus pengaduan dari database
    $hapus = mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id='$id'");

    if ($hapus) {
        // Opsional: hapus juga notifikasi yang berkaitan
        mysqli_query($koneksi, "DELETE FROM notifikasi WHERE id_pengaduan='$id'");

        echo "<script>
            alert('Pengaduan berhasil dihapus!');
            window.location='pengaduan.php';
        </script>";
    } else {
        echo "<script>
            alert('Gagal menghapus pengaduan!');
            window.location='pengaduan.php';
        </script>";
    }
} else {
    echo "<script>
        alert('ID tidak ditemukan!');
        window.location='pengaduan.php';
    </script>";
}
?>
