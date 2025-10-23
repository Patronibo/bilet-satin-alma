<?php
// Ödeme Seçim Sayfası - Bakiye veya Kredi Kartı
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

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET isteği gelirse (geriye gitme vs) ana sayfaya yönlendir
    header('Location: index.php');
    exit;
}

// CSRF koruması
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    die("Geçersiz istek. Lütfen tekrar deneyin.");
}

$trip_id = $_POST['trip_id'] ?? null;
$seat_number = $_POST['seat_number'] ?? null;
$gender = $_POST['gender'] ?? 'male';
$user_id = $_SESSION['user_id'] ?? null;

if (!$trip_id || !$seat_number) {
    die("Sefer veya koltuk bilgisi eksik.");
}

// Seferi doğrula
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// Eğer kullanıcı giriş yapmadıysa yönlendir
if (!$user_id) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /src/login.php');
    exit;
}

// Kullanıcı bilgilerini al
$userStmt = $db->prepare("SELECT balance, full_name, email FROM User WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Kullanıcı bulunamadı.");
}

$currentBalance = (float)($user['balance'] ?? 0);
$ticketPrice = (float)$trip['price'];

// Koltuk dolu mu kontrol et
$checkSeat = $db->prepare("
    SELECT COUNT(*) FROM Booked_Seats 
    INNER JOIN Tickets ON Tickets.id = Booked_Seats.ticket_id
    WHERE Tickets.trip_id = ? AND Booked_Seats.seat_number = ? AND Tickets.status = 'active'
");
$checkSeat->execute([$trip_id, $seat_number]);

if ($checkSeat->fetchColumn() > 0) {
    die("Bu koltuk zaten dolu!");
}

// Pending purchase'ı session'a kaydet
$_SESSION['pending_purchase'] = [
    'trip_id' => $trip_id,
    'seats' => [$seat_number], // Array olarak kaydet (kredi kartı sistemi için)
    'seat_number' => $seat_number, // Tek koltuk için
    'gender' => $gender,
    'price_per' => $ticketPrice,
    'total' => $ticketPrice,
    'trip_info' => $trip,
    'user_balance' => $currentBalance,
    'created_at' => time()
];

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$hasEnoughBalance = $currentBalance >= $ticketPrice;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💳 Ödeme Yöntemi Seçin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: gradientShift 10s ease infinite;
            background-size: 200% 200%;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-icon {
            font-size: 80px;
            margin-bottom: 15px;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        h1 {
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 10px;
            animation: fadeIn 0.5s ease-out 0.4s both;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .subtitle {
            color: #6b7280;
            font-size: 1.05rem;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }
        .trip-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid #0071e3;
            animation: fadeIn 0.5s ease-out 0.8s both;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            color: #6b7280;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .info-value {
            color: #1f2937;
            font-weight: 700;
            font-size: 1rem;
        }
        .balance-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #0071e3;
            text-align: center;
            animation: fadeIn 0.5s ease-out 1s both;
        }
        .balance-text {
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-out 1.2s both;
        }
        .payment-method {
            border: 3px solid #e5e7eb;
            border-radius: 16px;
            padding: 25px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }
        .payment-method:hover {
            border-color: #0071e3;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 113, 227, 0.2);
        }
        .payment-method.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f9fafb;
        }
        .payment-method.disabled:hover {
            transform: none;
            border-color: #e5e7eb;
            box-shadow: none;
        }
        .payment-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .payment-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .payment-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .insufficient-badge {
            background: #fef2f2;
            color: #dc2626;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
        }
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-back {
            background: #6b7280;
            color: white;
            margin-top: 15px;
            animation: fadeIn 0.5s ease-out 1.4s both;
        }
        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.3);
        }
        @media (max-width: 600px) {
            .payment-methods {
                grid-template-columns: 1fr;
            }
            .container { padding: 30px 25px; }
            h1 { font-size: 1.7rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">💳</div>
            <h1>Ödeme Yöntemi Seçin</h1>
            <p class="subtitle">Biletinizi almak için bir ödeme yöntemi seçin</p>
        </div>

        <div class="trip-info">
            <div class="info-row">
                <span class="info-label">🚌 Güzergah:</span>
                <span class="info-value"><?= e($trip['departure_city']) ?> → <?= e($trip['destination_city']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📅 Tarih:</span>
                <span class="info-value"><?= e(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">💺 Koltuk No:</span>
                <span class="info-value"><?= e($seat_number) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">💰 Ücret:</span>
                <span class="info-value"><?= number_format($ticketPrice, 2) ?> ₺</span>
            </div>
        </div>

        <div class="balance-info">
            <div class="balance-text">
                💵 Mevcut Bakiyeniz: <strong><?= number_format($currentBalance, 2) ?> ₺</strong>
            </div>
        </div>

        <div class="payment-methods">
            <!-- Sanal Bakiye ile Öde -->
            <form action="odeme/pay_with_balance.php" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" class="payment-method <?= !$hasEnoughBalance ? 'disabled' : '' ?>" <?= !$hasEnoughBalance ? 'disabled' : '' ?>>
                    <div class="payment-icon">💳</div>
                    <div class="payment-title">Sanal Bakiye</div>
                    <div class="payment-subtitle">Hızlı ve Güvenli</div>
                    <?php if (!$hasEnoughBalance): ?>
                        <div class="insufficient-badge">⚠️ Yetersiz Bakiye</div>
                    <?php endif; ?>
                </button>
            </form>

            <!-- Kredi Kartı ile Öde -->
            <a href="odeme/index.php" class="payment-method" style="text-decoration: none; display: block;">
                <div class="payment-icon">💰</div>
                <div class="payment-title">Kredi Kartı</div>
                <div class="payment-subtitle">Tüm Kartlar Kabul</div>
            </a>
        </div>

        <a href="/src/index.php" class="btn btn-back">
            ← Ana Sayfaya Dön
        </a>
    </div>
</body>
</html>
