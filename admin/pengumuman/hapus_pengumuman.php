<?php
include '../../koneksi.php';
include '../../cek_login.php';

// Pastikan parameter id ada
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $username = $_SESSION['username'];

    // Ambil data pengumuman untuk dicatat log
    $query_info = mysqli_query($koneksi, "SELECT * FROM pengumuman WHERE id = '$id'");
    if ($query_info && mysqli_num_rows($query_info) > 0) {
        $data = mysqli_fetch_assoc($query_info);
        $isi_ringkas = substr($data['isi'], 0, 100);
        $isi_ringkas = mysqli_real_escape_string($koneksi, $isi_ringkas);

        // Hapus data
        $hapus = mysqli_query($koneksi, "DELETE FROM pengumuman WHERE id = '$id'");

        if ($hapus) {
            // Simpan log aktivitas
            $aksi = "Menghapus pengumuman ID $id (isi: $isi_ringkas...)";
            $aksi = mysqli_real_escape_string($koneksi, $aksi);

            $insert_log = mysqli_query($koneksi, "
                INSERT INTO log_aktivitas (username, aksi, waktu)
                VALUES ('$username', '$aksi', NOW())
            ");

            if ($insert_log) {
                header("Location: ../pengumuman.php?hapus=berhasil");
                exit;
            } else {
                echo "Gagal mencatat log aktivitas: " . mysqli_error($koneksi);
            }
        } else {
            echo "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    } else {
        echo "Data tidak ditemukan di tabel pengumuman.";
    }
} else {
    echo "ID tidak valid atau tidak dikirim.";
}
?>
