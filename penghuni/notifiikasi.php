<?php
include '../koneksi.php';
include '../cek_login.php';

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'pengaduan';

$query = mysqli_query($koneksi, "
    SELECT * FROM notifikasi 
    WHERE jenis='$jenis' 
    ORDER BY tanggal DESC
");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Notifikasi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>Daftar Notifikasi (<?= ucfirst($jenis) ?>)</h3>
    <div class="list-group mt-3">
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($query)): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center <?= $n['status']=='baru' ? 'bg-warning-subtle' : '' ?>">
                    <div>
                        <?= $n['pesan'] ?><br>
                        <small class="text-muted"><?= $n['tanggal'] ?></small>
                    </div>
                    <?php if ($n['status']=='baru'): ?>
                        <form method="POST" action="baca_notif.php" class="m-0">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success">Tandai dibaca</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">Belum ada notifikasi.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
