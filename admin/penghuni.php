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

// ========= KELUAR DARI KAMAR (SET TGL KELUAR) =========
if (isset($_GET['aksi']) && $_GET['aksi'] == 'keluar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
        // Ambil data penghuni sebelum update
    $query_penghuni = mysqli_query($koneksi, "SELECT pk.*, k.nomor_kamar 
        FROM penghuni_kamar pk 
        LEFT JOIN kamar k ON pk.kamar_id = k.id 
        WHERE pk.id='$id'");
    $penghuni_data = mysqli_fetch_assoc($query_penghuni);
    $nama_penghuni = $penghuni_data['nama_penghuni'] ?? '';
    $kamar_id = $penghuni_data['kamar_id'];
    $nomor_kamar = $penghuni_data['nomor_kamar'] ?? '';
    
    // Update status penghuni dan set tanggal keluar
    $tgl_keluar = date('Y-m-d');
    $update = mysqli_query($koneksi, "UPDATE penghuni_kamar SET status='nonaktif', tgl_keluar='$tgl_keluar' WHERE id='$id'");
    
    // Update status kamar menjadi tersedia
    if ($kamar_id) {
        mysqli_query($koneksi, "UPDATE kamar SET status='tersedia' WHERE id='$kamar_id'");
    }
    
    // Update status user
    $penghuni_id = $penghuni_data['penghuni_id'];
    mysqli_query($koneksi, "UPDATE users SET status='nonaktif' WHERE id='$penghuni_id'");
    
    if ($update) {
        $pesan = "success|✅ <strong>$nama_penghuni</strong> telah keluar dari kamar <strong>$nomor_kamar</strong>!";
    } else {
        $pesan = "danger|❌ Gagal memproses! Error: " . mysqli_error($koneksi);
    }
}

// ========= AKTIFKAN KEMBALI =========
if (isset($_GET['aksi']) && $_GET['aksi'] == 'aktifkan' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query_penghuni = mysqli_query($koneksi, "SELECT nama_penghuni, penghuni_id FROM penghuni_kamar WHERE id='$id'");
    $penghuni_data = mysqli_fetch_assoc($query_penghuni);
    $nama_penghuni = $penghuni_data['nama_penghuni'] ?? '';
    
    $update = mysqli_query($koneksi, "UPDATE penghuni_kamar SET status='aktif', tgl_keluar=NULL WHERE id='$id'");
    
    // Update status user
    $penghuni_id = $penghuni_data['penghuni_id'];
    mysqli_query($koneksi, "UPDATE users SET status='aktif' WHERE id='$penghuni_id'");
    
    if ($update) {
        $pesan = "success|✅ <strong>$nama_penghuni</strong> telah diaktifkan kembali!";
    } else {
        $pesan = "danger|❌ Gagal mengaktifkan! Error: " . mysqli_error($koneksi);
    }
}

// ========= HAPUS PENGHUNI =========
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Ambil data sebelum dihapus
    $query_penghuni = mysqli_query($koneksi, "SELECT pk.*, k.nomor_kamar 
        FROM penghuni_kamar pk 
        LEFT JOIN kamar k ON pk.kamar_id = k.id 
        WHERE pk.id='$id'");
    $penghuni_data = mysqli_fetch_assoc($query_penghuni);
    $nama_penghuni = $penghuni_data['nama_penghuni'] ?? '';
    $kamar_id = $penghuni_data['kamar_id'];
    
    // Update status kamar jadi tersedia jika ada
    if ($kamar_id) {
        mysqli_query($koneksi, "UPDATE kamar SET status='tersedia' WHERE id='$kamar_id'");
    }
    
    $delete = mysqli_query($koneksi, "DELETE FROM penghuni_kamar WHERE id='$id'");
    
    if ($delete) {
        $pesan = "success|✅ Data penghuni <strong>$nama_penghuni</strong> berhasil dihapus!";
    } else {
        $pesan = "danger|❌ Gagal menghapus! Error: " . mysqli_error($koneksi);
    }
}

// ========= UPDATE DATA PENGHUNI =========
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $nama_penghuni = mysqli_real_escape_string($koneksi, trim($_POST['nama_penghuni']));
    $tgl_masuk = mysqli_real_escape_string($koneksi, $_POST['tgl_masuk']);
    $tgl_keluar = mysqli_real_escape_string($koneksi, $_POST['tgl_keluar']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $no_kamar_baru = mysqli_real_escape_string($koneksi, trim($_POST['no_kamar']));
    
    // Ambil data lama
    $query_old = mysqli_query($koneksi, "SELECT kamar_id FROM penghuni_kamar WHERE id='$id'");
    $old_data = mysqli_fetch_assoc($query_old);
    $old_kamar_id = $old_data['kamar_id'];
    
    // Cek kamar baru
    $kamar_id_baru = null;
    if (!empty($no_kamar_baru)) {
        $cek_kamar = mysqli_query($koneksi, "SELECT id FROM kamar WHERE nomor_kamar='$no_kamar_baru'");
        if (mysqli_num_rows($cek_kamar) > 0) {
            $data_kamar = mysqli_fetch_assoc($cek_kamar);
            $kamar_id_baru = $data_kamar['id'];
        }
    }
    
    // Update penghuni_kamar
    if ($kamar_id_baru) {
        $update_query = "UPDATE penghuni_kamar SET 
            nama_penghuni='$nama_penghuni',
            kamar_id='$kamar_id_baru',
            id_kamar='$kamar_id_baru',
            tgl_masuk='$tgl_masuk',
            tgl_keluar=" . ($tgl_keluar ? "'$tgl_keluar'" : "NULL") . ",
            status='$status'
            WHERE id='$id'";
    } else {
        $update_query = "UPDATE penghuni_kamar SET 
            nama_penghuni='$nama_penghuni',
            tgl_masuk='$tgl_masuk',
            tgl_keluar=" . ($tgl_keluar ? "'$tgl_keluar'" : "NULL") . ",
            status='$status'
            WHERE id='$id'";
    }
    
    $update = mysqli_query($koneksi, $update_query);
    
    if ($update) {
        // Update status kamar lama jadi tersedia
        if ($old_kamar_id && $old_kamar_id != $kamar_id_baru) {
            mysqli_query($koneksi, "UPDATE kamar SET status='tersedia' WHERE id='$old_kamar_id'");
        }
        
        // Update status kamar baru jadi terisi
        if ($kamar_id_baru && $status == 'aktif') {
            mysqli_query($koneksi, "UPDATE kamar SET status='terisi' WHERE id='$kamar_id_baru'");
        }
        
        $pesan = "success|✅ Data penghuni <strong>$nama_penghuni</strong> berhasil diperbarui!";
    } else {
        $pesan = "danger|❌ Gagal memperbarui data! Error: " . mysqli_error($koneksi);
    }
}

// ========= PENCARIAN & FILTER =========
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';

$query = "SELECT pk.*, u.username, pk.no_hp, k.nomor_kamar, k.tipe_kamar, k.harga
          FROM penghuni_kamar pk
          LEFT JOIN users u ON pk.penghuni_id = u.id
          LEFT JOIN kamar k ON pk.kamar_id = k.id
          WHERE 1=1";

if ($cari != '') {
    $query .= " AND (pk.nama_penghuni LIKE '%$cari%' OR k.nomor_kamar LIKE '%$cari%' OR u.username LIKE '%$cari%')";
}
if ($filter_status != '') {
    $query .= " AND pk.status='$filter_status'";
}
$query .= " ORDER BY pk.tgl_masuk DESC";
$result = mysqli_query($koneksi, $query);

// Statistik
$total_penghuni = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM penghuni_kamar"));
$aktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM penghuni_kamar WHERE status='aktif'"));
$nonaktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM penghuni_kamar WHERE status='nonaktif'"));

// Hitung total kamar terisi
$kamar_terisi = mysqli_num_rows(mysqli_query($koneksi, "SELECT DISTINCT kamar_id FROM penghuni_kamar WHERE status='aktif' AND kamar_id IS NOT NULL"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css">
<title>Data Penghuni - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        <li><a href="penghuni.php" class="active"><i class="fa-solid fa-users"></i> Penghuni</a></li>
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
        <div class="top-bar-left">
            <h1><i class="fa-solid fa-users"></i> Data Penghuni Kamar</h1>
            <p>Kelola data penghuni dan penempatan kamar</p>
        </div>
    </div>

    <?php if (!empty($pesan)): 
        list($type, $msg) = explode('|', $pesan, 2);
    ?>
    <div class="alert alert-<?= $type ?>" id="alertNotification">
        <i class="fa-solid fa-<?= $type == 'success' ? 'check-circle' : ($type == 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <span><?= $msg ?></span>
        <button onclick="closeAlert()" style="margin-left: auto; background: none; border: none; color: inherit; cursor: pointer; font-size: 18px; padding: 0; width: 24px; height: 24px;">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_penghuni ?></h3>
                <p>Total Penghuni</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $aktif ?></h3>
                <p>Penghuni Aktif</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div class="stat-info">
                <h3><?= $nonaktif ?></h3>
                <p>Penghuni Keluar</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="stat-info">
                <h3><?= $kamar_terisi ?></h3>
                <p>Kamar Terisi</p>
            </div>
        </div>
    </div>

    <form class="search-bar" method="get" action="">
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="🔍 Cari nama penghuni, username, atau no kamar...">
        <select name="status" class="form-control" style="width: 150px;">
            <option value="">Semua Status</option>
            <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Cari
        </button>
        <a href="penghuni.php" class="btn btn-secondary">
            <i class="fa-solid fa-rotate"></i> Reset
        </a>
    </form>

    <div class="table-container">
        <table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Penghuni</th>
            <th>No HP</th>
            <th>No Kamar</th>
            <th>Tgl Masuk</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($result) > 0): 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
        ?>
        <tr>
            <td><strong><?= $no++ ?></strong></td>
            <td><?= htmlspecialchars($row['nama_penghuni']) ?></td>
            <td><?= htmlspecialchars($row['no_hp']) ?: '-' ?></td>
            <td>
                <?php if ($row['nomor_kamar']): ?>
                    <span class="badge" style="background: #6366f1; color: white; font-size: 13px;">
                        <i class="fa-solid fa-door-closed"></i> <?= htmlspecialchars($row['nomor_kamar']) ?>
                    </span>
                <?php else: ?>
                    <span style="color: #94a3b8;">Belum ada kamar</span>
                <?php endif; ?>
            </td>
            <td><?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?></td>
            <td>
                <?php if ($row['status'] == 'aktif'): ?>
                    <span class="badge badge-success">
                        <i class="fa-solid fa-circle-check"></i> Aktif
                    </span>
                <?php else: ?>
                    <span class="badge badge-danger">
                        <i class="fa-solid fa-circle-xmark"></i> Nonaktif
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="openModal('editModal<?= $row['id'] ?>')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <?php if ($row['status'] == 'aktif'): ?>
                <button onclick="confirmAction('keluar', <?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_penghuni']) ?>')" 
                    class="btn btn-warning btn-sm" title="Keluar dari Kamar">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
                <?php else: ?>
                <button onclick="confirmAction('aktifkan', <?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_penghuni']) ?>')" 
                    class="btn btn-success btn-sm" title="Aktifkan Kembali">
                    <i class="fa-solid fa-check"></i>
                </button>
                <?php endif; ?>
                <button onclick="confirmAction('hapus', <?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_penghuni']) ?>')" 
                    class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>

        <!-- MODAL EDIT -->
        <div class="modal" id="editModal<?= $row['id'] ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5><i class="fa-solid fa-pen"></i> Edit Data Penghuni</h5>
                    <button class="modal-close" onclick="closeModal('editModal<?= $row['id'] ?>')">×</button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="form-group">
                            <label>Nama Penghuni <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="nama_penghuni" class="form-control" value="<?= htmlspecialchars($row['nama_penghuni']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>No HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($row['no_hp']) ?>" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-group">
                            <label>No Kamar</label>
                            <input type="text" name="no_kamar" class="form-control" value="<?= htmlspecialchars($row['nomor_kamar']) ?>" placeholder="Contoh: 101">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Masuk <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="tgl_masuk" class="form-control" value="<?= $row['tgl_masuk'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status <span style="color: #ef4444;">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="aktif" <?= $row['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $row['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal<?= $row['id'] ?>')">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endwhile; else: ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                Tidak ada data penghuni ditemukan
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
    </div>
</div>

<!-- MODAL KONFIRMASI -->
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-header">
            <div class="confirm-icon" id="confirmIcon">
                <i class="fa-solid fa-question"></i>
            </div>
            <h3 id="confirmTitle">Konfirmasi Aksi</h3>
            <p id="confirmMessage">Apakah Anda yakin ingin melakukan aksi ini?</p>
        </div>
        <div class="confirm-footer">
            <button class="btn btn-danger" id="confirmBtn" onclick="executeAction()">
                <i class="fa-solid fa-check"></i> Ya, Lanjutkan
            </button>
            <button class="btn btn-secondary" onclick="closeConfirm()">
                <i class="fa-solid fa-times"></i> Batal
            </button>
        </div>
    </div>
</div>

<script>
let actionType = '';
let actionId = '';
let actionName = '';

function openModal(id) {
    document.getElementById(id).classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function confirmAction(type, id, name) {
    actionType = type;
    actionId = id;
    actionName = name;
    
    const confirmModal = document.getElementById('confirmModal');
    const confirmIcon = document.getElementById('confirmIcon');
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (type === 'hapus') {
        confirmIcon.className = 'confirm-icon danger';
        confirmIcon.innerHTML = '<i class="fa-solid fa-trash"></i>';
        confirmTitle.textContent = 'Hapus Data Penghuni?';
        confirmMessage.innerHTML = `Apakah Anda yakin ingin menghapus data <strong>${name}</strong>?<br><small style="color: #ef4444;">Data yang dihapus tidak dapat dikembalikan!</small>`;
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Ya, Hapus';
    } else if (type === 'keluar') {
        confirmIcon.className = 'confirm-icon warning';
        confirmIcon.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i>';
        confirmTitle.textContent = 'Penghuni Keluar?';
        confirmMessage.innerHTML = `Apakah <strong>${name}</strong> keluar dari kamar?<br><small style="color: #f59e0b;">Status akan diubah menjadi nonaktif dan kamar akan tersedia kembali.</small>`;
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar';
    } else if (type === 'aktifkan') {
        confirmIcon.className = 'confirm-icon success';
        confirmIcon.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
        confirmTitle.textContent = 'Aktifkan Kembali?';
        confirmMessage.innerHTML = `Apakah Anda yakin ingin mengaktifkan kembali <strong>${name}</strong>?<br><small style="color: #10b981;">Status akan diubah menjadi aktif.</small>`;
        confirmBtn.className = 'btn btn-success';
        confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Ya, Aktifkan';
    }
    
    confirmModal.classList.add('show');
}

function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
}

function executeAction() {
    window.location.href = `?aksi=${actionType}&id=${actionId}`;
}

// Close modal ketika klik di luar modal content
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Close confirm modal ketika klik di luar
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirm();
    }
});

// Auto close alert setelah 5 detik
setTimeout(function() {
    closeAlert();
}, 5000);

function closeAlert() {
    const alert = document.getElementById('alertNotification');
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 300);
    }
}

// Scroll to top when alert appears
if (document.getElementById('alertNotification')) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</body>
</html>