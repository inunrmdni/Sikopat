<?php
require_once '../koneksi.php';
require_once '../cek_login.php';

$pengumuman_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pengumuman_id <= 0) {
    echo '<div class="no-komentar">Tidak ada komentar.</div>';
    exit;
}

$q = mysqli_prepare($koneksi, "
    SELECT k.*, u.username
    FROM komentar_pengumuman k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.pengumuman_id = ?
    ORDER BY k.created_at DESC
");
mysqli_stmt_bind_param($q, "i", $pengumuman_id);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);

if (mysqli_num_rows($res) === 0) {
    echo '<div class="no-komentar">
            <i class="fa-regular fa-comment-dots"></i>
            Belum ada komentar. Jadilah yang pertama berkomentar!
          </div>';
    exit;
}

while ($k = mysqli_fetch_assoc($res)) {
    $username = htmlspecialchars($k['username'] ?? 'Penghuni');
    $created = date('d/m/Y H:i', strtotime($k['created_at']));
    $text = nl2br(htmlspecialchars($k['komentar']));
    echo '<div class="komentar-item">';
    echo '  <div class="komentar-header">';
    echo "    <span class=\"komentar-user\"><i class=\"fa-solid fa-user-circle\"></i> {$username}</span>";
    echo "    <span class=\"komentar-time\"><i class=\"fa-regular fa-clock\"></i> {$created}</span>";
    echo '  </div>';
    echo "  <div class=\"komentar-text\">{$text}</div>";
    echo '</div>';
}
