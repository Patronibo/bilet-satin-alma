<?php
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
require_once __DIR__ . '/../includes/email.php';
$db = getDB();

$message = '';
$step = $_SESSION['2fa_step'] ?? 'email'; // 'email' or '2fa'

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
        $message = 'Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
        
        // STEP 1: Email + Password
        if ($step === 'email' && isset($_POST['email'], $_POST['password'])) {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if ($email && $password) {
                // Kullanıcıyı bul
                $stmt = $db->prepare("SELECT * FROM User WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Rol kontrolü
                    if (strtolower($user['role']) !== 'company') {
                        $message = "Bu panel sadece firma yöneticileri içindir.";
                    } else {
                        // Rate limit kontrol
                        if (!check2FARateLimit($user['id'])) {
                            $message = "Çok fazla deneme yaptınız. Lütfen 1 saat sonra tekrar deneyin.";
                        } else {
                            // 2FA kodu oluştur ve gönder
                            $code = generate2FACode();
                            store2FACode($user['id'], $code);
                            
                            // Email gönder
                            if (send2FACode($email, $code, $user['full_name'])) {
                                // Kullanıcı bilgilerini session'da geçici sakla
                                $_SESSION['2fa_user_id'] = $user['id'];
                                $_SESSION['2fa_user_data'] = [
                                    'email' => $user['email'],
                                    'full_name' => $user['full_name'],
                                    'role' => $user['role'],
                                    'company_id' => $user['company_id']
                                ];
                                $_SESSION['2fa_step'] = '2fa';
                                $step = '2fa';
                                $message = "✅ Doğrulama kodu email adresinize gönderildi!";
                            } else {
                                $message = "Email gönderilemedi. Lütfen tekrar deneyin.";
                            }
                        }
                    }
                } else {
                    $message = "E-posta veya şifre hatalı!";
                }
            } else {
                $message = "Lütfen tüm alanları doldurun!";
            }
        }
        
        // STEP 2: 2FA Code Verification
        elseif ($step === '2fa' && isset($_POST['code'])) {
            $code = trim($_POST['code']);
            $userId = $_SESSION['2fa_user_id'] ?? null;
            
            if ($userId && $code) {
                if (verify2FACode($userId, $code)) {
                    // Başarılı! Session'ı temizle ve kullanıcıyı giriş yap
                    $userData = $_SESSION['2fa_user_data'];
                    
                    // Tüm 2FA verilerini temizle
                    unset($_SESSION['2fa_step']);
                    unset($_SESSION['2fa_user_id']);
                    unset($_SESSION['2fa_user_data']);
                    
                    // Session'ı yeniden başlat
                    session_regenerate_id(true);
                    
                    // Kullanıcı bilgilerini kaydet
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['email'] = $userData['email'];
                    $_SESSION['full_name'] = $userData['full_name'];
                    $_SESSION['role'] = $userData['role'];
                    $_SESSION['company_id'] = $userData['company_id'];

                    header('Location: /firma_admin/panel.php');
                    exit;
                } else {
                    $message = "❌ Geçersiz veya süresi dolmuş kod!";
                }
            } else {
                $message = "Lütfen doğrulama kodunu girin!";
            }
        }
        
        // Yeni kod gönder
        elseif ($step === '2fa' && isset($_POST['resend'])) {
            $userId = $_SESSION['2fa_user_id'] ?? null;
            $userData = $_SESSION['2fa_user_data'] ?? null;
            
            if ($userId && $userData) {
                if (!check2FARateLimit($userId)) {
                    $message = "Çok fazla deneme yaptınız. Lütfen 1 saat sonra tekrar deneyin.";
                } else {
                    $code = generate2FACode();
                    store2FACode($userId, $code);
                    
                    if (send2FACode($userData['email'], $code, $userData['full_name'])) {
                        $message = "✅ Yeni kod gönderildi!";
                    } else {
                        $message = "Email gönderilemedi.";
                    }
                }
            }
        }
    }
}

// Geri dön butonu
if (isset($_GET['back']) && $_GET['back'] === '1') {
    unset($_SESSION['2fa_step']);
    unset($_SESSION['2fa_user_id']);
    unset($_SESSION['2fa_user_data']);
    $step = 'email';
    header('Location: /firma_admin/firma_admin_login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma Admin Girişi - 2FA</title>
<style>
:root {
    --apple-gray: #f5f5f7;
    --apple-dark: #1d1d1f;
    --accent: #0071e3;
    --card-bg: rgba(255,255,255,0.8);
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 40px;
    max-width: 450px;
    width: 100%;
}
.logo {
    text-align: center;
    margin-bottom: 30px;
}
.logo-icon {
    font-size: 60px;
    margin-bottom: 10px;
}
h1 {
    color: var(--apple-dark);
    font-size: 1.8rem;
    margin-bottom: 10px;
    text-align: center;
}
.subtitle {
    color: #6b7280;
    text-align: center;
    margin-bottom: 30px;
    font-size: 0.9rem;
}
.message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
}
.message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
.message.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}
.form-group {
    margin-bottom: 20px;
}
label {
    display: block;
    margin-bottom: 8px;
    color: var(--apple-dark);
    font-weight: 600;
    font-size: 0.9rem;
}
input[type="email"],
input[type="password"],
input[type="text"] {
    width: 100%;
    padding: 15px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}
input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}
.code-input {
    text-align: center;
    font-size: 1.5rem;
    letter-spacing: 0.5em;
    font-weight: bold;
    font-family: 'Courier New', monospace;
}
button[type="submit"] {
    width: 100%;
    padding: 15px;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
button[type="submit"]:hover {
    background: #005bb5;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 113, 227, 0.3);
}
.secondary-btn {
    width: 100%;
    padding: 12px;
    background: white;
    color: var(--accent);
    border: 2px solid var(--accent);
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s ease;
}
.secondary-btn:hover {
    background: var(--accent);
    color: white;
}
.info-box {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #1e40af;
}
.footer-links {
    text-align: center;
    margin-top: 20px;
    font-size: 0.85rem;
    color: #6b7280;
}
.footer-links a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
}
.footer-links a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>
<div class="container">
    <div class="logo">
        <div class="logo-icon">🚌</div>
    </div>
    
    <?php if ($step === 'email'): ?>
        <h1>Firma Admin Girişi</h1>
        <p class="subtitle">İki adımlı doğrulama ile güvenli giriş</p>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" required autofocus placeholder="ornek@firma.com">
            </div>
            
            <div class="form-group">
                <label>🔒 Şifre</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit">Devam Et →</button>
        </form>
        
    <?php else: ?>
        <h1>Doğrulama Kodu</h1>
        <p class="subtitle">Email adresinize gönderilen 6 haneli kodu girin</p>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            📧 <strong><?= htmlspecialchars($_SESSION['2fa_user_data']['email'] ?? '') ?></strong> adresine kod gönderildi.<br>
            ⏱️ Kod 10 dakika geçerlidir.
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label>🔢 Doğrulama Kodu</label>
                <input type="text" name="code" class="code-input" required autofocus 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <button type="submit">Giriş Yap ✓</button>
        </form>
        
        <form method="POST" style="margin-top: 10px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="secondary-btn">🔄 Yeni Kod Gönder</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="?back=1" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">← Geri Dön</a>
        </div>
    <?php endif; ?>
    
    <div class="footer-links">
        <a href="/index.php">← Ana Sayfaya Dön</a>
    </div>
</div>
</body>
</html>
