<?php
session_start();
require_once '../koneksi.php';
require_once '../cek_login.php';

// hanya penghuni yang boleh komentar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penghuni') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$user_id = intval($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Penghuni';

$pengumuman_id = isset($_POST['pengumuman_id']) ? intval($_POST['pengumuman_id']) : 0;
$komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';

if ($pengumuman_id <= 0 || $komentar === '') {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

// simpan komentar menggunakan prepared statement
$stmt = mysqli_prepare($koneksi, "INSERT INTO komentar_pengumuman (pengumuman_id, user_id, komentar, created_at) VALUES (?, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, "iis", $pengumuman_id, $user_id, $komentar);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan komentar: ' . mysqli_error($koneksi)]);
    exit;
}

// id komentar yang baru
$id_komentar = mysqli_insert_id($koneksi);

// buat notifikasi ke admin (simpan ke tabel notifikasi)
$pesan = "💬 {$username} mengomentari pengumuman ID {$pengumuman_id}";
$nt = mysqli_prepare($koneksi, "INSERT INTO notifikasi (id_pengumuman, pesan, tanggal, status, jenis) VALUES (?, ?, NOW(), 'baru', 'pengumuman')");
mysqli_stmt_bind_param($nt, "is", $pengumuman_id, $pesan);
mysqli_stmt_execute($nt);
mysqli_stmt_close($nt);

// ambil data komentar yang baru (username & created_at)
$q = mysqli_prepare($koneksi, "
    SELECT k.komentar, k.created_at, u.username
    FROM komentar_pengumuman k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($q, "i", $id_komentar);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$record = mysqli_fetch_assoc($res);
mysqli_stmt_close($q);

if (!$record) {
    echo json_encode(['success' => true, 'message' => 'Komentar disimpan, namun gagal mengambil data.']);
    exit;
}

// kirim balik data komentar (JSON)
echo json_encode([
    'success' => true,
    'data' => [
        'id' => $id_komentar,
        'username' => $record['username'],
        'komentar' => $record['komentar'],
        'created_at' => $record['created_at']
    ]
]);
exit;
