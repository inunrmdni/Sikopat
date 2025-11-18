<?php
session_start();
require_once '../koneksi.php';
include '../cek_login.php';

if ($_SESSION['role'] !== 'penghuni') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_penghuni = $_SESSION['id_penghuni'] ?? $_SESSION['user_id'];
$username = $_SESSION['username'];

// ================== Proses Kirim Komentar ==================
if (isset($_POST['kirim_komentar'])) {
    $pengumuman_id = intval($_POST['pengumuman_id']);
    $komentar = mysqli_real_escape_string($koneksi, trim($_POST['komentar']));

    if (!empty($komentar)) {
        mysqli_query($koneksi, "INSERT INTO komentar_pengumuman (pengumuman_id, user_id, komentar, created_at) 
                                VALUES ($pengumuman_id, $user_id, '$komentar', NOW())");

        mysqli_query($koneksi, "INSERT INTO notifikasi (id_pengumuman, pesan, jenis, status, tanggal)
                                VALUES ($pengumuman_id, '$username memberikan komentar.', 'pengumuman', 'baru', NOW())");

        echo "<script>
            setTimeout(() => {
                Swal.fire({
                    title: 'Komentar Terkirim!',
                    text: 'Komentarmu berhasil dikirim.',
                    icon: 'success',
                    confirmButtonColor: '#6366f1'
                }).then(() => {
                    window.location.href = 'pengumuman.php';
                });
            }, 150);
        </script>";
    }
}

// ================== Ambil Pengumuman ==================
$query_pengumuman = mysqli_query($koneksi, "SELECT * FROM pengumuman ORDER BY created_at DESC");

// Data Penghuni
$q_penghuni = mysqli_query($koneksi, "
    SELECT pk.*, k.nomor_kamar 
    FROM penghuni_kamar pk 
    LEFT JOIN kamar k ON pk.id_kamar = k.id 
    WHERE pk.id = '$id_penghuni';
");
$data_penghuni = mysqli_fetch_assoc($q_penghuni);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css">
<title>Pengumuman - SIKOPAT</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'Poppins', sans-serif; }

    .pengumuman-content {
        margin-top: 10px;
        padding: 10px;
        line-height: 1.6;
        font-size: 15px;
        background: #fafafa;
        border-radius: 6px;
        border: 1px solid #eee;
        color: #444;
    }

    /* Komentar */
    .komentar-section {
        margin-top: 20px;
        background: #f9fafb;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #e5e7eb;
    }
    .komentar-item {
        background: #ffffff;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        border-left: 4px solid #6366f1;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .komentar-header {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #555;
    }
    .komentar-user { font-weight: 600; }
    .komentar-text { margin-top: 7px; }

    .form-komentar textarea {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-size: 14px;
    }
    .form-komentar button {
        background: #6366f1;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 10px;
    }
    .form-komentar button:hover {
        background: #5254d8;
    }

    .btn-detail {
        margin-top: 12px;
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        float: right;
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
        <div class="user-avatar-big"><i class="fa-solid fa-user"></i></div>
        <div class="user-profile-info">
            <h4><?= htmlspecialchars($data_penghuni['nama_lengkap'] ?? $username) ?></h4>
            <p>Kamar <?= $data_penghuni['nomor_kamar'] ?? '-' ?></p>
        </div>
    </div>

    <ul>
        <li><a href="dashboard_penghuni.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="pengumuman_penghuni.php" class="active"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="tagihan_penghuni.php"><i class="fa-solid fa-file-invoice"></i> Tagihan Saya</a></li>
        <li><a href="pembayaran_penghuni.php"><i class="fa-solid fa-wallet"></i> Pembayaran</a></li>
        <li><a href="chat_penghuni.php"><i class="fa-solid fa-comments"></i> Chat Admin</a></li>
        <li><a href="pengaduan_penghuni.php"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
        <li><a href="profil_penghuni.php"><i class="fa-solid fa-user-circle"></i> Profil Saya</a></li>
        <li><a href="../logout.php" onclick="return confirm('Yakin ingin logout?')" class="logout-btn">
            <i class="fa fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <h1><i class="fa-solid fa-bullhorn"></i> Pengumuman</h1>
        <p>Lihat pengumuman terbaru dari admin kos</p>
    </div>

    <?php while ($p = mysqli_fetch_assoc($query_pengumuman)): ?>

    <?php 
    $full = $p['isi'];
    $short = strlen($full) > 200 ? substr($full, 0, 200) . "..." : $full;
    ?>

    <div class="pengumuman-card">

        <div class="pengumuman-header">
            <div class="pengumuman-icon"><i class="fa-solid fa-megaphone"></i></div>
            <div class="pengumuman-title">
                <h3><?= htmlspecialchars($p['judul']) ?></h3>
                <div class="pengumuman-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d F Y, H:i', strtotime($p['created_at'])) ?> WIB
                </div>
            </div>
        </div>

        <!-- ISI DIPOTONG -->
        <div class="pengumuman-content">
            <?= nl2br(htmlspecialchars($short)) ?>
        </div>

        <button class="btn-detail" onclick='lihatDetailPengumuman(<?= json_encode($p) ?>)'>
            Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i>
        </button>

        <div style="clear: both;"></div>

        <!-- KOMENTAR -->
        <div class="komentar-section">
            <h4><i class="fa-solid fa-comments"></i> Komentar</h4>

            <?php
            $idp = $p['id'];
            $qK = mysqli_query($koneksi, "
                SELECT k.*, u.username 
                FROM komentar_pengumuman k 
                LEFT JOIN users u ON k.user_id = u.id 
                WHERE k.pengumuman_id = $idp 
                ORDER BY k.created_at DESC
            ");
            ?>

            <?php while ($k = mysqli_fetch_assoc($qK)): ?>
                <div class="komentar-item">
                    <div class="komentar-header">
                        <span class="komentar-user"><i class="fa-solid fa-user"></i> <?= $k['username'] ?></span>
                        <span><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($k['created_at'])) ?></span>
                    </div>
                    <div class="komentar-text"><?= nl2br(htmlspecialchars($k['komentar'])) ?></div>
                </div>
            <?php endwhile; ?>

            <!-- FORM -->
            <form method="POST" class="form-komentar">
                <input type="hidden" name="pengumuman_id" value="<?= $p['id'] ?>">
                <textarea name="komentar" rows="3" placeholder="Tulis komentar..." required></textarea>
                <button type="submit" name="kirim_komentar"><i class="fa-solid fa-paper-plane"></i> Kirim</button>
            </form>
        </div>
    </div>

    <?php endwhile; ?>
</div>

<script>
function lihatDetailPengumuman(data) {
    const tanggal = new Date(data.created_at);
    const tanggalFormat = tanggal.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    Swal.fire({
        title: `
            <div style="display:flex;align-items:center;gap:10px;">
                <i class="fa-solid fa-bullhorn" style="font-size:24px;color:#6366f1;"></i>
                <span style="font-size:18px;font-weight:600;">Detail Pengumuman</span>
            </div>
        `,
        html: `
            <div style="text-align:left;font-size:15px;line-height:1.6;">
                
                <div style="margin-bottom:12px;">
                    <strong style="color:#374151;">Judul Pengumuman</strong><br>
                    <div style="padding:10px;background:#f3f4f6;border-radius:8px;margin-top:5px;">
                        ${data.judul}
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <strong style="color:#374151;">Isi Pengumuman</strong><br>
                    <div style="padding:12px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;margin-top:5px;white-space:pre-wrap;">
                        ${data.isi}
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <strong style="color:#374151;">Tanggal Dibuat</strong><br>
                    <div style="padding:10px;background:#f3f4f6;border-radius:8px;margin-top:5px;">
                        ${tanggalFormat}
                    </div>
                </div>

            </div>
        `,
        width: "600px",
        showConfirmButton: true,
        confirmButtonColor: "#6366f1",
        confirmButtonText: "<i class='fa-solid fa-check'></i> Tutup"
    });
}
</script>

</body>
</html>
