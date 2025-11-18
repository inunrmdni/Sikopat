<?php
include '../koneksi.php';
include '../cek_login.php';

$id = intval($_GET['id'] ?? 0);
$query = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE id=$id LIMIT 1");

if (!$query || mysqli_num_rows($query) == 0) {
    die("<h4>Notifikasi tidak ditemukan.</h4>");
}

$data = mysqli_fetch_assoc($query);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Detail Notifikasi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="notifikasi.php" class="btn btn-secondary mb-3">⬅ Kembali</a>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Detail Notifikasi</h5>
            <p><?= htmlspecialchars($data['pesan']) ?></p>
            <small class="text-muted">Tanggal: <?= date('d M Y H:i', strtotime($data['tanggal'])) ?></small>
        </div>
    </div>
</div>
</body>
</html>
