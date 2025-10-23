<?php
/**
 * Email Gönderme Sistemi (2FA için)
 * Production'da gerçek SMTP kullanılmalı (PHPMailer, SendGrid, vs.)
 */

function send2FACode($toEmail, $code, $userName) {
    // Development ortamı için basit mail() fonksiyonu
    // Production'da PHPMailer veya SMTP servisi kullanılmalı
    
    $subject = "Siber Otobüs - Giriş Doğrulama Kodu";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .code-box { background: white; border: 2px solid #0071e3; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0071e3; margin: 20px 0; border-radius: 8px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; color: #856404; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Giriş Doğrulama</h1>
            </div>
            <div class='content'>
                <p>Merhaba <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p>Firma Admin Paneline giriş yapmak için doğrulama kodunuz:</p>
                
                <div class='code-box'>" . htmlspecialchars($code) . "</div>
                
                <p>Bu kod <strong>10 dakika</strong> geçerlidir.</p>
                
                <div class='warning'>
                    ⚠️ <strong>Güvenlik Uyarısı:</strong><br>
                    Bu kodu kimseyle paylaşmayın. Siber Otobüs asla size telefon veya email ile bu kodu sormaz.
                </div>
                
                <p>Eğer bu giriş denemesini siz yapmadıysanız, lütfen hemen şifrenizi değiştirin.</p>
            </div>
            <div class='footer'>
                <p>© 2025 Siber Otobüs - Güvenli Seyahat</p>
                <p>Bu otomatik bir mesajdır, lütfen yanıtlamayın.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Siber Otobüs <noreply@siberotobus.com>" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Development ortamı kontrolü
    if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
        // Localhost'ta console'a log at
        error_log("=== 2FA CODE EMAIL ===");
        error_log("To: $toEmail");
        error_log("Code: $code");
        error_log("User: $userName");
        error_log("======================");
        
        // Dosyaya da yaz (test için)
        $logFile = __DIR__ . '/../logs/2fa_codes.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | $toEmail | $code | $userName\n", FILE_APPEND);
        
        return true; // Localhost'ta her zaman başarılı
    }
    
    // Production ortamı - gerçek email gönder
    return mail($toEmail, $subject, $message, $headers);
}

function generate2FACode() {
    // 6 haneli numeric kod
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function store2FACode($userId, $code) {
    $db = getDB();
    
    // Tabloyu oluştur (yoksa)
    create2FATable($db);
    
    // Eski kodları temizle
    $db->exec("DELETE FROM TwoFA_Codes WHERE datetime(expires_at) < datetime('now', 'localtime') OR used = 1");
    
    // Yeni kodu kaydet (10 dakika geçerli)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $db->prepare("INSERT INTO TwoFA_Codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $code, $expiresAt]);
    
    return true;
}

function verify2FACode($userId, $code) {
    $db = getDB();
    
    // Tabloyu oluştur (yoksa)
    create2FATable($db);
    
    // Kodu kontrol et
    $stmt = $db->prepare("
        SELECT id 
        FROM TwoFA_Codes 
        WHERE user_id = ? 
        AND code = ? 
        AND used = 0 
        AND datetime(expires_at) > datetime('now', 'localtime')
    ");
    $stmt->execute([$userId, $code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Kodu kullanılmış olarak işaretle
        $updateStmt = $db->prepare("UPDATE TwoFA_Codes SET used = 1 WHERE id = ?");
        $updateStmt->execute([$result['id']]);
        return true;
    }
    
    return false;
}

function check2FARateLimit($userId) {
    $db = getDB();
    
    // Tabloyu oluştur (yoksa)
    create2FATable($db);
    
    // Son 1 saatte kaç kod gönderilmiş?
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM TwoFA_Codes 
        WHERE user_id = ? 
        AND datetime(created_at) > datetime('now', '-1 hour', 'localtime')
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Maksimum 5 kod/saat
    return $result['count'] < 5;
}

function create2FATable($db) {
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS TwoFA_Codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                code TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                used INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now', 'localtime')),
                FOREIGN KEY(user_id) REFERENCES User(id) ON DELETE CASCADE
            )
        ");
    } catch (Exception $e) {
        // Tablo zaten varsa devam et
    }
}