<?php
include '../koneksi.php';
include '../cek_login.php';

$result = mysqli_query($koneksi, "SELECT * FROM log_aktivitas ORDER BY waktu ASC");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Log Aktivitas</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>🧾 Log Aktivitas</h3>
    <table class="table table-bordered table-striped align-middle mt-3">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Aksi</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($log = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($log['username']) ?></td>
                <td><?= htmlspecialchars($log['aksi']) ?></td>
                <td><?= $log['waktu'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
