<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

// Giriş kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: /../firma_admin/firma_admin_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
$full_name = $_SESSION['full_name'];

// Firma bilgilerini çek
$stmt = $db->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT full_name, email FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Şifre güncelleme işlemi
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = trim($_POST['new_password']);
    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $message = "Şifre başarıyla güncellendi.";
    } else {
        $message = "Lütfen geçerli bir şifre girin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma Admin Profili</title>
<style>
:root {
    --bg: #f4f6f8;
    --dark: #1d1d1f;
    --accent: #0071e3;
    --accent-hover: #005bb5;
    --danger: #dc3545;
    --card: #ffffff;
}
* {
    box-sizing: border-box;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
    padding: 0;
}
body {
    display: flex;
    height: 100vh;
    background: var(--bg);
    color: var(--dark);
}

/* ==== SIDEBAR ==== */
.sidebar {
    width: 260px;
    background: white;
    border-right: 1px solid rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    padding: 25px;
}
.sidebar h2 {
    color: var(--accent);
    font-size: 1.5rem;
    margin-bottom: 25px;
    text-align: center;
}
.tab-button {
    border: none;
    background: #f1f3f5;
    color: var(--dark);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
    font-weight: 500;
    text-decoration: none;
    display: block;
}
.tab-button:hover {
    background: var(--accent);
    color: #fff;
}

/* ==== MAIN ==== */
.main-content {
    flex: 1;
    overflow-y: auto;
    padding: 30px 40px;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
header h1 {
    font-size: 1.6rem;
    font-weight: 600;
}
header button {
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
}
header button:hover {
    background: var(--accent-hover);
}

/* ==== CARD ==== */
.card {
    background: var(--card);
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
    max-width: 600px;
}
.card h2 {
    margin-bottom: 15px;
    color: var(--accent);
}
.info {
    margin-bottom: 10px;
    font-size: 1rem;
}
.info strong {
    color: var(--accent);
}

/* ==== FORM ==== */
form {
    margin-top: 20px;
}
input[type="password"] {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-bottom: 10px;
}
button[type="submit"] {
    background: var(--accent);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
button[type="submit"]:hover {
    background: var(--accent-hover);
}
.message {
    margin-top: 10px;
    font-weight: 600;
    color: green;
}
</style>
</head>
<body>
    <div class="sidebar">
        <h2>🚌 Firma Paneli</h2>
        <a class="tab-button" href="panel.php">Seferler</a>
        <a class="tab-button" href="sefer_ekle.php">Yeni Sefer Ekle</a>
        <a class="tab-button" href="kupon_yonetimi.php">Kupon Yönetimi</a>
        <a class="tab-button" href="firma_admin_profil.php">Profil</a>
        <a class="tab-button" href="/firma_admin/logout.php">Çıkış Yap</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Profil Bilgilerin</h1>
            <button onclick="location.reload()">🔄 Yenile</button>
        </header>

        <div class="card">
            <h2>👤 Kişisel Bilgiler</h2>
            <p class="info"><strong>Ad Soyad:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
            <p class="info"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p class="info"><strong>Bağlı Olduğu Firma:</strong> <?= htmlspecialchars($company['name']) ?></p>

            <h2 style="margin-top:25px;">🔐 Şifre Güncelle</h2>
            <form method="POST">
                <label for="current_password">Eski Şifre</label>
                <input type="password" id="current_password" name="current_password" placeholder="Eski şifrenizi girin" required style="margin-bottom:10px; padding:8px; width:100%; border-radius:8px; border:1px solid #ccc;">

                <label for="new_password">Yeni Şifre</label>
                <input type="password" id="new_password" name="new_password" placeholder="Yeni şifrenizi girin" required style="margin-bottom:10px; padding:8px; width:100%; border-radius:8px; border:1px solid #ccc;">

                <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Yeni şifrenizi tekrar girin" required style="margin-bottom:15px; padding:8px; width:100%; border-radius:8px; border:1px solid #ccc;">

                <button type="submit" name="update_password" class="action-btn">Şifreyi Güncelle</button>
            </form>
            <?php if (!empty($message)): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
