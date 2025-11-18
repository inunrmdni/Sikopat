<?php
session_start();
include '../../koneksi.php';
include '../../cek_login.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi']);
    // Pastikan session berisi ID user yang login
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    if ($created_by === null) {
        echo "<script>
            alert('User tidak dikenali. Silakan login ulang.');
            window.location.href = '../logout.php';
        </script>";
        exit;
    }
    // Simpan pengumuman ke tabel pengumuman
    $query = "INSERT INTO pengumuman (judul, isi, created_by) VALUES ('$judul', '$isi', '$created_by')";
    $simpan = mysqli_query($koneksi, $query);
    if ($simpan) {
        $id_pengumuman = mysqli_insert_id($koneksi);
        // Menambahkan ke tabel notifikasi untuk penghuni
        $pesan = "📢 Pengumuman baru: " . substr($isi, 0, 100) . "...";
        $query_notif = "INSERT INTO notifikasi (id_pengumuman, pesan) VALUES ('$id_pengumuman', '$pesan')";
        mysqli_query($koneksi, $query_notif);
        // SweetAlert popup sukses
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Pengumuman berhasil disimpan dan dikirim ke penghuni.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '../pengumuman.php';
                });
            };
        </script>";
    } else {
        // SweetAlert popup gagal
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            window.onload = function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menyimpan pengumuman.'
                });
            };
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pengumuman - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Poppins", sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; padding: 30px; }
    .container { max-width: 800px; margin: 0 auto; }
    .top-bar { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .top-bar h1 { font-size: 28px; font-weight: 700; color: #1e1b4b; margin-bottom: 5px; }
    .top-bar p { color: #64748b; font-size: 14px; margin: 0; }
    .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #1e1b4b; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: "Poppins", sans-serif; outline: none; transition: all 0.3s ease; }
    .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    textarea.form-control { resize: vertical; min-height: 120px; }
    .btn { padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
    .btn i { margin-right: 8px; }
    .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
    .btn-secondary { background: #64748b; color: white; margin-left: 10px; }
    .btn-secondary:hover { background: #475569; transform: translateY(-2px); }
    .button-group { display: flex; gap: 10px; margin-top: 30px; }
    @media (max-width: 768px) { body { padding: 15px; } .form-container { padding: 20px; } .button-group { flex-direction: column; } .btn { width: 100%; text-align: center; } }
</style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1><i class="fa-solid fa-bullhorn"></i> Tambah Pengumuman</h1>
        <p>Buat pengumuman baru untuk penghuni kos</p>
    </div>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-heading"></i> Judul Pengumuman</label>
                <input type="text" name="judul" class="form-control" required placeholder="Masukkan judul pengumuman...">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-align-left"></i> Isi Pengumuman</label>
                <textarea name="isi" class="form-control" rows="5" required placeholder="Tulis isi pengumuman di sini..."></textarea>
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Simpan Pengumuman
                </button>
                <a href="../pengumuman.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>