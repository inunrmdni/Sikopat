<?php
session_start();
include '../koneksi.php';

// Pastikan sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

// Ambil data user (pemilik)
$stmt = mysqli_prepare($koneksi, "SELECT id, username, nama_lengkap, email, no_hp, role, created_at, foto_profil FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "Data pengguna tidak ditemukan.";
    exit;
}
$user_id = $data['id'];

// Ambil login terakhir
$stmt = mysqli_prepare($koneksi, "SELECT login_time FROM riwayat_login WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$login_row = mysqli_fetch_assoc($res);
$last_login = $login_row['login_time'] ?? 'Belum ada login';
mysqli_stmt_close($stmt);

// Ambil 5 log aktivitas terakhir
$logs = [];
$stmt = mysqli_prepare($koneksi, "SELECT waktu, aksi FROM log_aktivitas WHERE username = ? ORDER BY waktu DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $logs[] = $row;
}
mysqli_stmt_close($stmt);

// Handle form update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $nohp = trim($_POST['no_hp']);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    $upload_path = '../uploads/';
    $foto = $data['foto_profil'];

    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto_profil'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors[] = "Format file tidak didukung (jpg/png/webp).";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "Ukuran file terlalu besar (maksimal 2MB).";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'owner_' . $user_id . '_' . time() . '.' . $ext;
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $upload_path . $new_name)) {
                if ($foto !== 'default.png' && file_exists($upload_path . $foto)) {
                    unlink($upload_path . $foto);
                }
                $foto = $new_name;
            } else {
                $errors[] = "Gagal upload file.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap=?, email=?, no_hp=?, foto_profil=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $nohp, $foto, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Simpan log aktivitas
        $aksi = "Memperbarui profil pemilik";
        $stmt2 = mysqli_prepare($koneksi, "INSERT INTO log_aktivitas (username, aksi) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt2, "ss", $username, $aksi);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // Redirect dengan notifikasi sukses
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Pemilik Kos - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;color:#333}
.sidebar{width:260px;background:linear-gradient(180deg,#1e1b4b 0%,#312e81 100%);color:#fff;position:fixed;top:0;left:0;bottom:0;overflow-y:auto;box-shadow:4px 0 20px rgba(0,0,0,0.1)}
.sidebar-header{padding:25px 20px;background:rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.1)}
.sidebar-header h3{font-size:24px;font-weight:700;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar ul{list-style:none;padding:20px 15px}
.sidebar ul li a{display:flex;align-items:center;padding:12px 15px;border-radius:10px;color:rgba(255,255,255,0.8);text-decoration:none;font-weight:500;transition:all 0.3s}
.sidebar ul li a:hover{background:rgba(255,255,255,0.1);color:white;transform:translateX(5px)}
.sidebar ul li a.active{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white}
.logout-btn{margin-top:20px;padding:12px 15px;background:rgba(239,68,68,0.1);color:#fca5a5;border-radius:10px;display:flex;align-items:center;text-decoration:none}
.content{margin-left:260px;padding:30px;min-height:100vh}
.top-bar{background:white;padding:20px 30px;border-radius:15px;margin-bottom:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.top-bar h1{font-size:28px;font-weight:700;color:#1e1b4b}
.profile-card{background:white;border-radius:15px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:900px;margin:auto}
.profile-grid{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
.profile-photo{display:flex;flex-direction:column;align-items:center;gap:12px}
.profile-photo img{width:160px;height:160px;border-radius:12px;object-fit:cover;border:4px solid #f1f5f9}
.field{margin:8px 0;display:flex;gap:12px;align-items:center}
.field label{width:160px;font-weight:600;color:#374151}
.field input[readonly]{background:#f8fafc;border:1px solid #e6eef7;padding:10px;border-radius:8px;width:100%}
.small-muted{font-size:13px;color:#64748b}
.btn{padding:10px 16px;border-radius:10px;border:none;cursor:pointer}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white}
.btn-outline{background:white;border:1px solid #cbd5e1;color:#374151}
.log-list{margin-top:18px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e6eef7}
.log-item{font-size:14px;padding:6px 0;border-bottom:1px dashed #e2e8f0}
.log-item:last-child{border-bottom:none;color:#374151}
@media (max-width:768px){.sidebar{width:100%;position:relative;height:auto}.content{margin-left:0}}
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
        <li><a href="kamar.php"><i class="fa-solid fa-bed"></i> Kamar</a></li>
        <li><a href="penghuni.php"><i class="fa-solid fa-users"></i> Penghuni</a></li>
        <li><a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
        <li><a href="laporan.php"><i class="fa-solid fa-chart-line"></i> Laporan</a></li>
        <li><a href="profil.php" class="active"><i class="fa-solid fa-user"></i> Profil Pemilik</a></li>
        <li><a href="../logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="content">
    <div class="top-bar">
        <h1><i class="fa-solid fa-user"></i> Profil Pemilik Kos</h1>
        <p>Detail akun pemilik kos</p>
    </div>

    <div class="profile-card">
        <?php if (!empty($errors)): ?>
        <div style="background:#fee2e2;padding:10px;border-radius:8px;color:#991b1b;margin-bottom:10px">
            <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Profil kamu berhasil diperbarui!',
            showConfirmButton: false,
            timer: 2000
        });
        </script>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="profileForm">
            <div class="profile-grid">
                <div class="profile-photo">
                    <?php $foto_src = '../uploads/' . ($data['foto_profil'] ?: 'default.png'); ?>
                    <img id="previewFoto" src="<?= htmlspecialchars($foto_src) ?>" alt="Foto Profil">
                    <div class="small-muted">Foto profil</div>
                    <input type="file" name="foto_profil" id="foto_profil" accept="image/*" disabled>
                </div>

                <div>
                    <div class="field"><label>Username:</label><input type="text" value="<?= htmlspecialchars($data['username']) ?>" readonly></div>
                    <div class="field"><label>Nama Lengkap:</label><input type="text" name="nama_lengkap" id="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" readonly></div>
                    <div class="field"><label>Email:</label><input type="text" name="email" id="email" value="<?= htmlspecialchars($data['email']) ?>" readonly></div>
                    <div class="field"><label>No. HP:</label><input type="text" name="no_hp" id="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>" readonly></div>
                    <div class="field"><label>Jabatan:</label><input type="text" value="<?= htmlspecialchars($data['role']) ?>" readonly></div>
                    <div class="field"><label>Dibuat pada:</label><input type="text" value="<?= htmlspecialchars($data['created_at']) ?>" readonly></div>
                    <div class="field"><label>Login terakhir:</label><input type="text" value="<?= htmlspecialchars($last_login) ?>" readonly></div>

                    <div style="margin-top:16px;display:flex;gap:10px">
                        <button type="button" id="btnEdit" class="btn btn-outline"><i class="fa fa-edit"></i> Edit Profil</button>
                        <button type="submit" name="save_profile" id="btnSave" class="btn btn-primary" style="display:none"><i class="fa fa-save"></i> Simpan</button>
                        <button type="button" id="btnCancel" class="btn btn-outline" style="display:none"><i class="fa fa-times"></i> Batal</button>
                        <a href="dashboard.php" class="btn btn-outline" style="margin-left:auto"><i class="fa fa-arrow-left"></i> Kembali</a>
                    </div>

                    <h3 style="margin-top:20px;font-size:16px;color:#1e1b4b">Log Aktivitas Terakhir</h3>
                    <div class="log-list">
                        <?php if (empty($logs)): ?>
                            <div class="log-item">Belum ada aktivitas.</div>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <div class="log-item"><?= htmlspecialchars($l['waktu']) ?> — <?= htmlspecialchars($l['aksi']) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const btnEdit=document.getElementById('btnEdit');
const btnSave=document.getElementById('btnSave');
const btnCancel=document.getElementById('btnCancel');
btnEdit.addEventListener('click',()=>{
    document.getElementById('nama_lengkap').readOnly=false;
    document.getElementById('email').readOnly=false;
    document.getElementById('no_hp').readOnly=false;
    document.getElementById('foto_profil').disabled=false;
    btnEdit.style.display='none';btnSave.style.display='';btnCancel.style.display='';
});
btnCancel.addEventListener('click',()=>location.reload());
document.getElementById('foto_profil').addEventListener('change',e=>{
    const f=e.target.files[0];if(!f)return;
    document.getElementById('previewFoto').src=URL.createObjectURL(f);
});
</script>

</body>
</html>
