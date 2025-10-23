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

// Initialize CSRF token on first load
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ödeme sayfasından gelen hata mesajını göster
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// CSRF validation
	$postedToken = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
		$error = 'Geçersiz istek. Lütfen tekrar deneyin.';
	} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    // Tablo ismini User olarak düzelt
    $stmt = $db->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($user && password_verify($password, $user['password'])) {
		// Rol kontrolü: Sadece 'user' rolü bu sayfadan giriş yapabilir
		if (strtolower($user['role']) !== 'user') {
			$error = "Bu panel sadece müşteriler içindir. Lütfen kendi panelinizden giriş yapın.";
		} else {
			// Tüm session'ı temizle ve yeniden başlat
			$_SESSION = array();
			session_regenerate_id(true);
			
			// CSRF token'ı yeniden oluştur
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
			
			// Yeni kullanıcı bilgilerini kaydet
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['role'] = $user['role'];
			$_SESSION['name'] = $user['full_name'];
			
			header('Location: index.php');
			exit;
		}
    } else {
        $error = "Giriş başarısız";
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
    <title>Giriş Yap</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%);
            color: #1c1c1e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* 🍏 Geri butonu */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.85);
            color: #1d1d1f;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.25s ease;
            backdrop-filter: blur(8px);
            z-index: 1000;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.95);
            transform: translateY(-1px);
        }

        .container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.85);
            padding: 40px 35px;
            border-radius: 18px;
            box-shadow:
                0 10px 25px rgba(0,0,0,0.1),
                0 1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(16px);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            color: #1d1d1f;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        form label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            margin-top: 16px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1.8px solid #d2d2d7;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
            background-color: #fafafa;
        }

        input:focus {
            border-color: #0071e3; /* Apple mavi tonu */
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 24px;
            background: #0071e3; /* Apple mavi */
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            background: #0077ed;
            box-shadow: 0 8px 20px rgba(0, 113, 227, 0.3);
            transform: translateY(-2px);
        }

        .msg {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
        }

        .msg.error {
            background-color: #fff2f2;
            color: #c33;
            border: 1px solid #fcc;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .register-link a {
            color: #0071e3;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .register-link a:hover {
            color: #005bb5;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Sol üst köşedeki geri dön butonu -->
    <a href="javascript:void(0)" class="back-btn" onclick="if(document.referrer){history.back();}else{window.location='index.php';}">
        ⬅️ Geri
    </a>

    <div class="container">
        <h2>Hoş Geldiniz</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label>E-posta</label>
            <input type="email" name="email" required placeholder="ornek@email.com">
            <label>Şifre</label>
            <input type="password" name="password" required placeholder="••••••••">
            <button type="submit">Giriş Yap</button>
        </form>

        <?php if (!empty($error)) echo "<div class='msg error'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; ?>
        <?php if (!empty($success)) echo "<div class='msg success'>" . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . "</div>"; ?>

        <p class="register-link">
            Hesabınız yok mu? <a href="register.php">Hesap Oluştur</a>
        </p>
    </div>

</body>
</html>
