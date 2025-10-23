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

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

try {
    $db = getDB();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $id = uniqid('company_');
            $name = trim($_POST['name'] ?? '');
            $logo_path = trim($_POST['logo_path'] ?? '');
            
            if ($name) {
                try {
                    $stmt = $db->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, ?, datetime('now'))");
                    $result = $stmt->execute([$id, $name, $logo_path]);
                    
                    if($result){
                        $message = "✅ Firma başarıyla eklendi!";
                    } else {
                        $error = "❌ Firma eklenirken hata oluştu!";
                    }
                } catch (PDOException $e) {
                    // UNIQUE constraint hatası
                    if(strpos($e->getMessage(), 'UNIQUE') !== false) {
                        $error = "❌ Bu firma adı zaten kullanılıyor!";
                    } else {
                        $error = "❌ Veritabanı hatası: " . $e->getMessage();
                    }
                }
            } else {
                $error = "❌ Firma adı boş olamaz!";
            }
            
        } elseif($action === 'update'){
            $id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $logo_path = trim($_POST['logo_path'] ?? '');
            
            if($id && $name){
                try {
                    $stmt = $db->prepare("UPDATE Bus_Company SET name=?, logo_path=? WHERE id=?");
                    $stmt->execute([$name, $logo_path, $id]);
                    $message = "✅ Firma güncellendi!";
                } catch (PDOException $e) {
                    if(strpos($e->getMessage(), 'UNIQUE') !== false) {
                        $error = "❌ Bu firma adı zaten kullanılıyor!";
                    } else {
                        $error = "❌ Güncelleme hatası: " . $e->getMessage();
                    }
                }
            }
            
        } elseif($action === 'delete'){
            $id = $_POST['id'] ?? '';
            
            if($id){
                try {
                    $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id=?");
                    $stmt->execute([$id]);
                    $message = "✅ Firma silindi!";
                } catch (PDOException $e) {
                    $error = "❌ Silme hatası: " . $e->getMessage();
                }
            }
        }
    }
    
    // Güncel liste
    $companies = $db->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "❌ Genel hata: " . $e->getMessage();
    $companies = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi</title>
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
    padding-top: 2rem;
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
.form-group input[type="text"] {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid rgba(0,113,227,0.15);
    border-radius: 10px;
    font-size: 15px;
    transition: border-color 0.3s;
    background: rgba(255,255,255,0.8);
}
.form-group input[type="text"]:focus {
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

.stats {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
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
    font-size: 1.4rem; font-weight: 700; color: var(--accent); 
}
.logout-btn { 
    background: #ef4444; color: white; padding: 0.6rem 1rem; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s;
 }
.logout-btn:hover {
     background: #dc2626; 
    }
.menu-btn { 
    font-size: 1.5rem; cursor: pointer; background: none; border: none; margin-right: 1rem; 
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
    <button class="tab-button active" onclick="window.location.href='/../admin/firma_düzenle.php'">Firma Yönetimi</button>
    <button class="tab-button" onclick="window.location.href='/../admin/firma_admin.php'">Firma Admin Ekle/Çıkar</button>
</div>


<div class="container">
    <div class="card">
        <h2>🚌 Firma Yönetimi</h2>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-box">
            <h3>➕ Yeni Firma Ekle</h3>
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Firma Adı *</label>
                    <input type="text" id="name" name="name" placeholder="Örn: Metro Turizm" required>
                </div>
                
                <div class="form-group">
                    <label for="logo">Logo URL (Opsiyonel)</label>
                    <input type="text" id="logo" name="logo_path" placeholder="https://example.com/logo.png">
                </div>
                
                <button type="submit" class="btn btn-primary">➕ Firma Ekle</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3>📋 Mevcut Firmalar <span class="stats"><?= count($companies) ?> Firma</span></h3>
        
        <?php if(empty($companies)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3 style="color: #6c757d; margin-bottom: 10px;">Henüz Firma Eklenmemiş</h3>
                <p>Yukarıdaki formu kullanarak ilk firmayı ekleyebilirsiniz.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Firma Adı</th>
                        <th style="width: 35%;">Logo URL</th>
                        <th style="width: 20%;">Eklenme Tarihi</th>
                        <th style="width: 25%;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($company['id']) ?>">
                            <td>
                                <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>" required class="table-input">
                            </td>
                            <td>
                                <input type="text" name="logo_path" value="<?= htmlspecialchars($company['logo_path'] ?? '') ?>" class="table-input">
                            </td>
                            <td style="color: #6c757d; font-size: 14px;">
                                <?= date('d.m.Y H:i', strtotime($company['created_at'])) ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="submit" name="action" value="update" class="btn btn-update">
                                        ✏️ Güncelle
                                    </button>
                                    <button type="submit" name="action" value="delete" class="btn btn-delete" 
                                            onclick="return confirm('<?= htmlspecialchars($company['name']) ?> firmasını silmek istediğinizden emin misiniz?')">
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

// Sekme yönetimi
const tabButtons = document.querySelectorAll('.tab-button');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        tabButtons.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        const tabId = btn.dataset.tab;
        const tab = document.getElementById(tabId);
        tab.classList.add('active');

        const phpFile = btn.dataset.php;
        if (phpFile) {
            tab.innerHTML = "<p>Yükleniyor...</p>"; // loading efekti
            fetch(phpFile)
                .then(res => res.text())
                .then(html => {
                    tab.innerHTML = html;
                })
                .catch(() => {
                    tab.innerHTML = "<p>Bir hata oluştu.</p>";
                });
        }
    });
});
</script>


</body>
</html>