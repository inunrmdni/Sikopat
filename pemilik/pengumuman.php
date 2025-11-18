<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Ambil data pencarian & filter
$keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : '';

// Query dasar
$query = "SELECT * FROM pengumuman WHERE 1";

// Jika ada pencarian judul atau isi
if (!empty($keyword)) {
    $query .= " AND (judul LIKE '%$keyword%' OR isi LIKE '%$keyword%')";
}

// Jika ada filter tanggal
if (!empty($tanggal)) {
    $query .= " AND DATE(created_at) = '$tanggal'";
}

// Urutkan berdasarkan waktu terbaru
$query .= " ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);

// Hitung total pengumuman
$total_pengumuman = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Pengumuman - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    
    .stats-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
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
    
    .search-filter-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .search-filter-card h5 {
        font-size: 16px;
        font-weight: 600;
        color: #1e1b4b;
        margin-bottom: 20px;
    }
    
    .form-control {
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .input-group i {
        position: absolute;
        left: 15px;
        color: #94a3b8;
        z-index: 10;
    }
    
    .input-group .form-control {
        padding-left: 45px;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }
    
    .col-md-4 {
        flex: 1;
        min-width: 200px;
    }
    
    .col-md-3 {
        flex: 0 0 auto;
        min-width: 180px;
    }
    
    .col-md-5 {
        flex: 1;
        min-width: 200px;
    }
    
    .d-flex {
        display: flex;
    }
    
    .gap-2 {
        gap: 10px;
    }
    
    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
    }
    
    .btn-sm {
        padding: 8px 15px;
        font-size: 13px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    
    .btn-secondary {
        background: #64748b;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #475569;
        transform: translateY(-2px);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow-x: auto;
    }
    
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .table-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1e1b4b;
        margin: 0;
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
    
    thead th:first-child {
        border-radius: 10px 0 0 0;
    }
    
    thead th:last-child {
        border-radius: 0 10px 0 0;
    }
    
    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }
    
    tbody tr:hover {
        background: #f8fafc;
    }
    
    tbody td {
        padding: 15px;
        font-size: 14px;
        color: #475569;
    }
    
    .pengumuman-judul {
        font-weight: 600;
        color: #1e1b4b;
        margin-bottom: 5px;
        font-size: 15px;
    }
    
    .pengumuman-isi {
        color: #64748b;
        font-size: 13px;
        line-height: 1.6;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .sidebar { 
            width: 100%; 
            position: relative;
            height: auto;
        }
        .content { margin-left: 0; }
        .table-container { overflow-x: scroll; }
        .row { flex-direction: column; }
        .col-md-4, .col-md-3, .col-md-5 { width: 100%; }
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
        <li><a href="pengumuman.php" class="active"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
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
        <h1><i class="fa-solid fa-bullhorn"></i> Daftar Pengumuman</h1>
        <p>Kelola pengumuman untuk penghuni kos</p>
    </div>

    <div class="stats-card">
        <div class="stat-icon">
            <i class="fa-solid fa-clipboard-list"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total_pengumuman ?></h3>
            <p>Total Pengumuman</p>
        </div>
    </div>

    <!-- Form Pencarian dan Filter -->
    <div class="search-filter-card">
        <h5><i class="fa-solid fa-filter"></i> Filter & Pencarian</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari judul atau isi pengumuman..." 
                           value="<?= htmlspecialchars($keyword) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <i class="fa-solid fa-calendar"></i>
                    <input type="date" name="tanggal" class="form-control" 
                           value="<?= htmlspecialchars($tanggal) ?>">
                </div>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i> Cari
                </button>
                <a href="pengumuman.php" class="btn btn-secondary">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </a>
                <a href="../admin/pengumuman/tambah_pengumuman.php" class="btn btn-success" style="margin-left: auto;">
                    <i class="fa-solid fa-plus"></i> Tambah Pengumuman
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Pengumuman -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fa-solid fa-list"></i> Data Pengumuman</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="60px">No</th>
                    <th>Judul & Isi Pengumuman</th>
                    <th width="200px">Tanggal</th>
                    <th width="200px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            mysqli_data_seek($result, 0);
            if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
            ?>
                <tr>
                    <td><strong><?= $no++ ?></strong></td>
                    <td>
                        <div style="max-width: 600px;">
                            <?php if (!empty($row['judul'])): ?>
                                <div class="pengumuman-judul">
                                    <i class="fa-solid fa-bookmark"></i> <?= htmlspecialchars($row['judul']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="pengumuman-isi">
                                <?= nl2br(htmlspecialchars($row['isi'])) ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <i class="fa-regular fa-calendar"></i>
                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="../admin/pengumuman/edit_pengumuman.php?id=<?= $row['id'] ?>" 
                               class="btn btn-warning btn-sm">
                               <i class="fa-solid fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="hapusPengumuman(<?= $row['id'] ?>)">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </div>
                    </td>
                </tr>
            <?php 
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="fa-solid fa-inbox"></i>
                            <p>Belum ada pengumuman</p>
                        </div>
                    </td>
                </tr>
            <?php
            endif;
            ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<script>
function hapusPengumuman(id) {
    Swal.fire({
        title: "Hapus Pengumuman?",
        text: "Pengumuman yang sudah dihapus tidak bisa dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#64748b",
        confirmButtonText: '<i class="fas fa-check"></i> Ya, hapus!',
        cancelButtonText: '<i class="fas fa-times"></i> Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "../admin/pengumuman/hapus_pengumuman.php?id=" + id;
        }
    });
}

<?php if (isset($_GET['hapus']) && $_GET['hapus'] == 'berhasil'): ?>
Swal.fire({
    title: "Berhasil!",
    text: "Pengumuman telah dihapus.",
    icon: "success",
    timer: 2000,
    showConfirmButton: false,
    timerProgressBar: true
});
<?php endif; ?>
</script>
</body>
</html>