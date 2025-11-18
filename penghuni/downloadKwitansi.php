<?php
session_start();
include '../koneksi.php';
require_once __DIR__ . '/../cek_login.php';

$id = $_GET['id'];

// Ambil data pembayaran
$query = mysqli_query($koneksi, "
    SELECT p.*, pk.nama_lengkap, pk.no_hp, k.nomor_kamar, k.harga_sewa
    FROM pembayaran p
    JOIN penghuni_kamar pk ON p.id_penghuni = pk.id
    JOIN kamar k ON pk.id_kamar = k.id
    WHERE p.id = '$id' AND p.status = 'lunas'
");

if (mysqli_num_rows($query) == 0) {
    die("Kwitansi tidak ditemukan atau pembayaran belum diverifikasi.");
}

$data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kwitansi Pembayaran - SIKOPAT</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
        font-family: Arial, sans-serif;
        padding: 40px;
        background: #f5f5f5;
    }
    
    .kwitansi {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .header {
        text-align: center;
        border-bottom: 3px solid #667eea;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    
    .header h1 {
        color: #667eea;
        font-size: 32px;
        margin-bottom: 5px;
    }
    
    .header p {
        color: #666;
        font-size: 14px;
    }
    
    .kwitansi-title {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .kwitansi-title h2 {
        font-size: 24px;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    
    .kwitansi-no {
        text-align: right;
        margin-bottom: 20px;
        color: #666;
        font-size: 14px;
    }
    
    .info-section {
        margin-bottom: 30px;
    }
    
    .info-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px dotted #ddd;
    }
    
    .info-label {
        width: 200px;
        font-weight: bold;
        color: #333;
    }
    
    .info-value {
        flex: 1;
        color: #666;
    }
    
    .total-section {
        background: #f8f9fa;
        padding: 20px;
        margin: 30px 0;
        border-left: 4px solid #667eea;
    }
    
    .total-section .amount {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    
    .footer {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
    }
    
    .signature {
        text-align: center;
        width: 200px;
    }
    
    .signature-line {
        border-top: 1px solid #333;
        margin-top: 80px;
        padding-top: 5px;
    }
    
    .print-btn {
        text-align: center;
        margin: 20px 0;
    }
    
    .btn {
        padding: 12px 30px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin: 0 5px;
    }
    
    .btn:hover {
        background: #5568d3;
    }
    
    .btn-secondary {
        background: #6c757d;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    @media print {
        body { padding: 0; background: white; }
        .print-btn { display: none; }
        .kwitansi { box-shadow: none; }
    }
</style>
</head>
<body>

<div class="print-btn">
    <button class="btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Kwitansi
    </button>
    <button class="btn btn-secondary" onclick="window.close()">
        <i class="fa-solid fa-times"></i> Tutup
    </button>
</div>

<div class="kwitansi">
    <div class="header">
        <h1>SIKOPAT</h1>
        <p>Sistem Informasi Kos Pintar</p>
        <p>Jl. Contoh No. 123, Kota, Provinsi | Telp: 081234567890</p>
    </div>
    
    <div class="kwitansi-title">
        <h2>KWITANSI PEMBAYARAN</h2>
    </div>
    
    <div class="kwitansi-no">
        No. Kwitansi: <strong>KWT/<?= date('Y/m', strtotime($data['periode'])) ?>/<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></strong>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Telah Terima Dari</div>
            <div class="info-value">: <?= $data['nama_lengkap'] ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">No. Telepon</div>
            <div class="info-value">: <?= $data['no_hp'] ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Nomor Kamar</div>
            <div class="info-value">: <?= $data['nomor_kamar'] ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Untuk Pembayaran</div>
            <div class="info-value">: Sewa Kos Periode <?= date('F Y', strtotime($data['periode'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Bayar</div>
            <div class="info-value">: <?= date('d F Y', strtotime($data['tanggal_bayar'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Metode Pembayaran</div>
            <div class="info-value">: <?= ucwords(str_replace('_', ' ', $data['metode_pembayaran'])) ?></div>
        </div>
    </div>
    
    <div class="total-section">
        <div style="margin-bottom: 10px;">
            <strong>Total Pembayaran:</strong>
        </div>
        <div class="amount">
            Rp <?= number_format($data['total_bayar'], 0, ',', '.') ?>
        </div>
        <div style="margin-top: 10px; color: #666; font-size: 14px;">
            <em>Terbilang: <?= terbilang($data['total_bayar']) ?> Rupiah</em>
        </div>
    </div>
    
    <div style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; margin: 20px 0;">
        <strong>Status:</strong> <span style="color: #059669;">✓ LUNAS</span>
    </div>
    
    <div class="footer">
        <div class="signature">
            <p>Penyewa</p>
            <div class="signature-line">
                <?= $data['nama_lengkap'] ?>
            </div>
        </div>
        <div class="signature">
            <p>Penerima</p>
            <div class="signature-line">
                Admin SIKOPAT
            </div>
        </div>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 12px;">
        <p>Kwitansi ini dicetak secara otomatis oleh sistem pada <?= date('d F Y H:i') ?> WIB</p>
        <p>Dokumen ini sah tanpa tanda tangan dan stempel</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>

<?php
// Fungsi terbilang
function terbilang($angka) {
    $angka = abs($angka);
    $baca = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    
    if ($angka < 12) {
        return $baca[$angka];
    } else if ($angka < 20) {
        return terbilang($angka - 10) . ' Belas';
    } else if ($angka < 100) {
        return terbilang($angka / 10) . ' Puluh ' . terbilang($angka % 10);
    } else if ($angka < 200) {
        return 'Seratus ' . terbilang($angka - 100);
    } else if ($angka < 1000) {
        return terbilang($angka / 100) . ' Ratus ' . terbilang($angka % 100);
    } else if ($angka < 2000) {
        return 'Seribu ' . terbilang($angka - 1000);
    } else if ($angka < 1000000) {
        return terbilang($angka / 1000) . ' Ribu ' . terbilang($angka % 1000);
    } else if ($angka < 1000000000) {
        return terbilang($angka / 1000000) . ' Juta ' . terbilang($angka % 1000000);
    }
    return 'Angka terlalu besar';
}
?>