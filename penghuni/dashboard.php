<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Cek login dan role
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'penghuni') {
    header("Location: ../login.php");
    exit;
}

$id_penghuni = $_SESSION['id_penghuni'] ?? $_SESSION['user_id'];

// ==========================
// AMBIL DATA DARI DATABASE
// ==========================

// Data penghuni
$q_penghuni = mysqli_query($koneksi, "
    SELECT pk.*, k.nomor_kamar, k.harga
    FROM penghuni_kamar pk 
    LEFT JOIN kamar k ON pk.id = k.id 
    WHERE pk.id = '$id_penghuni'
");
$data_penghuni = mysqli_fetch_assoc($q_penghuni);

// Tagihan pending
$q_tagihan = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    AND t.status = 'belum_bayar'
");

$total_tagihan = mysqli_fetch_assoc($q_tagihan)['total'] ?? 0;

// Total tunggakan
$q_tunggakan = mysqli_query($koneksi, "
    SELECT SUM(t.jumlah) AS total
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    AND t.status = 'belum_bayar'
");

$total_tunggakan = mysqli_fetch_assoc($q_tunggakan)['total'] ?? 0;

// Pengaduan
$q_pengaduan = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM pengaduan 
    WHERE user_id = '$id_penghuni'
");

$total_pengaduan = mysqli_fetch_assoc($q_pengaduan)['total'] ?? 0;

// Notifikasi
$notif_query = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM notifikasi 
    WHERE id_pengumuman='$id_penghuni' AND status='baru'
");
$total_notif = mysqli_fetch_assoc($notif_query)['total'] ?? 0;

// List notifikasi
$list_query = mysqli_query($koneksi, "
    SELECT * FROM notifikasi 
    WHERE id_pengumuman='$id_penghuni' 
    ORDER BY tanggal DESC LIMIT 5
");

// Pengumuman terbaru
$pengumuman_query = mysqli_query($koneksi, "
    SELECT * FROM pengumuman 
    ORDER BY created_at DESC 
    LIMIT 3
");

// Riwayat pembayaran terakhir
$pembayaran_query = mysqli_query($koneksi, "
    SELECT p.* 
    FROM pembayaran p
    JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    ORDER BY p.created_at DESC 
    LIMIT 3
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Penghuni - SIKOPAT</title>
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
    
    .user-profile {
        padding: 20px;
        background: rgba(255,255,255,0.05);
        margin: 15px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar-big {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .user-profile-info h4 {
        font-size: 16px;
        margin: 0;
        color: white;
    }
    
    .user-profile-info p {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        margin: 0;
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .welcome-text h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1e1b4b;
        margin-bottom: 5px;
    }
    
    .welcome-text p {
        color: #64748b;
        font-size: 14px;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .notif-icon {
        position: relative;
        width: 45px;
        height: 45px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .notif-icon:hover {
        background: #e2e8f0;
    }
    
    .notif-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    .user-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .user-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* Notifikasi Dropdown */
    .notif-dropdown {
        position: absolute;
        top: 60px;
        right: 60px;
        width: 350px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        display: none;
        z-index: 1000;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notif-dropdown.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .notif-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notif-header h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1e1b4b;
        margin: 0;
    }
    
    .notif-item {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .notif-item:hover {
        background: #f8fafc;
    }
    
    .notif-item:last-child {
        border-bottom: none;
    }
    
    .notif-item p {
        font-size: 13px;
        color: #475569;
        margin: 0 0 5px 0;
    }
    
    .notif-item small {
        font-size: 11px;
        color: #94a3b8;
    }
    
    .notif-empty {
        padding: 30px;
        text-align: center;
        color: #94a3b8;
    }
    
    .notif-empty i {
        font-size: 36px;
        margin-bottom: 10px;
        opacity: 0.5;
    }
    
    .cards { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
        gap: 20px; 
        margin-bottom: 30px;
    }
    
    .card { 
        background: white; 
        border-radius: 15px; 
        padding: 25px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .card-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .card-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .card-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
    .card-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    
    .card h4 { 
        font-size: 14px; 
        color: #64748b; 
        margin: 0 0 8px 0;
        font-weight: 500;
    }
    
    .card h2 { 
        font-size: 32px; 
        margin: 0 0 8px 0; 
        color: #1e1b4b;
        font-weight: 700;
    }
    
    .card p {
        font-size: 13px;
        margin: 0;
    }
    
    .green { 
        color: #10b981; 
        font-weight: 500;
    }
    
    .red { 
        color: #ef4444; 
        font-weight: 500;
    }
    
    .section-container { 
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px; 
        margin-bottom: 30px;
    }
    
    .section-card { 
        background: white; 
        padding: 25px; 
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .section-card h3 {
        font-size: 18px;
        color: #1e1b4b;
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pengumuman-item {
        padding: 15px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 12px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .pengumuman-item:hover {
        background: #f1f5f9;
        transform: translateX(5px);
    }
    
    .pengumuman-item h5 {
        font-size: 14px;
        color: #1e1b4b;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .pengumuman-item p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    .pembayaran-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .pembayaran-item:last-child {
        border-bottom: none;
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
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; }
        .section-container { grid-template-columns: 1fr; }
        .cards { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-building"></i> SIKOPAT</h3>
        <p>Sistem Kost Pintar</p>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar-big">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="user-profile-info">
            <h4><?= htmlspecialchars($data_penghuni['nama_lengkap'] ?? $_SESSION['username']) ?></h4>
            <p>Kamar <?= $data_penghuni['nomor_kamar'] ?? '-' ?></p>
        </div>
    </div>
    
    <ul>
        <li><a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="pengumuman.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="tagihan.php"><i class="fa-solid fa-file-invoice"></i> Tagihan Saya</a></li>
        <li><a href="pembayaran.php"><i class="fa-solid fa-wallet"></i> Pembayaran</a></li>
        <li><a href="chat.php"><i class="fa-solid fa-comments"></i> Chat Admin</a></li>
        <li><a href="pengaduan_penghuni.php"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
        <li><a href="profil_penghuni.php"><i class="fa-solid fa-user-circle"></i> Profil Saya</a></li>
        <li>
            <a href="../logout.php" onclick="return confirm('Yakin ingin logout?')" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <div class="welcome-text">
            <h1>Dashboard Penghuni</h1>
            <p>Selamat datang kembali, <?= htmlspecialchars($data_penghuni['nama_lengkap'] ?? $_SESSION['username']) ?>!</p>
        </div>
        <div class="user-info">
            <div class="notif-icon" onclick="toggleNotif()">
                <i class="fa-solid fa-bell"></i>
                <?php if($total_notif > 0): ?>
                <span class="notif-badge"><?= $total_notif ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Dropdown Notifikasi -->
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <h4><i class="fa-solid fa-bell"></i> Notifikasi</h4>
                </div>
                <div class="notif-body">
                    <?php if($total_notif > 0): ?>
                        <?php while($notif = mysqli_fetch_assoc($list_query)): ?>
                            <div class="notif-item">
                                <p><?= $notif['pesan'] ?></p>
                                <small><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($notif['tanggal'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="notif-empty">
                            <i class="fa-solid fa-bell-slash"></i>
                            <p>Tidak ada notifikasi baru</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="profil_penghuni.php" style="text-decoration: none;">
                <div class="user-avatar" title="Lihat Profil">
                    <i class="fa-solid fa-user"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-icon blue">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <h4>Kamar Saya</h4>
            <h2>No. <?= $data_penghuni['nomor_kamar'] ?? '-' ?></h2>
            <p style="color: #64748b;">Rp <?= number_format($data_penghuni['harga_sewa'] ?? 0, 0, ',', '.') ?>/bulan</p>
        </div>
        <div class="card">
            <div class="card-icon orange">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <h4>Tagihan Pending</h4>
            <h2><?= $total_tagihan ?></h2>
            <?php if($total_tagihan > 0): ?>
            <p class="red"><i class="fa-solid fa-clock"></i> Segera bayar</p>
            <?php else: ?>
            <p class="green"><i class="fa-solid fa-check-circle"></i> Tidak ada tagihan</p>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-icon red">
                <i class="fa-solid fa-exclamation-circle"></i>
            </div>
            <h4>Total Tunggakan</h4>
            <h2 style="font-size: 24px;">Rp <?= number_format($total_tunggakan, 0, ',', '.') ?></h2>
            <?php if($total_tunggakan > 0): ?>
            <p class="red"><i class="fa-solid fa-warning"></i> Harap dibayarkan</p>
            <?php else: ?>
            <p class="green"><i class="fa-solid fa-check"></i> Tidak ada tunggakan</p>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-icon green">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h4>Pengaduan</h4>
            <h2><?= $total_pengaduan ?></h2>
            <p style="color: #64748b;">Total pengaduan dibuat</p>
        </div>
    </div>

    <div class="section-container">
        <div class="section-card">
            <h3><i class="fa-solid fa-bullhorn"></i> Pengumuman Terbaru</h3>
            <?php if(mysqli_num_rows($pengumuman_query) > 0): ?>
                <?php while($pengumuman = mysqli_fetch_assoc($pengumuman_query)): ?>
                <div class="pengumuman-item">
                    <h5><?= htmlspecialchars($pengumuman['judul']) ?></h5>
                    <p><?= substr(strip_tags($pengumuman['isi']), 0, 100) ?>...</p>
                    <small style="color: #94a3b8;">
                        <i class="fa-regular fa-calendar"></i> 
                        <?= date('d F Y', strtotime($pengumuman['created_at'])) ?>
                    </small>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #94a3b8; padding: 20px;">
                    Belum ada pengumuman
                </p>
            <?php endif; ?>
        </div>
        
        <div class="section-card">
            <h3><i class="fa-solid fa-history"></i> Riwayat Pembayaran</h3>
            <?php if(mysqli_num_rows($pembayaran_query) > 0): ?>
                <?php while($bayar = mysqli_fetch_assoc($pembayaran_query)): ?>
                <div class="pembayaran-item">
                    <div>
                        <p style="font-size: 13px; font-weight: 600; color: #1e1b4b; margin: 0;">
                            <?= date('M Y', strtotime($bayar['periode'])) ?>
                        </p>
                        <small style="color: #64748b;">
                            Rp <?= number_format($bayar['total_bayar'], 0, ',', '.') ?>
                        </small>
                    </div>
                    <div>
                        <?php if($bayar['status'] == 'lunas'): ?>
                            <span class="badge badge-lunas">Lunas</span>
                        <?php elseif($bayar['status'] == 'pending'): ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php else: ?>
                            <span class="badge badge-belum">Belum Lunas</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
                <a href="pembayaran_penghuni.php" style="display: block; text-align: center; margin-top: 15px; color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">
                    Lihat Semua <i class="fa-solid fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <p style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">
                    Belum ada riwayat
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Toggle Notifikasi Dropdown
function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown ketika klik di luar
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notifDropdown');
    const notifIcon = document.querySelector('.notif-icon');
    
    if (!notifIcon.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
</script>
</body>
</html>