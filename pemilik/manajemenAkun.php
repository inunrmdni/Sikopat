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
<title>Manajemen Akun - SIKOPAT</title>
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
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .btn-secondary {
        background: #64748b;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    
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
    
    .search-bar {
        background: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: flex;
        gap: 10px;
    }
    
    .search-bar input {
        flex: 1;
        padding: 10px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .search-bar input:focus {
        outline: none;
        border-color: #667eea;
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
    
    .badge-success { background: #d1fae5; color: #059669; }
    .badge-danger { background: #fee2e2; color: #dc2626; }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-success {
        background: #d1fae5;
        color: #059669;
        border-left: 4px solid #10b981;
    }
    
    .alert-warning {
        background: #fef3c7;
        color: #d97706;
        border-left: 4px solid #f59e0b;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #dc2626;
        border-left: 4px solid #ef4444;
    }
    
    .alert i {
        font-size: 20px;
    }
    
    /* Modal */
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
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1e1b4b;
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #94a3b8;
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: #f1f5f9;
        color: #1e1b4b;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
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
    
    /* Confirmation Modal */
    .confirm-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .confirm-modal.show { display: flex; }
    
    .confirm-box {
        background: white;
        border-radius: 15px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .confirm-header {
        padding: 25px;
        text-align: center;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .confirm-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }
    
    .confirm-icon.warning {
        background: #fef3c7;
        color: #f59e0b;
    }
    
    .confirm-icon.danger {
        background: #fee2e2;
        color: #ef4444;
    }
    
    .confirm-icon.success {
        background: #d1fae5;
        color: #10b981;
    }
    
    .confirm-header h3 {
        font-size: 20px;
        color: #1e1b4b;
        margin: 0 0 10px 0;
        font-weight: 600;
    }
    
    .confirm-header p {
        font-size: 14px;
        color: #64748b;
        margin: 0;
        line-height: 1.6;
    }
    
    .confirm-footer {
        padding: 20px 25px;
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; }
        .stats-cards { grid-template-columns: 1fr; }
        .search-bar { flex-direction: column; }
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