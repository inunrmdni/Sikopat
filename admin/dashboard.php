<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Timeout 1 menit
$timeout_duration = 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    header("Location: ../logout.php");
    exit;
}
$_SESSION['last_activity'] = time();

// Cek login dan role
if ($_SESSION['role'] !== 'admin') {
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
    WHERE MONTH(created_at)=MONTH(CURRENT_DATE()) AND YEAR(created_at)=YEAR(CURRENT_DATE())
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

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css">
<title>Dashboard Admin - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-building"></i> SIKOPAT</h3>
        <p>Sistem Kost Pintar</p>
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
            <a href="#" onclick="confirmLogout()" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <div class="welcome-text">
            <h1><i class="fa-solid fa-chart-line"></i> Dashboard Admin</h1>
            <p>Selamat datang kembali! Berikut ringkasan sistem manajemen kos</p>
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
                    <a href="#" class="mark-read" onclick="markAllRead(event)">Tandai sudah dibaca</a>
                </div>
                    <div class="notif-body">
                        <?php if($total_notif > 0): ?>
                            <?php 
                            mysqli_data_seek($list_query, 0);
                            while($notif = mysqli_fetch_assoc($list_query)): 
                                $pengaduan_id = isset($notif['pengaduan_id']) ? $notif['pengaduan_id'] : 0;
                            ?>
                                <div class="notif-item <?= $notif['status'] == 'baru' ? 'unread' : '' ?>" 
                                    onclick="showNotifDetail(<?= $notif['id'] ?>, '<?= htmlspecialchars(addslashes($notif['pesan'])) ?>', '<?= date('d/m/Y H:i', strtotime($notif['tanggal'])) ?>', <?= $pengaduan_id ?>)">
                                    <p><?= htmlspecialchars($notif['pesan']) ?></p>
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
            <p style="color: #64748b;"><?= $total_kamar > 0 ? round(($kamar_terisi / $total_kamar) * 100, 1) : 0 ?>% Okupansi</p>
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
// Logout Confirmation
function confirmLogout() {
    Swal.fire({
        title: 'Logout?',
        text: 'Apakah Anda yakin ingin keluar dari sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-right-from-bracket"></i> Ya, Logout',
        cancelButtonText: '<i class="fa-solid fa-times"></i> Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../logout.php';
        }
    });
}

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

// Tampilkan detail notifikasi dengan tombol untuk lihat pengaduan
function showNotifDetail(notifId, pesan, tanggal, pengaduanId) {
    document.getElementById('notifDetailTime').textContent = tanggal;
    document.getElementById('notifDetailMessage').textContent = pesan;
    
    // Update tombol footer
    const footer = document.querySelector('.notif-modal-footer');
    if(pengaduanId && pengaduanId > 0) {
        footer.innerHTML = `
            <button class="btn-view-pengaduan" onclick="viewPengaduan(${notifId}, ${pengaduanId})">
                <i class="fa-solid fa-eye"></i> Lihat Pengaduan
            </button>
            <button class="btn-close-modal" onclick="closeNotifModal()">
                <i class="fa-solid fa-times"></i> Tutup
            </button>
        `;
    } else {
        footer.innerHTML = `
            <button class="btn-close-modal" onclick="closeNotifModal()">
                <i class="fa-solid fa-times"></i> Tutup
            </button>
        `;
    }
    
    document.getElementById('notifModal').style.display = 'block';
    
    // Tandai sebagai sudah dibaca
    markAsRead(notifId);
    
    // Tutup dropdown notifikasi
    document.getElementById('notifDropdown').classList.remove('show');
}

// Fungsi untuk melihat detail pengaduan
function viewPengaduan(notifId, pengaduanId) {
    window.location.href = 'mark_notif_read.php?id=' + notifId;
}

// Fungsi sederhana: langsung redirect
function redirectToNotif(notifId) {
    window.location.href = 'mark_notif_read.php?id=' + notifId;
}

// Tandai semua notifikasi sudah dibaca
function markAllRead(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Tandai Semua?',
        text: 'Tandai semua notifikasi sebagai sudah dibaca?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'read.php';
        }
    });
}

// Chart pemasukan dengan styling modern
const ctx1 = document.getElementById('incomeChart');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: ['Juli', 'Agustus', 'September', 'Oktober', 'November'],
    datasets: [{
      label: 'Pemasukan (Rp)',
      data: [8200000, 9400000, 8800000, 9700000, <?= $pemasukan_bulan_ini ?>],
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

// Chart status kamar dengan styling modern
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