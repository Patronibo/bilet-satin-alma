<?php
// ADMIN ACCESS CONTROL - Rotating Token Sistemi
// Token oluşturmak için: /admin/generate_access_token.php

require_once __DIR__ . '/../includes/db.php';

function validateAdminAccessToken() {
    if (!isset($_COOKIE['admin_access_token'])) {
        return false;
    }
    
    $token = $_COOKIE['admin_access_token'];
    $db = getDB();
    
    // Token'ı veritabanında kontrol et
    $stmt = $db->prepare("
        SELECT id, expires_at 
        FROM Admin_Access_Tokens 
        WHERE token = ? 
        AND datetime(expires_at) > datetime('now', 'localtime')
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $tokenData !== false;
}

// Token kontrolü
if (!validateAdminAccessToken()) {
    // Yetkisiz erişim - 404 göster
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html><head><title>404 Not Found</title></head>
<body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body>
</html>';
    exit;
}

// Secure session cookie params before starting the session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => $isHttps,
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/../includes/db.php';

$error = '';

// Initialize CSRF token on first load
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// CSRF validation
	$postedToken = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
		$error = 'Geçersiz istek. Lütfen tekrar deneyin.';
	} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Lütfen e-posta ve şifre giriniz.";
    } else {
        $db = getDB();



        try {
            // Kullanıcıyı e-posta adresine göre sorgula (TABLO ADI: User)
            $stmt = $db->prepare("SELECT * FROM User WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Şifreyi kontrol et
                if (password_verify($password, $user['password'])) {
                    // Yalnızca admin kullanıcıların girişine izin ver
                    if (strtolower($user['role']) === 'admin') {
                        // Tüm session'ı temizle ve yeniden başlat
                        $_SESSION = array();
                        session_regenerate_id(true);
                        
                        // Yeni kullanıcı bilgilerini kaydet
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];

                        header('Location: /admin/panel.php');
                        exit;
                    } else {
                        $error = "Bu panel sadece admin kullanıcılar içindir.";
                    }
                } else {
                    $error = "Şifre hatalı.";
                }
            } else {
                $error = "Kullanıcı bulunamadı.";
            }
        } catch (Exception $e) {
            $error = "Bir hata oluştu: " . htmlspecialchars($e->getMessage());
        }
    }
}
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
 <!-- 🚍 Emoji favicon -->
  <link rel="icon" href="data:image/svg+xml,
  <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'>
    <text y='.9em' font-size='90'>🚍</text>
  </svg>">
<title>Yönetici Girişi</title>
<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
        background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-box {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(15px);
        padding: 2rem 2.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        width: 100%;
        max-width: 420px;
    }
    h2 {
        text-align: center;
        color: #0071e3;
        margin-bottom: 1.5rem;
        font-weight: 700;
    }
    label {
        display: block;
        margin-bottom: .5rem;
        font-weight: 600;
        color: #333;
    }
    input {
        width: 100%;
        padding: .8rem 1rem;
        border-radius: 10px;
        border: 2px solid #ddd;
        margin-bottom: 1rem;
        outline: none;
        font-size: 1rem;
    }
    input:focus {
        border-color: #0071e3;
        box-shadow: 0 0 0 3px rgba(0,113,227,0.1);
    }
    button {
        width: 100%;
        padding: .9rem;
        background: #0071e3;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: .2s;
    }
    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,113,227,0.3);
    }
    .msg {
        margin-top: 1rem;
        text-align: center;
        padding: .8rem;
        background: #fee;
        border-radius: 10px;
        color: #c33;
        font-weight: 500;
    }
</style>
</head>
<body>

<div class="login-box">
    <h2>Yönetici Girişi</h2>
    <form method="POST">
        <label for="email">E-posta</label>
        <input type="email" id="email" name="email" placeholder="E-posta" required>

        <label for="password">Şifre</label>
        <input type="password" id="password" name="password" placeholder="Şifre" required>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Giriş Yap</button>

        <?php if (!empty($error)): ?>
            <div class="msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
