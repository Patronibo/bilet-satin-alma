<?php
/**
 * ADMIN ACCESS TOKEN GENERATOR - ULTRA SECURE
 * Bu sayfa da korumalıdır! Sadece SECRET_MASTER_KEY ile erişilebilir.
 * 
 * GÜVENLİK: Session tabanlı kontrol - artık cookie'de secret key yok!
 */

session_start();

// ============================================
// LAYER 1: SECRET MASTER KEY SESSION KONTROLÜ
// ============================================

// Session'da secret master authentication var mı ve geçerli mi?
$isAuthenticated = isset($_SESSION['secret_master_authenticated']) && 
                   $_SESSION['secret_master_authenticated'] === true &&
                   (time() - ($_SESSION['secret_master_time'] ?? 0)) < 86400; // 24 saat

if (!$isAuthenticated) {
    // Yetkisiz erişim - secret_access.php'ye yönlendir
    header('Location: /admin/secret_access.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Komut satırından veya tarayıcıdan çalıştırılabilir
$isCLI = php_sapi_name() === 'cli';

// Token oluştur
$token = bin2hex(random_bytes(32)); // 64 karakter güçlü token
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 saat geçerli

$db = getDB();

// Admin_Access_Tokens tablosunu oluştur (yoksa)
$db->exec("
    CREATE TABLE IF NOT EXISTS Admin_Access_Tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE NOT NULL,
        expires_at TEXT NOT NULL,
        used INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now', 'localtime')),
        used_at TEXT,
        ip_address TEXT
    )
");

// Token'ı veritabanına kaydet
$stmt = $db->prepare("INSERT INTO Admin_Access_Tokens (token, expires_at) VALUES (?, ?)");
$stmt->execute([$token, $expiresAt]);

// Eski token'ları temizle (kullanılmış veya süresi dolmuş)
$db->exec("
    DELETE FROM Admin_Access_Tokens 
    WHERE used = 1 
    OR datetime(expires_at) < datetime('now', 'localtime')
    OR datetime(created_at) < datetime('now', '-7 days', 'localtime')
");

if ($isCLI) {
    // Komut satırından çalıştırıldı
    echo "\n========================================\n";
    echo "   YENİ ADMIN ERİŞİM TOKENİ OLUŞTURULDU\n";
    echo "========================================\n\n";
    echo "Token: {$token}\n\n";
    echo "Geçerlilik: {$expiresAt}\n\n";
    echo "Tarayıcı konsolunda çalıştır:\n";
    echo "-----------------------------------\n";
    echo "document.cookie = \"admin_access_token={$token}; path=/; max-age=86400\";\n";
    echo "alert('Token ayarlandı!');\n";
    echo "-----------------------------------\n\n";
} else {
    // Tarayıcıdan çalıştırıldı
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Token Oluşturucu</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #1d1d1f;
                margin-bottom: 10px;
                font-size: 1.8rem;
            }
            .subtitle {
                color: #6b7280;
                margin-bottom: 30px;
                font-size: 0.95rem;
            }
            .token-box {
                background: #f3f4f6;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                word-break: break-all;
                font-family: 'Courier New', monospace;
                font-size: 0.9rem;
                color: #1f2937;
            }
            .info {
                background: #dbeafe;
                border-left: 4px solid #3b82f6;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                color: #1e40af;
                font-size: 0.9rem;
            }
            .btn {
                background: #0071e3;
                color: white;
                border: none;
                border-radius: 10px;
                padding: 15px 30px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
                transition: all 0.3s ease;
                margin-bottom: 10px;
            }
            .btn:hover {
                background: #005bb5;
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0,113,227,0.3);
            }
            .btn-secondary {
                background: #6b7280;
            }
            .btn-secondary:hover {
                background: #4b5563;
            }
            .success {
                background: #dcfce7;
                border-left: 4px solid #16a34a;
                padding: 15px;
                border-radius: 8px;
                color: #166534;
                margin-top: 20px;
                display: none;
            }
            .label {
                font-size: 0.85rem;
                color: #6b7280;
                font-weight: 600;
                margin-bottom: 8px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Admin Token Oluşturucu</h1>
            <p class="subtitle">Yeni bir admin erişim tokeni oluşturuldu</p>

            <div class="info">
                ⚠️ Bu token <strong>24 saat</strong> geçerlidir ve <strong>tek kullanımlıktır</strong>.
            </div>

            <div class="label">📋 TOKEN:</div>
            <div class="token-box" id="token"><?= htmlspecialchars($token) ?></div>

            <div class="label">⏰ GEÇERLİLİK:</div>
            <div class="token-box"><?= htmlspecialchars($expiresAt) ?></div>

            <button class="btn" onclick="copyToken()">📋 Token'ı Kopyala</button>
            <button class="btn btn-secondary" onclick="location.reload()">🔄 Yeni Token Oluştur</button>
        </div>

        <script>
            const token = <?= json_encode($token) ?>;

            function copyToken() {
                navigator.clipboard.writeText(token).then(() => {
                    showNotification('Token panoya kopyalandı!', 'success');
                }).catch(() => {
                    // Fallback
                    const textarea = document.createElement('textarea');
                    textarea.value = token;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    showNotification('Token panoya kopyalandı!', 'success');
                });
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 25px;
                    background: ${type === 'success' ? '#10b981' : '#ef4444'};
                    color: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    z-index: 10000;
                    font-weight: 600;
                    animation: slideIn 0.3s ease-out;
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(400px); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        </script>
    </body>
    </html>
    <?php
}
?>

