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

$user_id = $_SESSION['user_id'];
$id_penghuni = $_SESSION['id_penghuni'] ?? $_SESSION['user_id'];

// Ambil semua pengaduan milik penghuni ini
$query = mysqli_query($koneksi, "
    SELECT * FROM pengaduan 
    WHERE user_id='$user_id' 
    ORDER BY created_at DESC
");

// Statistik pengaduan
$total = mysqli_num_rows($query);
$baru = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE user_id='$user_id' AND status='baru'"));
$diproses = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE user_id='$user_id' AND status='diproses'"));
$selesai = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE user_id='$user_id' AND status='selesai'"));

// Data penghuni untuk sidebar
$q_penghuni = mysqli_query($koneksi, "
    SELECT pk.*, k.nomor_kamar 
    FROM penghuni_kamar pk 
    LEFT JOIN kamar k ON pk.id_kamar = k.id 
    WHERE pk.id = '$id_penghuni'
");
$data_penghuni = mysqli_fetch_assoc($q_penghuni);
$username = $data_penghuni['nama_lengkap'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaduan Saya - SIKOPAT</title>
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
    
    .top-bar-left h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1e1b4b;
        margin-bottom: 5px;
    }
    
    .top-bar-left p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    
    .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    
    .stat-info h3 {
        font-size: 28px;
        font-weight: 700;
        color: #1e1b4b;
        margin: 0;
    }
    
    .stat-info p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    .pengaduan-grid {
        display: grid;
        gap: 20px;
    }
    
    .pengaduan-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        gap: 20px;
        align-items: center;
    }
    
    .pengaduan-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .pengaduan-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
    }
    
    .pengaduan-info h4 {
        font-size: 16px;
        color: #1e1b4b;
        margin: 0 0 8px 0;
        font-weight: 600;
    }
    
    .pengaduan-info p {
        font-size: 14px;
        color: #64748b;
        margin: 0 0 5px 0;
        line-height: 1.6;
    }
    
    .pengaduan-info small {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .pengaduan-image {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        object-fit: cover;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 3px solid #f1f5f9;
    }
    
    .pengaduan-image:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .no-image {
        width: 80px;
        height: 80px;
        background: #f8fafc;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #cbd5e1;
        font-size: 32px;
    }
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-baru { background: #fee2e2; color: #dc2626; }
    .badge-diproses { background: #fef3c7; color: #d97706; }
    .badge-selesai { background: #d1fae5; color: #059669; }
    
    .empty-state {
        background: white;
        padding: 60px;
        text-align: center;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .empty-state i {
        font-size: 64px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        color: #64748b;
        margin: 0 0 10px 0;
    }
    
    .empty-state p {
        color: #94a3b8;
        margin: 0;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.show { display: flex; }
    
    .modal-content {
        position: relative;
        max-width: 90%;
        max-height: 90vh;
    }
    
    .modal-content img {
        max-width: 100%;
        max-height: 90vh;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.5);
    }
    
    .modal-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        color: #1e1b4b;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: #f1f5f9;
        transform: rotate(90deg);
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; }
        .pengaduan-card {
            grid-template-columns: 1fr;
            text-align: center;
        }
        .stats-cards { grid-template-columns: 1fr; }
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
            <h4><?= htmlspecialchars($username) ?></h4>
            <p>Kamar <?= $data_penghuni['nomor_kamar'] ?? '-' ?></p>
        </div>
    </div>
    
    <ul>
        <li><a href="dashboard_penghuni.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="pengumuman_penghuni.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="tagihan_penghuni.php"><i class="fa-solid fa-file-invoice"></i> Tagihan Saya</a></li>
        <li><a href="pembayaran_penghuni.php"><i class="fa-solid fa-wallet"></i> Pembayaran</a></li>
        <li><a href="chat_penghuni.php"><i class="fa-solid fa-comments"></i> Chat Admin</a></li>
        <li><a href="pengaduan_penghuni.php" class="active"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
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
        <div class="top-bar-left">
            <h1><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan Saya</h1>
            <p>Kelola dan pantau pengaduan yang Anda ajukan</p>
        </div>
        <a href="../penghuni/pengaduan/tambah_pengaduan.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Pengaduan
        </a>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total ?></h3>
                <p>Total Pengaduan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div class="stat-info">
                <h3><?= $baru ?></h3>
                <p>Pengaduan Baru</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-spinner"></i>
            </div>
            <div class="stat-info">
                <h3><?= $diproses ?></h3>
                <p>Sedang Diproses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $selesai ?></h3>
                <p>Selesai</p>
            </div>
        </div>
    </div>

    <div class="pengaduan-grid">
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                <div class="pengaduan-card">
                    <div class="pengaduan-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    
                    <div class="pengaduan-info">
                        <h4>Pengaduan #<?= $row['id'] ?></h4>
                        <p><?= htmlspecialchars($row['isi']) ?></p>
                        <small>
                            <i class="fa-regular fa-calendar"></i>
                            <?= date('d F Y, H:i', strtotime($row['created_at'])) ?> WIB
                        </small>
                    </div>
                    
                    <div>
                        <?php if (!empty($row['gambar'])): ?>
                            <img 
                                src="../uploads/pengaduan/<?= htmlspecialchars($row['gambar']) ?>" 
                                class="pengaduan-image"
                                onclick="showPreview('../uploads/pengaduan/<?= htmlspecialchars($row['gambar']) ?>')"
                                alt="Bukti pengaduan"
                            >
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fa-regular fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <?php 
                        $status = $row['status'];
                        $badge_class = match($status) {
                            'baru' => 'badge-baru',
                            'diproses' => 'badge-diproses',
                            'selesai' => 'badge-selesai',
                            default => 'badge-baru'
                        };
                        $icon = match($status) {
                            'baru' => 'fa-circle-exclamation',
                            'diproses' => 'fa-spinner',
                            'selesai' => 'fa-circle-check',
                            default => 'fa-circle'
                        };
                        ?>
                        <span class="badge <?= $badge_class ?>">
                            <i class="fa-solid <?= $icon ?>"></i>
                            <?= ucfirst($status) ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h3>Belum ada pengaduan</h3>
                <p>Anda belum membuat pengaduan. Klik tombol "Tambah Pengaduan" untuk membuat pengaduan baru.</p>
                <a href="tambah_pengaduan.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fa-solid fa-plus"></i> Buat Pengaduan
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Preview Gambar -->
<div class="modal" id="previewModal" onclick="closeModal()">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">
            <i class="fa-solid fa-times"></i>
        </button>
        <img id="previewImage" src="" alt="Preview">
    </div>
</div>

<script>
function showPreview(src) {
    document.getElementById('previewImage').src = src;
    document.getElementById('previewModal').classList.add('show');
}

function closeModal() {
    document.getElementById('previewModal').classList.remove('show');
}

// Prevent modal close when clicking on image
document.querySelector('.modal-content').addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>
</body>
</html>