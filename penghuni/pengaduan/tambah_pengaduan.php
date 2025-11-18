<?php
session_start();
include '../../koneksi.php';

// Timeout 1 menit
$timeout_duration = 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    header("Location: ../../logout.php");
    exit;
}
$_SESSION['last_activity'] = time();

// Cek login dan role
require_once __DIR__ . '/../../cek_login.php';
if ($_SESSION['role'] !== 'penghuni') {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_penghuni = $_SESSION['id_penghuni'] ?? $_SESSION['user_id'];

if (isset($_POST['kirim'])) {
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi']);
    $anonim = isset($_POST['anonim']) ? 1 : 0;
    $gambar = null;

    // === Upload gambar ===
    if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../../uploads/pengaduan/";
        
        // Pastikan folder ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Validasi ukuran file (max 2MB)
        if ($_FILES['gambar']['size'] <= 2097152) {
            // Validasi ekstensi
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($ext, $allowed)) {
                // Generate nama file unik
                $nama_file = 'pengaduan_' . time() . '_' . uniqid() . '.' . $ext;
                $target_file = $target_dir . $nama_file;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    $gambar = $nama_file;
                }
            }
        }
    }

    // Simpan pengaduan ke database
    $query = mysqli_query($koneksi, "
        INSERT INTO pengaduan (user_id, isi, gambar, anonim, status, created_at)
        VALUES ('$user_id', '$isi', " . ($gambar ? "'$gambar'" : "NULL") . ", '$anonim', 'baru', NOW())
    ");

    if ($query) {
        $id_pengaduan = mysqli_insert_id($koneksi);
        
        // Buat pesan notifikasi
        if ($anonim) {
            $pesan = "💬 Pengaduan anonim baru telah diterima dan menunggu ditinjau.";
        } else {
            $nama = mysqli_real_escape_string($koneksi, $username);
            $pesan = "💬 Pengaduan baru dari <b>$nama</b> telah diterima dan menunggu ditinjau.";
        }

        // Kirim notifikasi ke admin
        $notif_query = mysqli_query($koneksi, "
            INSERT INTO notifikasi (id_pengumuman, pesan, jenis, status, tanggal)
            VALUES ('$id_pengaduan', '$pesan', 'pengaduan', 'baru', NOW())
        ");

        if ($notif_query) {
            echo "<script>
                alert('✅ Pengaduan berhasil dikirim!\\n\\nPengaduan Anda akan ditinjau oleh admin dalam waktu 1x24 jam. Anda akan mendapatkan notifikasi ketika ada pembaruan.');
                window.location='../pengaduan_penghuni.php';
            </script>";
        } else {
            echo "<script>
                alert('Pengaduan tersimpan tetapi notifikasi gagal dikirim.');
                window.location='../pengaduan_penghuni.php';
            </script>";
        }
    } else {
        echo "<script>
            alert('❌ Gagal mengirim pengaduan. Error: " . mysqli_error($koneksi) . "');
            window.history.back();
        </script>";
    }
}

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
<title>Tambah Pengaduan - SIKOPAT</title>
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
    
    .btn-secondary {
        background: #64748b;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #475569;
    }
    
    .form-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        max-width: 800px;
    }
    
    .form-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .form-icon {
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
    
    .form-header-text h3 {
        font-size: 20px;
        color: #1e1b4b;
        margin: 0;
        font-weight: 600;
    }
    
    .form-header-text p {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
    }
    
    .form-group label i {
        margin-right: 5px;
        color: #667eea;
    }
    
    .required {
        color: #ef4444;
        margin-left: 3px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        resize: vertical;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-wrapper input[type="file"] {
        position: absolute;
        left: -9999px;
    }
    
    .file-input-label {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .file-input-label:hover {
        background: #f1f5f9;
        border-color: #667eea;
    }
    
    .file-input-label i {
        font-size: 24px;
        color: #667eea;
    }
    
    .file-input-text {
        flex: 1;
    }
    
    .file-input-text strong {
        display: block;
        color: #1e1b4b;
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .file-input-text small {
        color: #64748b;
        font-size: 12px;
    }
    
    .file-preview {
        margin-top: 10px;
        display: none;
    }
    
    .file-preview img {
        max-width: 200px;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
    }
    
    .checkbox-wrapper {
        background: #fef3c7;
        padding: 15px;
        border-radius: 10px;
        border-left: 4px solid #f59e0b;
    }
    
    .checkbox-wrapper input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        margin-right: 10px;
    }
    
    .checkbox-wrapper label {
        display: flex;
        align-items: center;
        cursor: pointer;
        margin: 0;
        font-size: 14px;
        color: #92400e;
    }
    
    .info-box {
        background: #dbeafe;
        padding: 15px;
        border-radius: 10px;
        border-left: 4px solid #3b82f6;
        margin-bottom: 20px;
    }
    
    .info-box p {
        margin: 0;
        font-size: 13px;
        color: #1e40af;
        line-height: 1.6;
    }
    
    .info-box i {
        margin-right: 8px;
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        flex: 1;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; }
        .btn-group { flex-direction: column; }
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
        <li><a href="../dashboard_penghuni.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a href="../pengumuman_penghuni.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="../tagihan_penghuni.php"><i class="fa-solid fa-file-invoice"></i> Tagihan Saya</a></li>
        <li><a href="../pembayaran_penghuni.php"><i class="fa-solid fa-wallet"></i> Pembayaran</a></li>
        <li><a href="../chat_penghuni.php"><i class="fa-solid fa-comments"></i> Chat Admin</a></li>
        <li><a href="../pengaduan_penghuni.php" class="active"><i class="fa-solid fa-triangle-exclamation"></i> Pengaduan</a></li>
        <li><a href="../profil_penghuni.php"><i class="fa-solid fa-user-circle"></i> Profil Saya</a></li>
        <li>
            <a href="../../logout.php" onclick="return confirm('Yakin ingin logout?')" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <div class="top-bar-left">
            <h1><i class="fa-solid fa-plus-circle"></i> Tambah Pengaduan</h1>
            <p>Sampaikan keluhan atau masalah yang Anda alami</p>
        </div>
        <a href="../pengaduan_penghuni.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="form-container">
        <div class="form-header">
            <div class="form-icon">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <div class="form-header-text">
                <h3>Form Pengaduan</h3>
                <p>Isi formulir di bawah ini untuk mengajukan pengaduan</p>
            </div>
        </div>

        <div class="info-box">
            <p>
                <i class="fa-solid fa-info-circle"></i>
                <strong>Informasi:</strong> Pengaduan Anda akan ditinjau oleh admin dalam waktu 1x24 jam. 
                Anda akan mendapatkan notifikasi ketika pengaduan Anda diproses atau selesai.
            </p>
        </div>

        <form method="POST" enctype="multipart/form-data" id="pengaduanForm">
            <div class="form-group">
                <label>
                    <i class="fa-solid fa-message"></i>
                    Isi Pengaduan<span class="required">*</span>
                </label>
                <textarea 
                    name="isi" 
                    class="form-control" 
                    rows="6" 
                    placeholder="Jelaskan masalah atau keluhan Anda secara detail..."
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label>
                    <i class="fa-solid fa-image"></i>
                    Upload Gambar/Bukti (Opsional)
                </label>
                <div class="file-input-wrapper">
                    <input 
                        type="file" 
                        name="gambar" 
                        id="fileInput"
                        accept=".jpg,.jpeg,.png,.gif"
                        onchange="previewImage(this)"
                    >
                    <label for="fileInput" class="file-input-label">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <div class="file-input-text">
                            <strong>Klik untuk upload gambar</strong>
                            <small>Format: JPG, PNG, GIF (Maks. 2MB)</small>
                        </div>
                    </label>
                </div>
                <div class="file-preview" id="imagePreview"></div>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <label>
                        <input type="checkbox" name="anonim" id="anonim">
                        <span>
                            <i class="fa-solid fa-user-secret"></i>
                            <strong>Kirim sebagai Anonim</strong>
                            <small style="display: block; margin-left: 28px; margin-top: 3px;">
                                Identitas Anda akan disembunyikan dari admin
                            </small>
                        </span>
                    </label>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" name="kirim" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Pengaduan
                </button>
                <a href="../pengaduan_penghuni.php" class="btn btn-secondary">
                    <i class="fa-solid fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <p style="margin-top: 10px; font-size: 13px; color: #64748b;">
                    <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                    Gambar berhasil dipilih: ${input.files[0].name}
                </p>
            `;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Validasi form sebelum submit
document.getElementById('pengaduanForm').addEventListener('submit', function(e) {
    const isi = document.querySelector('textarea[name="isi"]').value.trim();
    
    if (isi.length < 10) {
        e.preventDefault();
        alert('Isi pengaduan minimal 10 karakter!');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin mengirim pengaduan ini?');
});
</script>
</body>
</html>