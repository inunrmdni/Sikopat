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

// ========= HAPUS KAMAR =========
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Ambil data kamar sebelum dihapus
    $query_kamar = mysqli_query($koneksi, "SELECT nomor_kamar FROM kamar WHERE id='$id'");
    $kamar_data = mysqli_fetch_assoc($query_kamar);
    $nomor_kamar = $kamar_data['nomor_kamar'] ?? '';
    
    // Cek apakah kamar sedang ditempati
    $cek_penghuni = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penghuni_kamar WHERE kamar_id='$id' AND status='aktif'");
    $data_penghuni = mysqli_fetch_assoc($cek_penghuni);
    
    if ($data_penghuni['total'] > 0) {
        $pesan = "danger|❌ Kamar <strong>$nomor_kamar</strong> tidak dapat dihapus karena masih ditempati!";
    } else {
        $delete = mysqli_query($koneksi, "DELETE FROM kamar WHERE id='$id'");
        if ($delete) {
            $pesan = "success|✅ Kamar <strong>$nomor_kamar</strong> berhasil dihapus!";
        } else {
            $pesan = "danger|❌ Gagal menghapus kamar! Error: " . mysqli_error($koneksi);
        }
    }
}

// ========= SIMPAN DATA BARU =========
if (isset($_POST['simpan'])) {
    $nomor_kamar = mysqli_real_escape_string($koneksi, trim($_POST['nomor_kamar']));
    $tipe_kamar = mysqli_real_escape_string($koneksi, trim($_POST['tipe_kamar']));
    $harga = mysqli_real_escape_string($koneksi, trim($_POST['harga']));
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan']));

    // Validasi input
    if (empty($nomor_kamar)) {
        $pesan = "warning|⚠️ Nomor kamar wajib diisi!";
    } else {
        // Cek nomor kamar duplikat
        $cek = mysqli_query($koneksi, "SELECT * FROM kamar WHERE nomor_kamar='$nomor_kamar'");
        if (mysqli_num_rows($cek) > 0) {
            $pesan = "warning|⚠️ Nomor kamar <strong>$nomor_kamar</strong> sudah terdaftar!";
        } else {
            $sql = "INSERT INTO kamar (nomor_kamar, tipe_kamar, harga, status, keterangan)
                    VALUES ('$nomor_kamar', '$tipe_kamar', '$harga', '$status', '$keterangan')";
            
            $simpan = mysqli_query($koneksi, $sql);
            
            if ($simpan) {
                $pesan = "success|✅ Kamar <strong>$nomor_kamar</strong> berhasil ditambahkan!";
            } else {
                $pesan = "danger|❌ Gagal menambahkan kamar! Error: " . mysqli_error($koneksi);
            }
        }
    }
}

// ========= UPDATE DATA =========
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $nomor_kamar = mysqli_real_escape_string($koneksi, trim($_POST['nomor_kamar']));
    $tipe_kamar = mysqli_real_escape_string($koneksi, trim($_POST['tipe_kamar']));
    $harga = mysqli_real_escape_string($koneksi, trim($_POST['harga']));
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan']));

    // Cek nomor kamar duplikat (kecuali untuk kamar ini sendiri)
    $cek_nomor = mysqli_query($koneksi, "SELECT * FROM kamar WHERE nomor_kamar='$nomor_kamar' AND id != '$id'");
    if (mysqli_num_rows($cek_nomor) > 0) {
        $pesan = "warning|⚠️ Nomor kamar <strong>$nomor_kamar</strong> sudah digunakan!";
    } else {
        $update_query = "UPDATE kamar SET 
            nomor_kamar='$nomor_kamar', 
            tipe_kamar='$tipe_kamar', 
            harga='$harga', 
            status='$status', 
            keterangan='$keterangan'
            WHERE id='$id'";
        
        $update = mysqli_query($koneksi, $update_query);

        if ($update) {
            $pesan = "success|✅ Data kamar <strong>$nomor_kamar</strong> berhasil diperbarui!";
        } else {
            $pesan = "danger|❌ Gagal memperbarui data kamar! Error: " . mysqli_error($koneksi);
        }
    }
}

// ========= PENCARIAN =========
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';

$query = "SELECT * FROM kamar WHERE 1=1";
if ($cari != '') {
    $query .= " AND (nomor_kamar LIKE '%$cari%' OR tipe_kamar LIKE '%$cari%')";
}
if ($filter_status != '') {
    $query .= " AND status='$filter_status'";
}
$query .= " ORDER BY nomor_kamar ASC";
$result = mysqli_query($koneksi, $query);

// Statistik
$total_kamar = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kamar"));
$tersedia = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kamar WHERE status='tersedia'"));
$terisi = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kamar WHERE status='terisi'"));
$rusak = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kamar WHERE status='rusak'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../style.css">
<title>Manajemen Kamar - SIKOPAT</title>
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
        <li><a href="penghuni.php"><i class="fa-solid fa-users"></i> Penghuni</a></li>
        <li><a href="kamar.php" class="active"><i class="fa-solid fa-bed"></i> Kamar</a></li>
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
            <h1><i class="fa-solid fa-bed"></i> Manajemen Kamar</h1>
            <p>Kelola data kamar kos dengan mudah</p>
        </div>
        <button class="btn btn-success" onclick="openModal('tambahModal')">
            <i class="fa-solid fa-plus"></i> Tambah Kamar
        </button>
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
                <i class="fa-solid fa-door-closed"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_kamar ?></h3>
                <p>Total Kamar</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="stat-info">
                <h3><?= $tersedia ?></h3>
                <p>Kamar Tersedia</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $terisi ?></h3>
                <p>Kamar Terisi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-info">
                <h3><?= $rusak ?></h3>
                <p>Kamar Rusak</p>
            </div>
        </div>
    </div>

    <form class="search-bar" method="get" action="">
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="🔍 Cari nomor kamar atau tipe...">
        <select name="status" class="form-control" style="width: 150px;">
            <option value="">Semua Status</option>
            <option value="tersedia" <?= $filter_status == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
            <option value="terisi" <?= $filter_status == 'terisi' ? 'selected' : '' ?>>Terisi</option>
            <option value="rusak" <?= $filter_status == 'rusak' ? 'selected' : '' ?>>Rusak</option>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Cari
        </button>
        <a href="kamar.php" class="btn btn-secondary">
            <i class="fa-solid fa-rotate"></i> Reset
        </a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor Kamar</th>
                    <th>Tipe Kamar</th>
                    <th>Harga/Bulan</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): 
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= $no++ ?></strong></td>
                        <td>
                            <span class="badge" style="background: #6366f1; color: white; font-size: 14px; padding: 6px 12px;">
                                <i class="fa-solid fa-door-closed"></i> <?= htmlspecialchars($row['nomor_kamar']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['tipe_kamar']) ?></td>
                        <td><strong style="color: #10b981;">Rp <?= number_format($row['harga'], 0, ',', '.') ?></strong></td>
                        <td>
                            <?php if ($row['status'] == 'tersedia'): ?>
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-check-circle"></i> Tersedia
                                </span>
                            <?php elseif ($row['status'] == 'terisi'): ?>
                                <span class="badge" style="background: #f59e0b; color: white;">
                                    <i class="fa-solid fa-user"></i> Terisi
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger">
                                    <i class="fa-solid fa-tools"></i> Rusak
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['keterangan']) ?: '-' ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openModal('editModal<?= $row['id'] ?>')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nomor_kamar']) ?>')" 
                                class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- MODAL EDIT -->
                    <div class="modal" id="editModal<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5><i class="fa-solid fa-pen"></i> Edit Kamar</h5>
                                <button class="modal-close" onclick="closeModal('editModal<?= $row['id'] ?>')">×</button>
                            </div>
                            <form method="post" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="form-group">
                                        <label>Nomor Kamar <span style="color: #ef4444;">*</span></label>
                                        <input type="text" name="nomor_kamar" class="form-control" value="<?= htmlspecialchars($row['nomor_kamar']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipe Kamar</label>
                                        <input type="text" name="tipe_kamar" class="form-control" value="<?= htmlspecialchars($row['tipe_kamar']) ?>" placeholder="Contoh: Standar, VIP, Suite">
                                    </div>
                                    <div class="form-group">
                                        <label>Harga per Bulan</label>
                                        <input type="number" name="harga" class="form-control" value="<?= $row['harga'] ?>" placeholder="Contoh: 500000">
                                    </div>
                                    <div class="form-group">
                                        <label>Status <span style="color: #ef4444;">*</span></label>
                                        <select name="status" class="form-control" required>
                                            <option value="tersedia" <?= $row['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="terisi" <?= $row['status'] == 'terisi' ? 'selected' : '' ?>>Terisi</option>
                                            <option value="rusak" <?= $row['status'] == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Fasilitas, catatan khusus, dll"><?= htmlspecialchars($row['keterangan']) ?></textarea>
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
                            Tidak ada data kamar ditemukan
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
            <h5><i class="fa-solid fa-plus"></i> Tambah Kamar Baru</h5>
            <button class="modal-close" onclick="closeModal('tambahModal')">×</button>
        </div>
        <form method="post" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nomor Kamar <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nomor_kamar" class="form-control" required placeholder="Contoh: 101, A1, dll">
                </div>
                <div class="form-group">
                    <label>Tipe Kamar</label>
                    <input type="text" name="tipe_kamar" class="form-control" placeholder="Contoh: Standar, VIP, Suite">
                </div>
                <div class="form-group">
                    <label>Harga per Bulan</label>
                    <input type="number" name="harga" class="form-control" placeholder="Contoh: 500000">
                </div>
                <div class="form-group">
                    <label>Status <span style="color: #ef4444;">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="tersedia">Tersedia</option>
                        <option value="terisi">Terisi</option>
                        <option value="rusak">Rusak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Fasilitas: AC, WiFi, Kamar Mandi Dalam, dll"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="simpan" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Simpan Kamar
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('tambahModal')">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-header">
            <div class="confirm-icon danger">
                <i class="fa-solid fa-trash"></i>
            </div>
            <h3>Hapus Kamar?</h3>
            <p id="confirmMessage">Apakah Anda yakin ingin menghapus kamar ini?</p>
        </div>
        <div class="confirm-footer">
            <button class="btn btn-danger" id="confirmBtn" onclick="executeDelete()">
                <i class="fa-solid fa-trash"></i> Ya, Hapus
            </button>
            <button class="btn btn-secondary" onclick="closeConfirm()">
                <i class="fa-solid fa-times"></i> Batal
            </button>
        </div>
    </div>
</div>

<script>
let deleteId = '';
let deleteNomor = '';

function openModal(id) {
    document.getElementById(id).classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function confirmDelete(id, nomor) {
    deleteId = id;
    deleteNomor = nomor;
    
    document.getElementById('confirmMessage').innerHTML = 
        `Apakah Anda yakin ingin menghapus kamar <strong>${nomor}</strong>?<br><small style="color: #ef4444;">Data yang dihapus tidak dapat dikembalikan!</small>`;
    
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirm() {
    document.getElementById('confirmModal').classList.remove('show');
}

function executeDelete() {
    window.location.href = `?aksi=hapus&id=${deleteId}`;
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