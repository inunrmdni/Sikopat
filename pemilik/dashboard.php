<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Cek login dan role
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

// ==========================
// AMBIL DATA DARI DATABASE
// ==========================

// Total penghuni aktif
$q_penghuni = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penghuni_kamar WHERE status='aktif'");
$total_penghuni = mysqli_fetch_assoc($q_penghuni)['total'] ?? 0;

// Total kamar
$q_kamar = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM kamar");
$total_kamar = mysqli_fetch_assoc($q_kamar)['total'] ?? 0;

// Kamar terisi
$q_kamar_terisi = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM kamar WHERE status='terisi'");
$kamar_terisi = mysqli_fetch_assoc($q_kamar_terisi)['total'] ?? 0;

// Pemasukan bulan ini
$q_pemasukan = mysqli_query($koneksi, "
    SELECT SUM(total_bayar) AS total 
    FROM pembayaran 
    WHERE MONTH(created_at)=MONTH(CURRENT_DATE()) AND YEAR(created_at)=YEAR(CURRENT_DATE()) AND status='lunas'
");
$pemasukan_bulan_ini = mysqli_fetch_assoc($q_pemasukan)['total'] ?? 0;

// Tagihan belum lunas
$q_tagihan = mysqli_query($koneksi, "SELECT SUM(total_bayar) AS total FROM pembayaran WHERE status='belum_lunas'");
$tagihan_belum_lunas = mysqli_fetch_assoc($q_tagihan)['total'] ?? 0;

// Notifikasi
$notif_query = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM notifikasi WHERE status='baru'");
$notif_data = mysqli_fetch_assoc($notif_query);
$total_notif = $notif_data['total'];

$list_query = mysqli_query($koneksi, "SELECT * FROM notifikasi ORDER BY tanggal DESC LIMIT 5");

// Pengaduan pending
$q_pengaduan = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengaduan WHERE status='baru'");
$pengaduan_baru = mysqli_fetch_assoc($q_pengaduan)['total'] ?? 0;

// Pembayaran pending
$q_pembayaran_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pembayaran WHERE status='pending'");
$pembayaran_pending = mysqli_fetch_assoc($q_pembayaran_pending)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pemilik - SIKOPAT</title>
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
    
    .mark-read {
        font-size: 12px;
        color: #667eea;
        cursor: pointer;
        text-decoration: none;
    }
    
    .mark-read:hover {
        text-decoration: underline;
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
    
    .chart-container { 
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px; 
    }
    
    .chart-card, .status-card { 
        background: white; 
        padding: 25px; 
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .chart-card h3, .status-card h3 {
        font-size: 18px;
        color: #1e1b4b;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .action-btn {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }
    
    .action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    }
    
    .action-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
    }
    
    .action-text h5 {
        font-size: 14px;
        color: #1e1b4b;
        margin: 0 0 3px 0;
        font-weight: 600;
    }
    
    .action-text p {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; }
        .content { margin-left: 0; }
        .chart-container { grid-template-columns: 1fr; }
        .cards { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-building"></i> SIKOPAT</h3>
        <p>Sistem Kost Pintar - Pemilik</p>
    </div>
    <ul>
        <li><a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="manajemenAkun.php"><i class="fa-solid fa-user-gear"></i> Manajemen Akun</a></li>
        <li><a href="penghuni.php"><i class="fa-solid fa-users"></i> Penghuni</a></li>
        <li><a href="kamar.php"><i class="fa-solid fa-bed"></i> Kamar</a></li>
        <li><a href="pengumuman.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
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
        <div class="welcome-text">
            <h1>Dashboard Pemilik Kos</h1>
            <p>Selamat datang kembali! Berikut ringkasan bisnis kos Anda</p>
        </div>
        <div class="user-info">
            <div class="notif-icon" onclick="toggleNotif()">
                <i class="fa-solid fa-bell"></i>
                <?php if($total_notif > 0): ?>
                <span class="notif-badge"><?= $total_notif ?></span>
                <?php endif; ?>
            </div>
            
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <h4><i class="fa-solid fa-bell"></i> Notifikasi</h4>
                    <a href="#" class="mark-read" onclick="markAllRead(event)">Tandai sudah dibaca</a>
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
            
            <a href="profile.php" style="text-decoration: none;">
                <div class="user-avatar" title="Lihat Profil">
                    <i class="fa-solid fa-user"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <h4>Total Penghuni</h4>
            <h2><?= $total_penghuni ?><span style="font-size: 20px; color: #94a3b8;">/<?= $total_kamar ?></span></h2>
            <p class="green"><i class="fa-solid fa-circle-check"></i> Penghuni Aktif</p>
        </div>
        <div class="card">
            <div class="card-icon green">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <h4>Kamar Terisi</h4>
            <h2><?= $kamar_terisi ?><span style="font-size: 20px; color: #94a3b8;">/<?= $total_kamar ?></span></h2>
            <p style="color: #64748b;"><?= round(($kamar_terisi / max($total_kamar, 1)) * 100, 1) ?>% Okupansi</p>
        </div>
        <div class="card">
            <div class="card-icon orange">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <h4>Pemasukan Bulan Ini</h4>
            <h2 style="font-size: 24px;">Rp <?= number_format($pemasukan_bulan_ini, 0, ',', '.') ?></h2>
            <p class="green"><i class="fa-solid fa-arrow-trend-up"></i> +12% dari bulan lalu</p>
        </div>
        <div class="card">
            <div class="card-icon red">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <h4>Tagihan Belum Lunas</h4>
            <h2 style="font-size: 24px;">Rp <?= number_format($tagihan_belum_lunas, 0, ',', '.') ?></h2>
            <p class="red"><i class="fa-solid fa-clock"></i> Perlu follow up</p>
        </div>
    </div>

    <div class="quick-actions">
        <a href="pembayaran.php" class="action-btn">
            <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="action-text">
                <h5>Pembayaran Pending</h5>
                <p><?= $pembayaran_pending ?> menunggu verifikasi</p>
            </div>
        </a>
        <a href="pengaduan.php" class="action-btn">
            <div class="action-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="action-text">
                <h5>Pengaduan Baru</h5>
                <p><?= $pengaduan_baru ?> pengaduan perlu ditinjau</p>
            </div>
        </a>
        <a href="laporan.php" class="action-btn">
            <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <i class="fa-solid fa-file-chart"></i>
            </div>
            <div class="action-text">
                <h5>Laporan Keuangan</h5>
                <p>Lihat laporan lengkap</p>
            </div>
        </a>
    </div>

    <div class="chart-container">
        <div class="chart-card">
            <h3><i class="fa-solid fa-chart-bar"></i> Grafik Pemasukan Bulanan</h3>
            <canvas id="incomeChart" height="120"></canvas>
        </div>
        <div class="status-card">
            <h3><i class="fa-solid fa-chart-pie"></i> Status Kamar</h3>
            <canvas id="roomChart" height="120"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notifDropdown');
    const notifIcon = document.querySelector('.notif-icon');
    
    if (!notifIcon.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

function markAllRead(event) {
    event.preventDefault();
    if(confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
        window.location.href = 'mark_read.php';
    }
}

const ctx1 = document.getElementById('incomeChart');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: ['Juli', 'Agustus', 'September', 'Oktober', 'November'],
    datasets: [{
      label: 'Pemasukan (Rp)',
      data: [8200000, 9400000, 8800000, 9700000, 3100000],
      backgroundColor: function(context) {
        const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, '#667eea');
        gradient.addColorStop(1, '#764ba2');
        return gradient;
      },
      borderRadius: 8,
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: '#1e1b4b',
        padding: 12,
        borderRadius: 8,
        titleFont: {
          size: 14,
          weight: 'bold'
        },
        bodyFont: {
          size: 13
        },
        callbacks: {
          label: function(context) {
            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return 'Rp ' + (value / 1000000) + 'jt';
          }
        },
        grid: {
          color: '#f1f5f9'
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    }
  }
});

const ctx2 = document.getElementById('roomChart');
new Chart(ctx2, {
  type: 'doughnut',
  data: {
    labels: ['Terisi', 'Tersedia'],
    datasets: [{
      data: [<?= $kamar_terisi ?>, <?= $total_kamar - $kamar_terisi ?>],
      backgroundColor: [
        '#667eea',
        '#10b981'
      ],
      borderWidth: 0,
      spacing: 5
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          font: {
            size: 13,
            weight: '500'
          },
          usePointStyle: true,
          pointStyle: 'circle'
        }
      },
      tooltip: {
        backgroundColor: '#1e1b4b',
        padding: 12,
        borderRadius: 8,
        callbacks: {
          label: function(context) {
            return context.label + ': ' + context.parsed + ' kamar';
          }
        }
      }
    }
  }
});
</script>
</body>
</html>