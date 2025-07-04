<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hapus cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login, dengan menyertakan nama folder proyek

header("Location: http://localhost/20230140220_UAS_PDW/login.php");
exit();
?>