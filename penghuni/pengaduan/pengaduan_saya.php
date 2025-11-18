<?php
include '../../koneksi.php';
include '../../cek_login.php';

// Pastikan user login sebagai penghuni
$user_id = $_SESSION['user_id'];

// Ambil semua pengaduan milik user ini
$query = mysqli_query($koneksi, "
    SELECT id, isi, anonim, status, created_at 
    FROM pengaduan 
    WHERE user_id = '$user_id' 
    ORDER BY created_at DESC
");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Daftar Pengaduan Saya</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
.badge {
    text-transform: capitalize;
}
</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-4">Daftar Pengaduan Saya</h3>

    <a href="../pengaduan.php" class="btn btn-primary mb-3">+ Kirim Pengaduan Baru</a>

    <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
            <tr>
                <th width="5%">No</th>
                <th>Isi Pengaduan</th>
                <th width="15%">Anonim</th>
                <th width="15%">Status</th>
                <th width="20%">Tanggal</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if (mysqli_num_rows($query) > 0):
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)): 
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= nl2br(htmlspecialchars($row['isi'])) ?></td>
                <td><?= $row['anonim'] ? 'Ya' : 'Tidak' ?></td>
                <td>
                    <?php if ($row['status'] == 'baru'): ?>
                        <span class="badge bg-secondary">Baru</span>
                    <?php elseif ($row['status'] == 'ditanggapi'): ?>
                        <span class="badge bg-warning text-dark">Ditanggapi</span>
                    <?php else: ?>
                        <span class="badge bg-success">Selesai</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
            </tr>
        <?php 
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="5" class="text-center text-muted">Belum ada pengaduan</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="../dashboard.php" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
    </a>
</div>
</body>
</html>
