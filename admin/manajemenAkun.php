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

// ========= AKSI SUSPEND / AKTIFKAN =========
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    // Ambil nama user sebelum aksi
    $user_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM users WHERE id='$id'"));
    $nama_user = $user_data['username'] ?? 'Penghuni';

    if ($aksi == 'suspend') {
        mysqli_query($koneksi, "UPDATE users SET status='suspend' WHERE id='$id'");
        $pesan = "warning|⚠️ Akun <strong>$nama_user</strong> telah di-suspend!";
    } elseif ($aksi == 'aktifkan') {
        mysqli_query($koneksi, "UPDATE users SET status='aktif' WHERE id='$id'");
        $pesan = "success|✅ Akun <strong>$nama_user</strong> telah diaktifkan kembali!";
    } elseif ($aksi == 'hapus') {
        mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
        $pesan = "danger|🗑️ Akun <strong>$nama_user</strong> telah dihapus permanen!";
    }
}

// ========= SIMPAN DATA BARU =========
if (isset($_POST['simpan'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']); // TANPA HASH

    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "warning|⚠️ Username sudah terdaftar!";
    } else {
        $simpan = mysqli_query($koneksi, "INSERT INTO users (username, password, role, status, created_at)
                                          VALUES ('$username', '$password', 'penghuni', 'aktif', NOW())");
        $pesan = $simpan ? "success|✅ Akun penghuni berhasil ditambahkan!" : "danger|❌ Gagal menambahkan akun!";
    }
}

// ========= UPDATE DATA (EDIT) =========
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);

    $update_query = "UPDATE users SET username='$username'";

    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($koneksi, $_POST['password']); // TANPA HASH
        $update_query .= ", password='$password'";
    }
    $update_query .= " WHERE id='$id'";
    $update = mysqli_query($koneksi, $update_query);

    if ($update) {
        $pesan = "success|✅ Data akun <strong>$username</strong> berhasil diperbarui!";
    } else {
        $pesan = "danger|❌ Gagal memperbarui data akun!";
    }
}

// ========= PENCARIAN =========
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$query = "SELECT * FROM users WHERE role='penghuni'";
if ($cari != '') {
    $query .= " AND username LIKE '%$cari%'";
}
$query .= " ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);

// Statistik
$total_akun = mysqli_num_rows($result);
$aktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='penghuni' AND status='aktif'"));
$suspend = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='penghuni' AND status='suspend'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css">
<title>Manajemen Akun - SIKOPAT</title>
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
        <li><a href="manajemenAkun.php" class="active"><i class="fa-solid fa-user-gear"></i> Manajemen Akun</a></li>
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
        <div class="top-bar-left">
            <h1><i class="fa-solid fa-user-gear"></i> Manajemen Akun Penghuni</h1>
            <p>Kelola akun penghuni kos dengan mudah</p>
        </div>
        <button class="btn btn-success" onclick="openModal('tambahModal')">
            <i class="fa-solid fa-plus"></i> Tambah Akun
        </button>
    </div>

    <?php if (!empty($pesan)): 
        list($type, $msg) = explode('|', $pesan);
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
                <h3><?= $total_akun ?></h3>
                <p>Total Akun Penghuni</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $aktif ?></h3>
                <p>Akun Aktif</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div class="stat-info">
                <h3><?= $suspend ?></h3>
                <p>Akun Suspend</p>
            </div>
        </div>
    </div>

    <form class="search-bar" method="get" action="">
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="🔍 Cari username...">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Cari
        </button>
        <a href="manajemenAkun.php" class="btn btn-secondary">
            <i class="fa-solid fa-rotate"></i> Reset
        </a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): 
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= $no++ ?></strong></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><span class="badge" style="background: #e0e7ff; color: #4f46e5;"><?= ucfirst($row['role']) ?></span></td>
                        <td>
                            <?php if ($row['status'] == 'aktif'): ?>
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-circle-check"></i> Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger">
                                    <i class="fa-solid fa-circle-xmark"></i> Suspend
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openModal('editModal<?= $row['id'] ?>')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <?php if ($row['status'] == 'aktif'): ?>
                            <button onclick="confirmAction('suspend', <?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" 
                                class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                            <?php else: ?>
                            <button onclick="confirmAction('aktifkan', <?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" 
                                class="btn btn-success btn-sm">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <button onclick="confirmAction('hapus', <?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" 
                                class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- MODAL EDIT -->
                    <div class="modal" id="editModal<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5><i class="fa-solid fa-pen"></i> Edit Akun Penghuni</h5>
                                <button class="modal-close" onclick="closeModal('editModal<?= $row['id'] ?>')">×</button>
                            </div>
                            <form method="post" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password (kosongkan jika tidak diganti)</label>
                                        <input type="text" name="password" class="form-control" placeholder="Masukkan password baru">
                                        <small style="color: #f59e0b;">⚠️ Password disimpan tanpa enkripsi (plaintext)</small>
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
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            Tidak ada data penghuni ditemukan
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal" id="tambahModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5><i class="fa-solid fa-user-plus"></i> Tambah Akun Penghuni</h5>
            <button class="modal-close" onclick="closeModal('tambahModal')">×</button>
        </div>
        <form method="post" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Username <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="password" class="form-control" placeholder="Masukkan password" required>
                    <small style="color: #f59e0b;">⚠️ Password disimpan tanpa enkripsi (plaintext)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="simpan" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Simpan Akun
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('tambahModal')">
                    Batal
                </button>
            </div>
        </form>
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
        confirmTitle.textContent = 'Hapus Akun?';
        confirmMessage.innerHTML = `Apakah Anda yakin ingin menghapus akun <strong>${name}</strong>?<br><small style="color: #ef4444;">Data yang dihapus tidak dapat dikembalikan!</small>`;
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Ya, Hapus';
    } else if (type === 'suspend') {
        confirmIcon.className = 'confirm-icon warning';
        confirmIcon.innerHTML = '<i class="fa-solid fa-ban"></i>';
        confirmTitle.textContent = 'Suspend Akun?';
        confirmMessage.innerHTML = `Apakah Anda yakin ingin suspend akun <strong>${name}</strong>?<br><small style="color: #f59e0b;">Akun tidak dapat login setelah di-suspend.</small>`;
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Ya, Suspend';
    } else if (type === 'aktifkan') {
        confirmIcon.className = 'confirm-icon success';
        confirmIcon.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
        confirmTitle.textContent = 'Aktifkan Akun?';
        confirmMessage.innerHTML = `Apakah Anda yakin ingin mengaktifkan kembali akun <strong>${name}</strong>?<br><small style="color: #10b981;">Akun akan dapat login kembali setelah diaktifkan.</small>`;
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