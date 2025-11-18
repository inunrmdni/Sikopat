<?php
// koneksi.php
$host = "localhost";
$user = "root";
$pass = "";      // sesuaikan kalau Anda pakai password lain
$db   = "sikopat2";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
// set charset
mysqli_set_charset($koneksi, "utf8mb4");
?>
