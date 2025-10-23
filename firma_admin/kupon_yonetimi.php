<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: firma_admin_login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$full_name = $_SESSION['full_name'];

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Kupon CRUD işlemleri
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $_SESSION['kupon_message'] = '❌ Geçersiz istek.';
        header('Location: kupon_yonetimi.php');
        exit;
    }
    
    if (isset($_POST['create_coupon'])) {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $discount = (float)($_POST['discount'] ?? 0);
        $usage_limit = (int)($_POST['usage_limit'] ?? 1);
        $expire_date = trim((string)($_POST['expire_date'] ?? ''));

        if ($code && $discount > 0 && $discount <= 100) {
            $id = 'coupon-' . bin2hex(random_bytes(8));
            $stmt = $db->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $code, $discount / 100, $usage_limit, $expire_date]);
            $_SESSION['kupon_message'] = "Kupon başarıyla oluşturuldu";
        }
    } elseif (isset($_POST['delete_coupon'])) {
        $couponId = $_POST['delete_coupon'];
        $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");
        $stmt->execute([$couponId]);
        $_SESSION['kupon_message'] = "Kupon silindi";
    }
    
    // POST-Redirect-GET pattern
    header('Location: kupon_yonetimi.php');
    exit;
}

// Mesajı session'dan al ve temizle
if (isset($_SESSION['kupon_message'])) {
    $message = $_SESSION['kupon_message'];
    unset($_SESSION['kupon_message']);
}

// Kuponları listele
$coupons = $db->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kupon Yönetimi</title>
<style>
:root {
    --bg: #f4f6f8;
    --dark: #1d1d1f;
    --accent: #0071e3;
    --accent-hover: #005bb5;
    --danger: #dc3545;
    --card: #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
body { display: flex; height: 100vh; background: var(--bg); color: var(--dark); overflow: hidden; }

.sidebar {
    width: 260px;
    background: white;
    border-right: 1px solid rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    padding: 25px;
}
.sidebar h2 { color: var(--accent); font-size: 1.5rem; margin-bottom: 25px; text-align: center; }
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
.tab-button:hover { background: var(--accent); color: #fff; }

.main-content { flex: 1; overflow-y: auto; padding: 30px 40px; }
header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
header h1 { font-size: 1.6rem; font-weight: 600; }
header button { background: var(--accent); color: white; border: none; border-radius: 8px; padding: 10px 18px; font-weight: 500; cursor: pointer; transition: 0.3s; }
header button:hover { background: var(--accent-hover); }

.card { background: var(--card); padding: 25px; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
.card h2 { margin-bottom: 15px; color: var(--accent); }

table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 12px 10px; border-bottom: 1px solid rgba(0,0,0,0.1); text-align: left; }
th { color: var(--accent); font-weight: 600; background: #f8f9fa; }
tbody tr:hover { background: #f1f5ff; transition: 0.2s; }

.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
.form-group input { width: 100%; padding: 10px; border: 1.5px solid #d2d2d7; border-radius: 8px; font-size: 14px; }
.form-group input:focus { outline: none; border-color: var(--accent); }

.action-btn { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: white; font-size: 0.9rem; font-weight: 500; transition: 0.3s; }
.action-btn:hover { transform: scale(1.05); }
.action-btn.delete { background: var(--danger); }
.action-btn.delete:hover { background: #bb2d3b; }

.message { padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 15px; }
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
            <h1>Hoş geldin, <?= e($full_name) ?></h1>
            <button onclick="location.reload()">🔄 Yenile</button>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message"><?= e($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>🎟️ Yeni Kupon Oluştur</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Kupon Kodu</label>
                        <input type="text" name="code" placeholder="YENI2024" required>
                    </div>
                    <div class="form-group">
                        <label>İndirim Oranı (%)</label>
                        <input type="number" name="discount" min="1" max="100" placeholder="10" required>
                    </div>
                    <div class="form-group">
                        <label>Kullanım Limiti</label>
                        <input type="number" name="usage_limit" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Son Kullanma Tarihi</label>
                        <input type="datetime-local" name="expire_date" required>
                    </div>
                </div>
                <button type="submit" name="create_coupon" class="action-btn" style="background: var(--accent); padding: 10px 20px; margin-top: 10px;">Kupon Oluştur</button>
            </form>
        </div>

        <div class="card">
            <h2>📋 Mevcut Kuponlar</h2>
            <?php if (empty($coupons)): ?>
                <p>Henüz kupon oluşturulmamış.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>İndirim</th>
                        <th>Kullanım Limiti</th>
                        <th>Son Kullanma</th>
                        <th>Oluşturulma</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><b><?= e($coupon['code']) ?></b></td>
                        <td>%<?= number_format((float)$coupon['discount'] * 100, 0) ?></td>
                        <td><?= (int)$coupon['usage_limit'] ?></td>
                        <td><?= e($coupon['expire_date']) ?></td>
                        <td><?= e($coupon['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="delete_coupon" value="<?= e($coupon['id']) ?>">
                                <button type="submit" class="action-btn delete" onclick="return confirm('Bu kuponu silmek istediğine emin misin?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

