<?php
// ADMIN ACCESS CONTROL - Rotating Token Sistemi
require_once __DIR__ . '/../includes/db.php';

function validateAdminToken() {
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
require_once __DIR__ . '/../includes/security.php';
$db = getDB();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$message = '';

// CSRF token oluştur
generate_csrf_token();

// CRUD işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $_SESSION['admin_message'] = 'Geçersiz istek';
    } elseif (isset($_POST['create_company'])) {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name) {
            try {
                // Aynı isimde firma var mı kontrol et
                $checkStmt = $db->prepare("SELECT id FROM Bus_Company WHERE name = ?");
                $checkStmt->execute([$name]);
                if ($checkStmt->fetch()) {
                    $_SESSION['admin_message'] = "❌ Bu isimde bir firma zaten mevcut!";
                } else {
                    $id = 'company-' . bin2hex(random_bytes(8));
                    $stmt = $db->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
                    $stmt->execute([$id, $name]);
                    $_SESSION['admin_message'] = "✅ Firma başarıyla oluşturuldu";
                }
            } catch (PDOException $e) {
                $_SESSION['admin_message'] = "❌ Firma eklenirken hata oluştu: " . ($e->getCode() == 23000 ? "Bu isimde firma zaten mevcut" : "Veritabanı hatası");
            }
        }
    } elseif (isset($_POST['delete_company'])) {
        $companyId = $_POST['delete_company'];
        $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
        $stmt->execute([$companyId]);
        $_SESSION['admin_message'] = "Firma silindi";
    } elseif (isset($_POST['create_company_admin'])) {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        $companyId = trim((string)($_POST['company_id'] ?? ''));
        
        if ($fullName && $email && $password && $companyId) {
            $id = 'user-' . bin2hex(random_bytes(8));
            $hashedPw = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password, company_id) VALUES (?, ?, ?, 'company', ?, ?)");
            $stmt->execute([$id, $fullName, $email, $hashedPw, $companyId]);
            $_SESSION['admin_message'] = "✅ Firma Admin başarıyla oluşturuldu!";
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['delete_user'];
        $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        $_SESSION['admin_message'] = "Firma Admin silindi";
    }
    
    // POST-Redirect-GET pattern: Sayfa yenilendiğinde form tekrar submit olmasın
    header('Location: /admin/panel.php');
    exit;
}

// Mesajı session'dan al ve temizle
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// Listeleme
$companies = $db->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$companyAdmins = $db->query("
    SELECT u.*, bc.name AS company_name 
    FROM User u 
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id 
    WHERE u.role = 'company' 
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Paneli</title>
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
.form-group input, .form-group select { width: 100%; padding: 10px; border: 1.5px solid #d2d2d7; border-radius: 8px; font-size: 14px; }
.form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }

.action-btn { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: white; font-size: 0.9rem; font-weight: 500; transition: 0.3s; }
.action-btn:hover { transform: scale(1.05); }
.action-btn.delete { background: var(--danger); }
.action-btn.delete:hover { background: #bb2d3b; }

.message { padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 15px; }
</style>
</head>
<body>
    <div class="sidebar">
        <h2>⚙️ Admin Paneli</h2>
        <a class="tab-button" href="/admin/panel.php">Ana Sayfa</a>
        <a class="tab-button" href="/admin/logout.php">Çıkış Yap</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Hoş geldin, Admin</h1>
            <button onclick="location.reload()">🔄 Yenile</button>
        </header>

        <?php if ($message): ?>
            <div class="message"><?= e($message) ?></div>
        <?php endif; ?>

        <!-- Firma Oluştur -->
        <div class="card">
            <h2>🏢 Yeni Firma Oluştur</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label>Firma Adı</label>
                    <input type="text" name="name" placeholder="Metro Turizm" required>
                </div>
                <button type="submit" name="create_company" class="action-btn" style="background: var(--accent); padding: 10px 20px;">Firma Oluştur</button>
            </form>
        </div>

        <!-- Firmalar Listesi -->
        <div class="card">
            <h2>📋 Firmalar</h2>
            <?php if (empty($companies)): ?>
                <p>Henüz firma bulunmuyor.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Firma Adı</th>
                        <th>Oluşturulma</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><b><?= e($company['name']) ?></b></td>
                        <td><?= e($company['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="delete_company" value="<?= e($company['id']) ?>">
                                <button type="button" class="action-btn delete" onclick="showDeleteConfirm(this.form, 'Bu firmayı silmek istediğine emin misin?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Firma Admin Oluştur -->
        <div class="card">
            <h2>👤 Yeni Firma Admin Oluştur</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Ad Soyad</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>E-posta</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Şifre</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Firma</label>
                        <select name="company_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= e($company['id']) ?>"><?= e($company['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_company_admin" class="action-btn" style="background: var(--accent); padding: 10px 20px; margin-top: 10px;">Firma Admin Oluştur</button>
            </form>
        </div>

        <!-- Firma Adminler Listesi -->
        <div class="card">
            <h2>📋 Firma Adminleri</h2>
            <?php if (empty($companyAdmins)): ?>
                <p>Henüz firma admin bulunmuyor.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Firma</th>
                        <th>Oluşturulma</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companyAdmins as $admin): ?>
                    <tr>
                        <td><?= e($admin['full_name']) ?></td>
                        <td><?= e($admin['email']) ?></td>
                        <td><?= e($admin['company_name'] ?? '-') ?></td>
                        <td><?= e($admin['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="delete_user" value="<?= e($admin['id']) ?>">
                                <button type="button" class="action-btn delete" onclick="showDeleteConfirm(this.form, 'Bu kullanıcıyı silmek istediğine emin misin?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Güvenli Onay Modal'ı (XSS Korumalı) -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h3 style="margin: 0 0 15px 0; color: #dc2626;">⚠️ Onay Gerekli</h3>
            <p id="confirmMessage" style="margin: 0 0 25px 0; color: #374151;"></p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeConfirmModal()" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">İptal</button>
                <button id="confirmBtn" style="padding: 10px 20px; border: none; background: #dc2626; color: white; border-radius: 6px; cursor: pointer; font-weight: 600;">Sil</button>
            </div>
        </div>
    </div>

    <script>
        let pendingForm = null;

        function showDeleteConfirm(form, message) {
            pendingForm = form;
            // XSS koruması: textContent kullan
            document.getElementById('confirmMessage').textContent = message;
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'flex';
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
            pendingForm = null;
        }

        document.getElementById('confirmBtn').addEventListener('click', function() {
            if (pendingForm) {
                pendingForm.submit();
            }
            closeConfirmModal();
        });

        // ESC tuşu ile kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConfirmModal();
            }
        });
    </script>
</body>
</html>
