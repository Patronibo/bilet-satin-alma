<?php
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
require_once __DIR__ . '/../../includes/db.php';

$db = getDB();

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Read pending purchase
$pending = $_SESSION['pending_purchase'] ?? null;
if (!$pending) {
    // Kullanıcı dostu hata sayfası göster
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>⚠️ Geçersiz Erişim</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 24px;
                padding: 50px 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
            }
            .icon {
                font-size: 80px;
                margin-bottom: 20px;
                animation: bounce 2s infinite;
            }
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
            h1 {
                color: #1f2937;
                font-size: 2rem;
                margin-bottom: 15px;
            }
            p {
                color: #6b7280;
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .steps {
                background: #f9fafb;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 30px;
                text-align: left;
            }
            .steps h3 {
                color: #1f2937;
                font-size: 1.1rem;
                margin-bottom: 15px;
            }
            .steps ol {
                padding-left: 20px;
                color: #374151;
            }
            .steps li {
                margin-bottom: 8px;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
                color: white;
                padding: 16px 32px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 600;
                font-size: 1.1rem;
                transition: all 0.3s ease;
                box-shadow: 0 10px 30px rgba(0, 113, 227, 0.3);
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 40px rgba(0, 113, 227, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🚫</div>
            <h1>Geçersiz Erişim</h1>
            <p>Bu sayfaya direkt olarak erişilemez. Önce bir sefer ve koltuk seçmelisiniz.</p>
            
            <div class="steps">
                <h3>📋 Nasıl Bilet Alınır?</h3>
                <ol>
                    <li>Ana sayfadan bir sefer seçin</li>
                    <li>"Koltuk Seç" butonuna tıklayın</li>
                    <li>Koltuk numaranızı ve cinsiyetinizi seçin</li>
                    <li>Ödeme yöntemini seçin</li>
                    <li>Ödeme bilgilerinizi girin</li>
                </ol>
            </div>
            
            <a href="../index.php" class="btn">🏠 Ana Sayfaya Dön</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Eski sistemle uyumluluk için seats array'ini oluştur
if (!isset($pending['seats']) && isset($pending['seat_number'])) {
    $pending['seats'] = [$pending['seat_number']];
    $_SESSION['pending_purchase'] = $pending;
}

// Kupon kontrolü (Form submission - JavaScript'siz)
$couponData = null;
$couponMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = trim(strtoupper($_POST['coupon_code'] ?? ''));
    if (!empty($couponCode)) {
        $stmt = $db->prepare("SELECT discount_rate FROM Coupons WHERE code = ? AND status = 'active'");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            $discountRate = (int)$coupon['discount_rate'];
            $discountAmount = ($pending['total'] * $discountRate) / 100;
            $finalTotal = $pending['total'] - $discountAmount;
            
            $couponData = [
                'code' => $couponCode,
                'discount' => $discountRate,
                'discountAmount' => number_format($discountAmount, 2),
                'finalTotal' => number_format($finalTotal, 2)
            ];
            $couponMessage = "✅ Kupon uygulandı! %$discountRate indirim";
            
            // Kuponu session'a kaydet (ödeme işlemi için)
            $_SESSION['applied_coupon'] = $couponCode;
        } else {
            $couponMessage = "❌ Geçersiz kupon kodu";
        }
    }
}

// Giriş yapmadan ödeme yapılamaz
if (empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Ödeme yapmak için lütfen giriş yapın veya kayıt olun.';
    header('Location: ../login.php');
    exit;
}

// Fetch user info
$user = null;
$st = $db->prepare('SELECT id, full_name, email FROM User WHERE id = ?');
$st->execute([$_SESSION['user_id']]);
$user = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Errors via session flash
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ödeme</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%); margin:0; color:#1c1c1e; }
    .topbar { position: sticky; top:0; z-index: 100; padding: 12px 0; backdrop-filter: blur(16px); }
    .topbar .inner { max-width: 1100px; margin: 0 auto; padding: 10px 16px; display:flex; align-items:center; justify-content:space-between; background: rgba(255,255,255,0.85); border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05); }
    .brand { display:flex; align-items:center; gap:10px; font-weight:700; }
    .brand span { font-size: 1.1rem; }
    .nav-actions a { text-decoration:none; color:#fff; background:#0071e3; padding:8px 14px; border-radius:10px; margin-left:8px; font-weight:600; }
    .container { max-width: 1100px; margin: 30px auto; padding: 0 16px; }
    .section { background: rgba(255,255,255,0.92); border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05); padding: 22px; margin-bottom: 18px; }
    h2 { margin:0 0 16px; }
    .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:20px; }
    .field { display:flex; flex-direction:column; margin-bottom:12px; }
    label { font-size: 0.9rem; color:#333; margin-bottom:6px; }
    input, select { padding:12px; border:1.6px solid #d2d2d7; border-radius:10px; font-size:14px; background:#fafafa; }
    input:focus, select:focus { outline:none; border-color:#0071e3; background:#fff; box-shadow:0 0 0 3px rgba(0,113,227,.15); }
    .summary { margin-top:16px; }
    .btn { display:inline-block; padding:12px 18px; border-radius:10px; background:#0071e3; color:#fff; border:none; cursor:pointer; font-weight:600; }
    .btn.secondary { background:#6b7280; }
    .error { background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:8px; margin-bottom:10px; }
    .errors { margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="inner">
      <div class="brand"><div style="font-size:22px;">👑</div><span>Ödeme</span></div>
      <div class="nav-actions">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <a href="../logout.php">Çıkış</a>
        <?php else: ?>
          <a href="../login.php">Giriş Yap</a>
          <a href="../register.php">Kayıt Ol</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="section">
      <h2>Ödeme</h2>

      <?php if (!empty($errors)): ?>
        <div class="errors">
          <?php foreach ($errors as $err): ?>
            <div class="error"><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="grid2">
        <div>
          <div class="section">
            <h3>Yolcu Bilgileri</h3>
            <form method="post" action="process.php" id="payment-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
              <label>TC Kimlik Numarası</label>
              <input type="text" name="tc" maxlength="11" pattern="^[0-9]{11}$" required>
            </div>
            <div class="field">
              <label>Ad Soyad</label>
              <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="field">
              <label>Telefon</label>
              <input type="text" name="phone" placeholder="5XXXXXXXXX" pattern="^[0-9]{10,11}$" required>
            </div>
            <div class="field">
              <label>E-posta</label>
              <input type="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
            </form>
          </div>

          <div class="section">
            <h3>Kredi Kartı</h3>
            <div class="field">
              <label>Kart Numarası (16 hane)</label>
              <input type="text" name="card_number" form="payment-form" id="card_number" inputmode="numeric" pattern="^[0-9]{16}$" maxlength="16" required>
            </div>
            <div class="grid2" style="grid-template-columns: 1fr 1fr; gap:10px;">
              <div class="field">
                <label>SKT (AA/YY)</label>
                <input type="text" name="expiry" form="payment-form" id="expiry" placeholder="AA/YY" pattern="^(0[1-9]|1[0-2])\/([0-9]{2})$" maxlength="5" required>
              </div>
              <div class="field">
                <label>CVC (3 hane)</label>
                <input type="text" name="cvc" form="payment-form" id="cvc" pattern="^[0-9]{3}$" maxlength="3" required>
              </div>
            </div>

            <div class="summary">
              <p>Sefer No: <?= e($pending['trip_id']) ?></p>
              <p>Koltuklar: <?= e(implode(', ', $pending['seats'])) ?></p>
              <p>Cinsiyet: <?= e($pending['gender'] === 'female' ? 'Kadın' : 'Erkek') ?></p>
              <p>Birim Fiyat: <?= e(number_format($pending['price_per'], 2)) ?> ₺</p>
              <p>Ara Toplam: <?= e(number_format($pending['total'], 2)) ?> ₺</p>
              
              <form method="POST" style="margin-top:16px; border:1px solid #e5e7eb; padding:12px; border-radius:10px; background:#fafafa;">
                <div class="field" style="margin-bottom:8px;">
                  <label>Kupon Kodu (opsiyonel)</label>
                  <input type="text" name="coupon_code" id="coupon_code" placeholder="INDIRIM20" style="text-transform:uppercase;" value="<?= e($_POST['coupon_code'] ?? '') ?>">
                </div>
                <button type="submit" name="apply_coupon" class="btn" style="width:100%;padding:10px;">🎟️ Kupon Uygula</button>
                <?php if (!empty($couponMessage)): ?>
                  <div style="margin-top:8px; padding:8px; border-radius:6px; font-size:0.9rem; <?= strpos($couponMessage, '✅') !== false ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;' ?>">
                    <?= e($couponMessage) ?>
                  </div>
                <?php endif; ?>
              </form>
              
              <?php if ($couponData): ?>
              <div style="margin-top:10px; padding:10px; background:#dcfce7; border-radius:8px; color:#166534;">
                <p style="margin:0;">✨ İndirim: <strong><?= e($couponData['discountAmount']) ?></strong> ₺ (<span><?= e($couponData['discount']) ?></span>%)</p>
              </div>
              <?php endif; ?>
              
              <p style="margin-top:10px;"><b>Toplam: <?= $couponData ? e($couponData['finalTotal']) : e(number_format($pending['total'], 2)) ?> ₺</b></p>
            </div>

            <div style="margin-top:12px;">
              <button class="btn" type="submit" form="payment-form">Ödemeyi Tamamla</button>
              <a class="btn secondary" href="../index.php" style="text-decoration:none;">Vazgeç</a>
            </div>
          </div>
        </div>
        <div>
          <div class="section">
            <h3>Bilgilendirme</h3>
            <p>Giriş yapmadan seçim yapabilir ve ödeme adımına geçebilirsiniz. Ödeme tamamlanmadan koltuklar satılmaz.</p>
            <p>Giriş yaptıysanız bilgileriniz otomatik doldurulur. Eksik bilgi varsa hata alırsınız.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- ✅ TAM PHP SİSTEM - JavaScript kullanılmıyor! Kupon kontrolü form submission ile yapılıyor -->
</body>
</html>
