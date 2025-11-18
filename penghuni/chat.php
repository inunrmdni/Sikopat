<?php
session_start();
include '../koneksi.php';
include '../cek_login.php';

// Cek login dan role
require_once __DIR__ . '/../cek_login.php';
if ($_SESSION['role'] !== 'penghuni') {
    header("Location: ../login.php");
    exit;
}

$penghuni_id = $_SESSION['user_id'];

// Cari admin (role admin atau pemilik)
$admin_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id, username FROM users WHERE role IN ('admin', 'pemilik') LIMIT 1"));
$admin_id = $admin_data['id'] ?? 0;

// Kirim pesan ke admin
if (isset($_POST['kirim_pesan'])) {
    $pesan = mysqli_real_escape_string($koneksi, trim($_POST['pesan']));
    
    if (!empty($pesan) && $admin_id > 0) {
        $insert = mysqli_query($koneksi, "
            INSERT INTO chat (pengirim_id, penerima_id, pesan, created_at, status)
            VALUES ('$penghuni_id', '$admin_id', '$pesan', NOW(), 'terkirim')
        ");
        
        if ($insert) {
            header("Location: chat.php");
            exit;
        }
    }
}

// Tandai pesan dari admin sebagai dibaca
mysqli_query($koneksi, "
    UPDATE chat 
    SET status='dibaca' 
    WHERE penerima_id='$penghuni_id' AND pengirim_id='$admin_id' AND status='terkirim'
");

// Ambil semua pesan dengan admin
$messages = [];
if ($admin_id > 0) {
    $query_messages = mysqli_query($koneksi, "
        SELECT c.*, u.username
        FROM chat c
        LEFT JOIN users u ON c.pengirim_id = u.id
        WHERE (c.pengirim_id='$penghuni_id' AND c.penerima_id='$admin_id')
           OR (c.pengirim_id='$admin_id' AND c.penerima_id='$penghuni_id')
        ORDER BY c.created_at ASC
    ");
    while ($msg = mysqli_fetch_assoc($query_messages)) {
        $messages[] = $msg;
    }
}

// Total pesan belum dibaca
$total_unread = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM chat WHERE penerima_id='$penghuni_id' AND status='terkirim'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat dengan Admin - SIKOPAT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
        font-family: "Poppins", sans-serif; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .chat-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 40px);
    }
    
    .chat-header {
        padding: 20px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .chat-header-avatar {
        width: 50px;
        height: 50px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
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
    
    .btn-back {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: rgba(255,255,255,0.3);
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
        max-width: 70%;
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
    
    .message-text {
        line-height: 1.6;
        font-size: 14px;
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
        color: rgba(255,255,255,0.9);
        text-align: right;
    }
    
    .chat-input-form {
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
        background: white;
        border-radius: 0 0 15px 15px;
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
    
    .date-divider {
        text-align: center;
        margin: 20px 0;
        position: relative;
    }
    
    .date-divider span {
        background: #f8fafc;
        padding: 5px 15px;
        border-radius: 15px;
        font-size: 12px;
        color: #64748b;
        position: relative;
        z-index: 1;
    }
    
    .date-divider::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: #e2e8f0;
    }
    
    @media (max-width: 768px) {
        body { padding: 0; }
        .chat-container { height: 100vh; border-radius: 0; }
        .chat-header { border-radius: 0; }
        .chat-input-form { border-radius: 0; }
    }
</style>
</head>
<body>

<div class="container">
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-left">
                <div class="chat-header-avatar">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="chat-header-info">
                    <h3><?= htmlspecialchars($admin_data['username'] ?? 'Admin') ?></h3>
                    <p>Admin Kos • <?= $total_unread > 0 ? "$total_unread pesan baru" : "Online" ?></p>
                </div>
            </div>
            <a href="dashboard.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <?php if (count($messages) > 0): ?>
                <?php 
                $last_date = '';
                foreach ($messages as $msg): 
                    $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                    if ($msg_date != $last_date):
                        $last_date = $msg_date;
                        $date_label = $msg_date == date('Y-m-d') ? 'Hari Ini' : date('d F Y', strtotime($msg_date));
                ?>
                <div class="date-divider">
                    <span><?= $date_label ?></span>
                </div>
                <?php endif; ?>
                
                <div class="message <?= $msg['pengirim_id'] == $penghuni_id ? 'sent' : 'received' ?>">
                    <div class="message-bubble">
                        <div class="message-text">
                            <?= nl2br(htmlspecialchars($msg['pesan'])) ?>
                        </div>
                        <div class="message-time">
                            <?= date('H:i', strtotime($msg['created_at'])) ?>
                            <?php if ($msg['pengirim_id'] == $penghuni_id): ?>
                                <?= $msg['status'] == 'dibaca' ? '✓✓' : '✓' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fa-solid fa-comments"></i>
                    <h3>Belum ada percakapan</h3>
                    <p>Kirim pesan pertama Anda ke admin</p>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" class="chat-input-form">
            <input type="text" name="pesan" class="chat-input" placeholder="Ketik pesan Anda di sini..." required autofocus>
            <button type="submit" name="kirim_pesan" class="btn-send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Auto scroll to bottom
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto refresh setiap 5 detik
setInterval(function() {
    location.reload();
}, 5000);

// Prevent scroll jump saat reload
window.addEventListener('beforeunload', function() {
    sessionStorage.setItem('scrollPos', chatMessages.scrollTop);
});

window.addEventListener('load', function() {
    const scrollPos = sessionStorage.getItem('scrollPos');
    if (scrollPos) {
        chatMessages.scrollTop = scrollPos;
        sessionStorage.removeItem('scrollPos');
    }
});
</script>
</body>
</html>