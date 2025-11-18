<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// --- UBAH STATUS PENGADUAN ---
if (isset($_POST['ubah_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Update status pengaduan
    $update = mysqli_query($koneksi, "UPDATE pengaduan SET status='$status' WHERE id='$id'");

    if ($update) {
        // Ambil data pengaduan
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE id='$id'"));
        $pesan = "Status pengaduan Anda telah diubah menjadi <b>$status</b>.";

        // Tambahkan notifikasi
        mysqli_query($koneksi, "
            INSERT INTO notifikasi (id_pengaduan, pesan, jenis, status)
            VALUES ('$id', '$pesan', 'pengaduan', 'baru')
        ");


        echo "<script>
            alert('Status berhasil diubah dan notifikasi dikirim!'); 
            window.location='../admin/pengaduan.php';
        </script>";
    } else {
        echo "<script>alert('Gagal mengubah status!');</script>";
    }
}

// --- AMBIL DATA PENGADUAN ---
$query = mysqli_query($koneksi, "
    SELECT * FROM pengaduan
    ORDER BY created_at DESC
");

// Hitung statistik
$total_pengaduan = mysqli_num_rows($query);
$baru = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE status='baru'"));
$diproses = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE status='diproses'"));
$selesai = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE status='selesai'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Pengaduan - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
    .sidebar { width: 260px; background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%); color: white; position: fixed; top: 0; left: 0; bottom: 0; padding: 0; box-shadow: 4px 0 20px rgba(0,0,0,0.1); overflow-y: auto; z-index: 1000; }
    .sidebar-header { padding: 25px 20px; background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar-header h3 { font-size: 24px; font-weight: 700; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .sidebar-header p { font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 5px; }
    .sidebar ul { list-style: none; padding: 20px 15px; }
    .sidebar ul li { margin-bottom: 5px; }
    .sidebar ul li a { display: flex; align-items: center; padding: 12px 15px; border-radius: 10px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s ease; font-size: 14px; font-weight: 500; }
    .sidebar ul li a i { margin-right: 12px; width: 20px; text-align: center; font-size: 16px; }
    .sidebar ul li a:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
    .sidebar ul li a.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
    .logout-btn { margin-top: 20px; padding: 12px 15px; background: rgba(239, 68, 68, 0.1); color: #fca5a5; border-radius: 10px; display: flex; align-items: center; text-decoration: none; transition: all 0.3s ease; }
    .logout-btn:hover { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .content { margin-left: 260px; padding: 30px; min-height: 100vh; }
    .top-bar { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .top-bar h1 { font-size: 28px; font-weight: 700; color: #1e1b4b; margin-bottom: 5px; }
    .top-bar p { color: #64748b; font-size: 14px; margin: 0; }
    .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 15px; }
    .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
    .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .stat-icon.gray { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
    .stat-info h3 { font-size: 28px; font-weight: 700; color: #1e1b4b; margin: 0; }
    .stat-info p { font-size: 13px; color: #64748b; margin: 0; }
    .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow-x: auto; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .table-header h3 { font-size: 18px; font-weight: 600; color: #1e1b4b; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    thead th { padding: 15px; text-align: left; color: white; font-weight: 600; font-size: 14px; border: none; }
    tbody tr { border-bottom: 1px solid #f1f5f9; transition: all 0.3s ease; }
    tbody tr:hover { background: #f8fafc; }
    tbody td { padding: 15px; font-size: 14px; color: #475569; }
    .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .badge-baru { background: #fee2e2; color: #dc2626; }
    .badge-diproses { background: #fef3c7; color: #d97706; }
    .badge-selesai { background: #d1fae5; color: #059669; }
    .form-select { padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; transition: all 0.3s ease; }
    .form-select:focus { border-color: #667eea; }
    .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
    .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
    .btn-secondary { background: #64748b; color: white; }
    .btn-secondary:hover { background: #475569; }
    .action-buttons { display: flex; gap: 8px; align-items: center; }
    .empty-state { text-align: center; padding: 40px; color: #94a3b8; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
    @media (max-width: 768px) { .sidebar { width: 100%; position: relative; height: auto; } .content { margin-left: 0; } .stats-cards { grid-template-columns: 1fr; } .table-container { overflow-x: scroll; } }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-building"></i> SIKOPAT</h3>
        <p>Sistem Kost Pintar</p>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="manajemenAkun.php"><i class="fa-solid fa-user-gear"></i> Manajemen Akun</a></li>
        <li><a href="penghuni.php"><i class="fa-solid fa-users"></i> Penghuni</a></li>
        <li><a href="kamar.php"><i class="fa-solid fa-bed"></i> Kamar</a></li>
        <li><a href="pengumuman.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
        <li><a href="chat.php"><i class="fa-solid fa-comments"></i> Chat</a></li>
        <li><a href="pengaduan.php" class="active"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
        <li><a href="laporan.php"><i class="fa-solid fa-chart-line"></i> Laporan</a></li>
        <li>
            <a href="../logout.php" onclick="return confirm('Yakin ingin logout?')" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <h1><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan & Ulasan</h1>
        <p>Kelola pengaduan dan ulasan dari penghuni kos</p>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon gray"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="stat-info"><h3><?= $total_pengaduan ?></h3><p>Total Pengaduan</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="stat-info"><h3><?= $baru ?></h3><p>Pengaduan Baru</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-spinner"></i></div>
            <div class="stat-info"><h3><?= $diproses ?></h3><p>Sedang Diproses</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info"><h3><?= $selesai ?></h3><p>Selesai</p></div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3><i class="fa-solid fa-list"></i> Daftar Pengaduan</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Isi Pengaduan</th>
                    <th>No Kamar</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            mysqli_data_seek($query, 0);
            if (mysqli_num_rows($query) > 0):
                while ($row = mysqli_fetch_assoc($query)):
            ?>
            <tr>
                <td><strong><?= $no++ ?></strong></td>
                <td><div style="max-width: 400px;"><?= nl2br(htmlspecialchars($row['isi'])) ?></div></td>
                <td>
                    <?php if($row['anonim']): ?>
                        <span class="badge" style="background: #e0e7ff; color: #4f46e5;">
                            <i class="fa-solid fa-user-secret"></i> Anonim
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #f1f5f9; color: #64748b;">
                            <i class="fa-solid fa-user"></i> <?= htmlspecialchars($row['user_id'] ?? 'Tidak diketahui') ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" class="action-buttons">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <select name="status" class="form-select">
                            <option value="baru" <?= $row['status'] == 'baru' ? 'selected' : '' ?>>Baru</option>
                            <option value="diproses" <?= $row['status'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="selesai" <?= $row['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                        <button type="submit" name="ubah_status" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                </td>
                <td><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                    <button class="btn btn-danger" onclick="hapusPengaduan(<?= $row['id'] ?>)">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Belum ada pengaduan yang masuk</p>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function hapusPengaduan(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data ini tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../admin/hapus_pengaduan.php?id=' + id;
        }
    });
}
</script>

</body>
</html>