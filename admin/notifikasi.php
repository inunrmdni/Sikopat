<?php
include '../koneksi.php';
include '../cek_login.php';

// Jenis notifikasi (default: pengaduan)
$jenis = isset($_GET['jenis']) ? mysqli_real_escape_string($koneksi, $_GET['jenis']) : 'pengaduan';

// Ambil semua notifikasi
$query = mysqli_query($koneksi, "
    SELECT * FROM notifikasi 
    WHERE jenis='$jenis'
    ORDER BY tanggal DESC
");

// Setelah halaman dibuka, tandai semua notifikasi sebagai dibaca
mysqli_query($koneksi, "UPDATE notifikasi SET status='dibaca' WHERE jenis='$jenis' AND status='baru'");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Notifikasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
body { background: #f8f9fa; }
.list-group-item:hover {
    background-color: #eef3ff;
    transition: 0.2s;
}
.card {
    border-radius: 10px;
}
</style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>📬 Daftar Notifikasi (<?= ucfirst(htmlspecialchars($jenis)) ?>)</h3>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">⬅ Kembali</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (mysqli_num_rows($query) > 0): ?>
                <div class="list-group">
                    <?php while ($n = mysqli_fetch_assoc($query)): ?>
                        <a href="notifikasi.php $n['id'] ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?= $n['status']=='baru' ? 'fw-bold bg-warning-subtle' : '' ?>">
                            <div>
                                <div><?= htmlspecialchars($n['pesan']) ?></div>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($n['tanggal'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $n['status']=='baru' ? 'warning' : 'secondary' ?> rounded-pill text-dark">
                                <?= ucfirst($n['status']) ?>
                            </span>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted m-0">Belum ada notifikasi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
