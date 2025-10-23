<?php
session_start();

// Tüm oturum değişkenlerini temizle
$_SESSION = [];

// Oturumu tamamen sonlandır
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Admin token cookie'lerini temizle
setcookie('admin_access_token', '', time() - 3600, '/');
setcookie('secret_master_key', '', time() - 3600, '/');

// Son olarak session'ı yok et
session_destroy();

// Ana sayfaya yönlendir
header("Location: /index.php");
exit;
?>

