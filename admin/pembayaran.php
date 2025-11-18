<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Cek login dan role
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Verifikasi Pembayaran
if (isset($_POST['verifikasi'])) {
    $id = $_POST['id_pembayaran'];
    $status = $_POST['status_verifikasi'];
    
    $update = mysqli_query($koneksi, "UPDATE pembayaran SET status='$status' WHERE id='$id'");
    
    if ($update) {
        // Ambil data pembayaran
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE id='$id'"));
        
        // Kirim notifikasi ke penghuni
        if ($status == 'lunas') {
            $pesan = "Pembayaran Anda sebesar Rp " . number_format($data['total_bayar'], 0, ',', '.') . " telah diverifikasi dan diterima.";
        } else {
            $pesan = "Pembayaran Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.";
        }
        
        mysqli_query($koneksi, "
            INSERT INTO notifikasi (id_pengumuman, pesan, jenis, status, tanggal)
            VALUES ('{$data['id_penghuni']}', '$pesan', 'pembayaran', 'baru', NOW())
        ");
        
        echo "<script>alert('Status pembayaran berhasil diubah!'); window.location='pembayaran.php';</script>";
    }
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where = "";
if ($filter == 'lunas') {
    $where = "WHERE p.status='lunas'";
} elseif ($filter == 'belum_lunas') {
    $where = "WHERE p.status='belum_lunas'";
} elseif ($filter == 'pending') {
    $where = "WHERE p.status='pending'";
}

// Ambil data pembayaran
$query = mysqli_query($koneksi, "
    SELECT 
        p.*, 
        ph.nama_penghuni, 
        k.nomor_kamar
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    LEFT JOIN penghuni_kamar ph ON t.penghuni_id = ph.id
    LEFT JOIN kamar k ON t.kamar_id = k.id
    $where
    ORDER BY p.created_at DESC
");


// Statistik
$total_pemasukan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pembayaran WHERE status='lunas'"))['total'] ?? 0;
$pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE status='pending'"));
$belum_lunas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pembayaran WHERE status='belum_lunas'"))['total'] ?? 0;
$bulan_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM pembayaran WHERE status='lunas' AND MONTH(created_at)=MONTH(NOW())"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
        font-family: "Poppins", sans-serif; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: #333; 
    }
    
    .sidebar { 
        width: 260px; 
        background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
        color: white; 
        position: fixed; 
        top: 0; 
        left: 0; 
        bottom: 0; 
        padding: 0;
        box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        overflow-y: auto;
        z-index: 1000;
    }
    
    .sidebar-header {
        padding: 25px 20px;
        background: rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-header h3 { 
        font-size: 24px; 
        font-weight: 700; 
        margin: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .sidebar-header p {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        margin-top: 5px;
    }
    
    .sidebar ul { 
        list-style: none; 
        padding: 20px 15px;
    }
    
    .sidebar ul li { margin-bottom: 5px; }
    
    .sidebar ul li a { 
        display: flex;
        align-items: center;
        padding: 12px 15px; 
        border-radius: 10px; 
        color: rgba(255,255,255,0.8); 
        text-decoration: none; 
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    }
    
    .sidebar ul li a i {
        margin-right: 12px;
        width: 20px;
        text-align: center;
        font-size: 16px;
    }
    
    .sidebar ul li a:hover { 
        background: rgba(255,255,255,0.1); 
        color: white;
        transform: translateX(5px);
    }
    
    .sidebar ul li a.active { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .logout-btn {
        margin-top: 20px;
        padding: 12px 15px;
        background: rgba(239, 68, 68, 0.1);
        color: #fca5a5;
        border-radius: 10px;
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    
    .content { 
        margin-left: 260px; 
        padding: 30px;
        min-height: 100vh;
    }
    
    .top-bar {
        background: white;
        padding: 20px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .top-bar h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1e1b4b;
        margin-bottom: 5px;
    }
    
    .top-bar p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    
    .stat-info h3 {
        font-size: 24px;
        font-weight: 700;
        color: #1e1b4b;
        margin: 0;
    }
    
    .stat-info p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    .filter-tabs {
        background: white;
        padding: 15px 25px;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: flex;
        gap: 10px;
    }
    
    .filter-tabs a {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .filter-tabs a:hover {
        background: #f8fafc;
    }
    
    .filter-tabs a.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    thead th {
        padding: 15px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 14px;
        border: none;
    }
    
    thead th:first-child { border-radius: 10px 0 0 0; }
    thead th:last-child { border-radius: 0 10px 0 0; }
    
    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }
    
    tbody tr:hover { background: #f8fafc; }
    
    tbody td {
        padding: 15px;
        font-size: 14px;
        color: #475569;
    }
    
    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-lunas { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-belum { background: #fee2e2; color: #dc2626; }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.show { display: flex; }
    
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        margin-bottom: 20px;
    }
    
    .modal-header h3 {
        font-size: 20px;
        color: #1e1b4b;
        margin: 0;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; }
        .stats-cards { grid-template-columns: 1fr; }
        .filter-tabs { flex-wrap: wrap; }
    }
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
        <li><a href="pembayaran.php" class="active"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
        <li><a href="chat.php"><i class="fa-solid fa-comments"></i> Chat</a></li>
        <li><a href="pengaduan.php"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
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
        <h1><i class="fa-solid fa-money-bill-wave"></i> Manajemen Pembayaran</h1>
        <p>Kelola pembayaran sewa kos dan monitoring keuangan</p>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-info">
                <h3>Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></h3>
                <p>Total Pemasukan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3>Rp <?= number_format($bulan_ini, 0, ',', '.') ?></h3>
                <p>Pemasukan Bulan Ini</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending ?></h3>
                <p>Pembayaran Pending</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-exclamation-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Rp <?= number_format($belum_lunas, 0, ',', '.') ?></h3>
                <p>Belum Lunas</p>
            </div>
        </div>
    </div>

    <div class="filter-tabs">
        <a href="?filter=semua" class="<?= $filter == 'semua' ? 'active' : '' ?>">
            <i class="fa-solid fa-list"></i> Semua
        </a>
        <a href="?filter=pending" class="<?= $filter == 'pending' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock"></i> Pending
        </a>
        <a href="?filter=lunas" class="<?= $filter == 'lunas' ? 'active' : '' ?>">
            <i class="fa-solid fa-check-circle"></i> Lunas
        </a>
        <a href="?filter=belum_lunas" class="<?= $filter == 'belum_lunas' ? 'active' : '' ?>">
            <i class="fa-solid fa-times-circle"></i> Belum Lunas
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Penghuni</th>
                    <th>Kamar</th>
                    <th>Periode</th>
                    <th>Total Bayar</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(mysqli_num_rows($query) > 0):
                    while($row = mysqli_fetch_assoc($query)):
                ?>
                <tr>
                    <td><strong><?= $no++ ?></strong></td>
                    <td><?= $row['nama_lengkap'] ?></td>
                    <td><span class="badge" style="background: #e0e7ff; color: #4f46e5;">Kamar <?= $row['nomor_kamar'] ?></span></td>
                    <td><?= date('F Y', strtotime($row['periode'])) ?></td>
                    <td><strong>Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></strong></td>
                    <td>
                        <?php if($row['status'] == 'lunas'): ?>
                            <span class="badge badge-lunas"><i class="fa-solid fa-check-circle"></i> Lunas</span>
                        <?php elseif($row['status'] == 'pending'): ?>
                            <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                        <?php else: ?>
                            <span class="badge badge-belum"><i class="fa-solid fa-times-circle"></i> Belum Lunas</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                        <button class="btn btn-success" onclick="verifikasi(<?= $row['id'] ?>, 'lunas')">
                            <i class="fa-solid fa-check"></i> Terima
                        </button>
                        <button class="btn btn-danger" onclick="verifikasi(<?= $row['id'] ?>, 'belum_lunas')">
                            <i class="fa-solid fa-times"></i> Tolak
                        </button>
                        <?php else: ?>
                        <button class="btn btn-info" onclick="lihatDetail(<?= $row['id'] ?>)">
                            <i class="fa-solid fa-eye"></i> Detail
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                        Belum ada data pembayaran
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Verifikasi -->
<div class="modal" id="modalVerifikasi">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-check-circle"></i> Verifikasi Pembayaran</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="id_pembayaran" id="id_pembayaran">
            <input type="hidden" name="status_verifikasi" id="status_verifikasi">
            <p id="pesan_verifikasi"></p>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="verifikasi" class="btn btn-success" style="flex: 1;">
                    <i class="fa-solid fa-check"></i> Ya, Verifikasi
                </button>
                <button type="button" class="btn btn-danger" onclick="closeModal()" style="flex: 1;">
                    <i class="fa-solid fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function verifikasi(id, status) {
    document.getElementById('id_pembayaran').value = id;
    document.getElementById('status_verifikasi').value = status;
    
    if(status == 'lunas') {
        document.getElementById('pesan_verifikasi').innerHTML = 'Apakah Anda yakin ingin <strong style="color: #10b981;">menerima</strong> pembayaran ini?';
    } else {
        document.getElementById('pesan_verifikasi').innerHTML = 'Apakah Anda yakin ingin <strong style="color: #ef4444;">menolak</strong> pembayaran ini?';
    }
    
    document.getElementById('modalVerifikasi').classList.add('show');
}

function closeModal() {
    document.getElementById('modalVerifikasi').classList.remove('show');
}

function lihatDetail(id) {
    window.location.href = 'detail_pembayaran.php?id=' + id;
}
</script>
</body>
</html>