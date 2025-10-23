<?php
// ADMIN ACCESS CONTROL - Rotating Token Sistemi
require_once __DIR__ . '/../includes/db.php';

function validateAdminToken() {
    if (!isset($_COOKIE['admin_access_token'])) {
        return false;
    }
    
    $token = $_COOKIE['admin_access_token'];
    $db = getDB();
    
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
if (!validateAdminToken()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html><head><title>404 Not Found</title></head>
<body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body>
</html>';
    exit;
}

session_start();
$db = getDB();

// 🔐 Sadece admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: /../src/index.php');
    exit;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

// Form gönderildiyse işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $message = '❌ Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $company_id = $_POST['company_id'] ?? '';

    if ($full_name && $email && $password && $company_id) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at) VALUES (?, ?, ?, ?, 'company', ?, CURRENT_TIMESTAMP)");
        $id = uniqid('user-', true);
        if ($stmt->execute([$id, $full_name, $email, $hash, $company_id])) {
            $message = "Firma admini başarıyla eklendi!";
        } else {
            $message = "Bir hata oluştu, tekrar deneyin.";
        }
    } else {
        $message = "Lütfen tüm alanları doldurun.";
    }
    }
}

// Firma listesi
$companies = $db->query("SELECT id, name FROM Bus_Company")->fetchAll(PDO::FETCH_ASSOC);

// Mevcut firma adminlerini çek
$admins = $db->query("
    SELECT u.id, u.full_name, u.email, b.name AS company_name, u.created_at
    FROM User u
    LEFT JOIN Bus_Company b ON u.company_id = b.id
    WHERE u.role = 'company'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Adminleri Güncelle veya Sil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $message = '❌ Geçersiz istek. Lütfen tekrar deneyin.';
    } elseif (isset($_POST['action'], $_POST['id'])) {
        $adminId = $_POST['id'];

        if ($_POST['action'] === 'update') {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $company_id = $_POST['company_id'] ?? '';

            if ($full_name && $email && $company_id) {
                $stmt = $db->prepare("UPDATE User SET full_name = ?, email = ?, company_id = ? WHERE id = ? AND role = 'company'");
                if ($stmt->execute([$full_name, $email, $company_id, $adminId])) {
                    $message = "Admin başarıyla güncellendi!";
                } else {
                    $message = "Güncelleme sırasında bir hata oluştu.";
                }
            } else {
                $message = "Tüm alanları doldurun!";
            }

        } elseif ($_POST['action'] === 'delete') {
            $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
            if ($stmt->execute([$adminId])) {
                $message = "Admin başarıyla silindi!";
            } else {
                $message = "Silme işlemi sırasında bir hata oluştu.";
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
    <title>Firma Admini Ekle</title>
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
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
    background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%);
    min-height: 100vh;
    color: var(--apple-dark);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* KARTLAR */
.card {
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: 0.3s;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
}
.card h2 {
    color: var(--accent);
    font-size: 1.4rem;
    margin-bottom: 1rem;
}
.card h3 {
    color: var(--apple-dark);
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

/* ALERTLER */
.alert {
    padding: 1rem 1.2rem;
    border-radius: 10px;
    margin-bottom: 1.2rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}
.alert-success {
    background: rgba(76, 175, 80, 0.15);
    border-left: 4px solid #4CAF50;
    color: #155724;
}
.alert-error {
    background: rgba(244, 67, 54, 0.15);
    border-left: 4px solid #f44336;
    color: #721c24;
}

/* FORM KUTULARI */
.form-box {
    background: var(--card-bg);
    border: 1px solid rgba(0,113,227,0.1);
    border-radius: 16px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
}

/* FORM ELEMANLARI */
.form-group {
    margin-bottom: 1.2rem;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--apple-dark);
}
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid rgba(0,113,227,0.15);
    border-radius: 10px;
    font-size: 15px;
    transition: border-color 0.3s;
    background: rgba(255,255,255,0.8);
}
.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--accent);
}

/* BUTONLAR */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-primary {
    background: var(--accent);
    color: white;
}
.btn-primary:hover {
    background: #005bb5;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,113,227,0.3);
}
.btn-update {
    background: #2196F3;
    color: white;
    padding: 8px 16px;
}
.btn-update:hover { background: #0b7dda; }
.btn-delete {
    background: #f44336;
    color: white;
    padding: 8px 16px;
}
.btn-delete:hover { background: #da190b; }

/* TABLO */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: var(--card-bg);
    backdrop-filter: blur(15px);
    border-radius: 10px;
    overflow: hidden;
}
thead tr {
    background: rgba(0,113,227,0.08);
}
th, td {
    padding: 12px;
    text-align: left;
    font-size: 14px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
th {
    color: var(--apple-dark);
    font-weight: 700;
}
tbody tr:hover {
    background: rgba(0,113,227,0.05);
}

.table-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid rgba(0,113,227,0.2);
    border-radius: 6px;
    font-size: 14px;
}

.actions {
    display: flex;
    gap: 8px;
}

/* BOŞ DURUM */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}
.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.4;
}

/* HEADER */
header {
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(20px);
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 20;
}
header h1 { 
    font-size: 1.4rem; 
    font-weight: 700; 
    color: var(--accent); 
}
.logout-btn { 
    background: #ef4444; 
    color: white; 
    padding: 0.6rem 1rem; 
    border: none; 
    border-radius: 8px; 
    text-decoration: none; 
    font-weight: 600; 
    transition: 0.2s;
}
.logout-btn:hover {
    background: #dc2626; 
}
.menu-btn { 
    font-size: 1.5rem; 
    cursor: pointer; 
    background: none; 
    border: none; 
    margin-right: 1rem; 
}

/* TABS */
.tabs {
    display: flex;
    flex-direction: column;
    width: 200px;
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1rem;
    gap: 0.5rem;
    transition: transform 0.3s ease;
    transform: translateX(-220px);
    position: fixed;
    top: 60px;
    left: 0;
    z-index: 10;
}
.tabs.active { transform: translateX(0); }
.tab-button {
    padding: 0.8rem 1rem;
    cursor: pointer;
    border: none;
    background: transparent;
    font-weight: 600;
    text-align: left;
    border-radius: 8px;
    transition: 0.2s;
}
.tab-button.active {
    background: var(--accent);
    color: white;
}

.stats {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}
</style>
</head>
<body>
    <header>
        <button class="menu-btn">&#9776;</button>
        <h1>🎛️ Yönetici Paneli</h1>
        <a href="/admin/logout.php" class="logout-btn">Çıkış Yap</a>
    </header>

    <div class="tabs">
        <button class="tab-button" onclick="window.location.href='/../admin/panel.php'">İstatistikler</button>
        <button class="tab-button" onclick="window.location.href='/../admin/firma_düzenle.php'">Firma Yönetimi</button>
        <button class="tab-button active" onclick="window.location.href='/../admin/firma_admin.php'">Firma Admin Ekle/Çıkar</button>
    </div>

    <div class="container">
        <div class="card">
            <h2>👤 Firma Admini Ekle / Ata</h2>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="form-box">
                <h3>➕ Yeni Firma Admini Ekle</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="full_name">Ad Soyad *</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Örn: Ahmet Yılmaz" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="ornek@firma.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Şifre *</label>
                        <input type="password" id="password" name="password" placeholder="Güçlü bir şifre girin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_id">Firma Seç *</label>
                        <select id="company_id" name="company_id" required>
                            <option value="">Firma Seçin</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">➕ Firma Admini Ata</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3>📋 Mevcut Firma Adminleri <span class="stats"><?= count($admins) ?> Admin</span></h3>
            
            <?php if(empty($admins)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <h3 style="color: #6c757d; margin-bottom: 10px;">Henüz Firma Admini Eklenmemiş</h3>
                    <p>Yukarıdaki formu kullanarak ilk admini ekleyebilirsiniz.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Ad Soyad</th>
                            <th style="width: 25%;">Email</th>
                            <th style="width: 20%;">Firma</th>
                            <th style="width: 15%;">Eklenme Tarihi</th>
                            <th style="width: 20%;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($admin['id']) ?>">
                                
                                <td>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" class="table-input" required>
                                </td>
                                <td>
                                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" class="table-input" required>
                                </td>
                                <td>
                                    <select name="company_id" class="table-input" required>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?= htmlspecialchars($c['id']) ?>" <?= $admin['company_name'] === $c['name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="color: #6c757d; font-size: 14px;">
                                    <?= date('d.m.Y H:i', strtotime($admin['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button type="submit" name="action" value="update" class="btn btn-update">
                                            ✏️ Güncelle
                                        </button>
                                        <button type="submit" name="action" value="delete" class="btn btn-delete" 
                                                onclick="return confirm('<?= htmlspecialchars($admin['full_name']) ?> silinecek, emin misiniz?')">
                                            🗑️ Sil
                                        </button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Hamburger menü toggle
    const menuBtn = document.querySelector('.menu-btn');
    const tabs = document.querySelector('.tabs');
    menuBtn.addEventListener('click', () => {
        tabs.classList.toggle('active');
    });

    // Menü ekrana tıklayınca kapanma
    document.addEventListener('click', (e) => {
        if (!tabs.contains(e.target) && e.target !== menuBtn) {
            tabs.classList.remove('active');
        }
    });
    </script>
</body>
</html>