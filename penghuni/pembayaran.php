<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Cek login
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'penghuni') {
    header("Location: ../login.php");
    exit;
}

$id_penghuni = $_GET['id'] ?? null;

// Proses Upload Bukti Pembayaran
if (isset($_POST['bayar'])) {
    $id_tagihan = $_POST['id_tagihan'];
    $metode = $_POST['metode_pembayaran'];
    
    // Upload bukti transfer
    $bukti = '';
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $target_dir = "../uploads/pembayaran/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        $new_name = "bukti_" . time() . "_" . $id_penghuni . "." . $file_ext;
        $target_file = $target_dir . $new_name;
        
        if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target_file)) {
            $bukti = $new_name;
        }
    }
    
    // Update status pembayaran
    $update = mysqli_query($koneksi, "
        UPDATE pembayaran 
        SET status='pending', 
            metode_pembayaran='$metode', 
            bukti_transfer='$bukti',
            tanggal_bayar=NOW()
        WHERE id='$id_tagihan'
    ");
    
    if ($update) {
        // Notifikasi admin
        mysqli_query($koneksi, "
            INSERT INTO notifikasi (id_pengumuman, pesan, jenis, status, tanggal)
            VALUES ('$id_penghuni', 'Pembayaran baru menunggu verifikasi', 'pembayaran', 'baru', NOW())
        ");
        
        echo "<script>
            alert('Pembayaran berhasil dikirim! Menunggu verifikasi admin.');
            window.location='pembayaran_penghuni.php';
        </script>";
    }
}

// Ambil data tagihan penghuni
$query_tagihan = mysqli_query($koneksi, "
    SELECT p.*, k.nomor_kamar, k.harga
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    JOIN kamar k ON t.kamar_id = k.id
    WHERE t.penghuni_id = '$id_penghuni'
    ORDER BY t.bulan DESC
");

// Statistik
$total_tagihan = mysqli_num_rows($query_tagihan);
$belum_lunas = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT p.*
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    AND t.status = 'belum_bayar'
"));

$pending = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT p.*
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    AND t.status = 'pending'
"));

$lunas = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT p.*
    FROM pembayaran p
    LEFT JOIN tagihan t ON p.tagihan_id = t.id
    WHERE t.penghuni_id = '$id_penghuni'
    AND t.status = 'lunas'
"));

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran Saya - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
        font-family: "Poppins", sans-serif; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 30px;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
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
    
    .top-bar h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1e1b4b;
        margin-bottom: 5px;
    }
    
    .top-bar p {
        color: #64748b;
        font-size: 14px;
    }
    
    .btn-back {
        padding: 10px 20px;
        background: #64748b;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #475569;
        transform: translateY(-2px);
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
    .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    
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
    
    .tagihan-container {
        display: grid;
        gap: 20px;
    }
    
    .tagihan-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        align-items: center;
    }
    
    .tagihan-info h3 {
        font-size: 18px;
        color: #1e1b4b;
        margin-bottom: 10px;
    }
    
    .tagihan-detail {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #64748b;
    }
    
    .detail-item i {
        color: #667eea;
    }
    
    .tagihan-amount {
        text-align: right;
    }
    
    .tagihan-amount h2 {
        font-size: 32px;
        color: #1e1b4b;
        margin-bottom: 10px;
    }
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-lunas { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-belum { background: #fee2e2; color: #dc2626; }
    
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
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
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
        padding: 20px;
    }
    
    .modal.show { display: flex; }
    
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        margin-bottom: 20px;
    }
    
    .modal-header h3 {
        font-size: 20px;
        color: #1e1b4b;
        margin: 0;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .info-box {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
        margin-bottom: 20px;
    }
    
    .info-box p {
        font-size: 13px;
        color: #475569;
        margin: 5px 0;
    }
    
    @media (max-width: 768px) {
        .tagihan-card {
            grid-template-columns: 1fr;
        }
        .tagihan-amount {
            text-align: left;
        }
        .stats-cards {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <div>
            <h1><i class="fa-solid fa-wallet"></i> Pembayaran Saya</h1>
            <p>Kelola tagihan dan riwayat pembayaran sewa kos Anda</p>
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_tagihan ?></h3>
                <p>Total Tagihan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-exclamation-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $belum_lunas ?></h3>
                <p>Belum Lunas</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $lunas ?></h3>
                <p>Sudah Lunas</p>
            </div>
        </div>
    </div>

    <div class="tagihan-container">
        <?php 
        if(mysqli_num_rows($query_tagihan) > 0):
            while($row = mysqli_fetch_assoc($query_tagihan)):
        ?>
        <div class="tagihan-card">
            <div class="tagihan-info">
                <h3><i class="fa-solid fa-calendar"></i> Tagihan <?= date('F Y', strtotime($row['periode'])) ?></h3>
                <div class="tagihan-detail">
                    <div class="detail-item">
                        <i class="fa-solid fa-door-open"></i>
                        <span>Kamar <?= $row['nomor_kamar'] ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Jatuh Tempo: <?= date('d M Y', strtotime($row['periode'])) ?></span>
                    </div>
                    <?php if($row['tanggal_bayar']): ?>
                    <div class="detail-item">
                        <i class="fa-solid fa-check"></i>
                        <span>Dibayar: <?= date('d M Y', strtotime($row['tanggal_bayar'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tagihan-amount">
                <h2>Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></h2>
                <?php if($row['status'] == 'lunas'): ?>
                    <span class="badge badge-lunas">
                        <i class="fa-solid fa-check-circle"></i> Lunas
                    </span>
                    <br><br>
                    <button class="btn btn-success" onclick="downloadKwitansi(<?= $row['id'] ?>)">
                        <i class="fa-solid fa-download"></i> Download Kwitansi
                    </button>
                <?php elseif($row['status'] == 'pending'): ?>
                    <span class="badge badge-pending">
                        <i class="fa-solid fa-clock"></i> Menunggu Verifikasi
                    </span>
                <?php else: ?>
                    <span class="badge badge-belum">
                        <i class="fa-solid fa-times-circle"></i> Belum Lunas
                    </span>
                    <br><br>
                    <button class="btn btn-primary" onclick="bayar(<?= $row['id'] ?>, <?= $row['total_bayar'] ?>)">
                        <i class="fa-solid fa-credit-card"></i> Bayar Sekarang
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div style="background: white; padding: 60px; text-align: center; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <i class="fa-solid fa-inbox" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px;"></i>
            <h3 style="color: #64748b; margin: 0;">Belum ada tagihan</h3>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Pembayaran -->
<div class="modal" id="modalPembayaran">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-credit-card"></i> Pembayaran Tagihan</h3>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_tagihan" id="id_tagihan">
            
            <div class="info-box">
                <p><strong>Total Pembayaran:</strong></p>
                <h2 style="color: #667eea; margin: 5px 0;" id="total_bayar">Rp 0</h2>
            </div>
            
            <div class="form-group">
                <label>Metode Pembayaran *</label>
                <select name="metode_pembayaran" class="form-control" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="transfer_bca">Transfer BCA</option>
                    <option value="transfer_mandiri">Transfer Mandiri</option>
                    <option value="transfer_bri">Transfer BRI</option>
                    <option value="transfer_bni">Transfer BNI</option>
                    <option value="e_wallet">E-Wallet (GoPay/OVO/Dana)</option>
                </select>
            </div>
            
            <div class="info-box">
                <p><strong><i class="fa-solid fa-building-columns"></i> Informasi Rekening:</strong></p>
                <p>Bank BCA: 1234567890 a.n SIKOPAT</p>
                <p>Bank Mandiri: 0987654321 a.n SIKOPAT</p>
                <p>GoPay/OVO/Dana: 081234567890</p>
            </div>
            
            <div class="form-group">
                <label>Upload Bukti Transfer *</label>
                <input type="file" name="bukti_transfer" class="form-control" accept="image/*" required>
                <small style="color: #64748b; font-size: 12px;">Format: JPG, PNG, PDF. Maks 2MB</small>
            </div>
            
            <div class="form-group">
                <label>Catatan (Opsional)</label>
                <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="bayar" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Pembayaran
                </button>
                <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: #64748b; color: white;">
                    <i class="fa-solid fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bayar(id, total) {
    document.getElementById('id_tagihan').value = id;
    document.getElementById('total_bayar').innerText = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('modalPembayaran').classList.add('show');
}

function closeModal() {
    document.getElementById('modalPembayaran').classList.remove('show');
}

function downloadKwitansi(id) {
    window.open('download_kwitansi.php?id=' + id, '_blank');
}

// Close modal ketika klik di luar
document.getElementById('modalPembayaran').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
</body>
</html>