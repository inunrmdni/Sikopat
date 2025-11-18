<?php
session_start();
include '../koneksi.php';

// Timeout 1 menit
$timeout_duration = 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    header("Location: ../logout.php");
    exit;
}
$_SESSION['last_activity'] = time();

// Cek login dan role
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Pilih pengguna untuk chat
$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Kirim pesan
if (isset($_POST['kirim_pesan'])) {
    $penerima_id = intval($_POST['penerima_id']);
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    if (!empty($pesan)) {
        $insert = mysqli_query($koneksi, "
            INSERT INTO chat (pengirim_id, penerima_id, pesan, created_at, status)
            VALUES ('$admin_id', '$penerima_id', '$pesan', NOW(), 'terkirim')
        ");
        
        if ($insert) {
            // Redirect untuk refresh chat
            header("Location: chat.php?user_id=$penerima_id");
            exit;
        }
    }
}

// Tandai pesan sebagai dibaca
if ($selected_user > 0) {
    mysqli_query($koneksi, "
        UPDATE chat 
        SET status='dibaca' 
        WHERE penerima_id='$admin_id' AND pengirim_id='$selected_user' AND status='terkirim'
    ");
}

// Ambil daftar pengguna yang pernah chat
$query_users = mysqli_query($koneksi, "
    SELECT DISTINCT u.id, u.username, 
           (SELECT COUNT(*) FROM chat WHERE penerima_id='$admin_id' AND pengirim_id=u.id AND status='terkirim') as unread_count,
           (SELECT pesan FROM chat WHERE (pengirim_id=u.id AND penerima_id='$admin_id') OR (pengirim_id='$admin_id' AND penerima_id=u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM chat WHERE (pengirim_id=u.id AND penerima_id='$admin_id') OR (pengirim_id='$admin_id' AND penerima_id=u.id) ORDER BY created_at DESC LIMIT 1) as last_time
    FROM users u
    WHERE u.role='penghuni' AND EXISTS (
        SELECT 1 FROM chat 
        WHERE (pengirim_id=u.id AND penerima_id='$admin_id') 
           OR (pengirim_id='$admin_id' AND penerima_id=u.id)
    )
    ORDER BY last_time DESC
");

// Ambil pesan dengan user yang dipilih
$messages = [];
if ($selected_user > 0) {
    $query_messages = mysqli_query($koneksi, "
        SELECT c.*, u.username
        FROM chat c
        LEFT JOIN users u ON c.pengirim_id = u.id
        WHERE (c.pengirim_id='$admin_id' AND c.penerima_id='$selected_user')
           OR (c.pengirim_id='$selected_user' AND c.penerima_id='$admin_id')
        ORDER BY c.created_at ASC
    ");
    while ($msg = mysqli_fetch_assoc($query_messages)) {
        $messages[] = $msg;
    }
    
    // Get selected user info
    $user_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM users WHERE id='$selected_user'"));
}

// Total pesan belum dibaca
$total_unread = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM chat WHERE penerima_id='$admin_id' AND status='terkirim'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat - SIKOPAT</title>
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
    
    .chat-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 20px;
        height: calc(100vh - 60px);
    }
    
    .user-list {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .user-list-header {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .user-list-header h3 {
        font-size: 18px;
        margin: 0;
        font-weight: 600;
    }
    
    .user-list-body {
        flex: 1;
        overflow-y: auto;
    }
    
    .user-item {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        color: #333;
    }
    
    .user-item:hover {
        background: #f8fafc;
    }
    
    .user-item.active {
        background: #e0e7ff;
        border-left: 4px solid #667eea;
    }
    
    .user-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
    
    .user-name {
        font-weight: 600;
        font-size: 14px;
        color: #1e1b4b;
    }
    
    .user-time {
        font-size: 11px;
        color: #94a3b8;
    }
    
    .user-last-message {
        font-size: 13px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .unread-badge {
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
    }
    
    .chat-box {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
    }
    
    .chat-header {
        padding: 20px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .chat-header-avatar {
        width: 45px;
        height: 45px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .chat-header-info h3 {
        font-size: 18px;
        margin: 0;
        font-weight: 600;
    }
    
    .chat-header-info p {
        font-size: 12px;
        margin: 0;
        opacity: 0.9;
    }
    
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #f8fafc;
    }
    
    .message {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
    }
    
    .message.sent {
        flex-direction: row-reverse;
    }
    
    .message-bubble {
        max-width: 60%;
        padding: 12px 16px;
        border-radius: 15px;
        word-wrap: break-word;
    }
    
    .message.received .message-bubble {
        background: white;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }
    
    .message.sent .message-bubble {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    
    .message-time {
        font-size: 11px;
        margin-top: 5px;
        opacity: 0.7;
    }
    
    .message.received .message-time {
        color: #94a3b8;
    }
    
    .message.sent .message-time {
        color: rgba(255,255,255,0.8);
        text-align: right;
    }
    
    .chat-input-form {
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
    }
    
    .chat-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 25px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        outline: none;
        transition: all 0.3s ease;
    }
    
    .chat-input:focus {
        border-color: #667eea;
    }
    
    .btn-send {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s ease;
    }
    
    .btn-send:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .empty-chat {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #94a3b8;
    }
    
    .empty-chat i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; height: auto; }
        .content { margin-left: 0; padding: 15px; }
        .chat-container { grid-template-columns: 1fr; height: auto; }
        .user-list { max-height: 300px; }
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
        <li><a href="pengumuman.php"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a></li>
        <li><a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a></li>
        <li><a href="chat.php" class="active"><i class="fa-solid fa-comments"></i> Chat</a></li>
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
    <div class="chat-container">
        <!-- User List -->
        <div class="user-list">
            <div class="user-list-header">
                <h3><i class="fa-solid fa-message"></i> Pesan (<?= $total_unread ?>)</h3>
            </div>
            <div class="user-list-body">
                <?php if (mysqli_num_rows($query_users) > 0): ?>
                    <?php while ($user = mysqli_fetch_assoc($query_users)): ?>
                        <a href="?user_id=<?= $user['id'] ?>" class="user-item <?= $user['id'] == $selected_user ? 'active' : '' ?>">
                            <div class="user-item-header">
                                <span class="user-name">
                                    <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($user['username']) ?>
                                </span>
                                <?php if ($user['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $user['unread_count'] ?></span>
                                <?php else: ?>
                                    <span class="user-time"><?= date('H:i', strtotime($user['last_time'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="user-last-message">
                                <?= htmlspecialchars(substr($user['last_message'], 0, 40)) ?><?= strlen($user['last_message']) > 40 ? '...' : '' ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #94a3b8;">
                        <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                        <p>Belum ada percakapan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Box -->
        <div class="chat-box">
            <?php if ($selected_user > 0 && isset($user_info)): ?>
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="chat-header-info">
                        <h3><?= htmlspecialchars($user_info['username']) ?></h3>
                        <p>Penghuni Kos</p>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?= $msg['pengirim_id'] == $admin_id ? 'sent' : 'received' ?>">
                            <div class="message-bubble">
                                <?= nl2br(htmlspecialchars($msg['pesan'])) ?>
                                <div class="message-time">
                                    <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                                    <?php if ($msg['pengirim_id'] == $admin_id): ?>
                                        <?= $msg['status'] == 'dibaca' ? '✓✓' : '✓' ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" class="chat-input-form">
                    <input type="hidden" name="penerima_id" value="<?= $selected_user ?>">
                    <input type="text" name="pesan" class="chat-input" placeholder="Ketik pesan..." required autofocus>
                    <button type="submit" name="kirim_pesan" class="btn-send">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fa-solid fa-comments"></i>
                    <h3>Pilih pengguna untuk memulai chat</h3>
                    <p>Pilih dari daftar pengguna di sebelah kiri</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto scroll to bottom
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto refresh setiap 5 detik
<?php if ($selected_user > 0): ?>
setInterval(function() {
    location.reload();
}, 5000);
<?php endif; ?>
</script>
</body>
</html>