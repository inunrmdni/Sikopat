<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// waktu timeout (15 menit)
$timeout = 15 * 60;

// cek apakah user sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// auto logout jika tidak aktif lebih dari timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    $_SESSION = [];
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit;
}

// update waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// pastikan user_id diset
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!-- Tambahkan script untuk warning sebelum logout otomatis -->
<script>
// waktu idle maksimum (dalam milidetik) = 14 menit (beri 1 menit untuk peringatan)
const idleLimit = 14 * 60 * 1000; 
const warningTime = 60; // detik hitung mundur

let idleTimer;
let countdown;
let remainingTime = warningTime;

function resetTimer() {
    clearTimeout(idleTimer);
    clearInterval(countdown);
    document.getElementById('logout-warning')?.remove();
    idleTimer = setTimeout(showWarning, idleLimit);
}

// tampilkan peringatan 1 menit sebelum logout
function showWarning() {
    const warning = document.createElement('div');
    warning.id = 'logout-warning';
    warning.style.position = 'fixed';
    warning.style.top = '0';
    warning.style.left = '0';
    warning.style.width = '100%';
    warning.style.height = '100%';
    warning.style.background = 'rgba(0,0,0,0.5)';
    warning.style.display = 'flex';
    warning.style.justifyContent = 'center';
    warning.style.alignItems = 'center';
    warning.style.zIndex = '9999';
    warning.innerHTML = `
        <div style="background:white;padding:30px;border-radius:10px;text-align:center;max-width:400px">
            <h5>Anda akan logout otomatis dalam <span id="timer">${remainingTime}</span> detik</h5>
            <p>Klik <b>Tetap Masuk</b> untuk melanjutkan sesi Anda.</p>
            <button onclick="stayLoggedIn()" class="btn btn-primary">Tetap Masuk</button>
        </div>
    `;
    document.body.appendChild(warning);

    countdown = setInterval(() => {
        remainingTime--;
        document.getElementById('timer').textContent = remainingTime;
        if (remainingTime <= 0) {
            clearInterval(countdown);
            window.location.href = "../logout.php";
        }
    }, 1000);
}

// jika user klik "Tetap Masuk"
function stayLoggedIn() {
    resetTimer();
}

// deteksi aktivitas pengguna
['mousemove', 'keydown', 'click', 'scroll'].forEach(evt =>
    document.addEventListener(evt, resetTimer)
);

// mulai timer pertama kali
resetTimer();
</script>
