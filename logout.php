<?php
// logout.php
session_start();

// kosongkan session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_unset();
session_destroy();

// redirect ke login
header("Location: ../SIKOPAT/login.php"); // jika dijalankan dari subfolder, tapi safer:
header("Location: login.php");
exit;
?>
