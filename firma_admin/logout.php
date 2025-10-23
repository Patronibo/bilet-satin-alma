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

// Son olarak session'ı yok et
session_destroy();

// Ana sayfaya yönlendir
header("Location: /index.php");
exit;
?>

