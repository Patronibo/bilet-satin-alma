<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$db = getDB();

// CSRF koruması (AJAX için - session-based check yeterli)
// AJAX isteği olduğu için token yerine session kontrolü yapıyoruz
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı']);
    exit;
}

// Pending purchase kontrolü
$pending = $_SESSION['pending_purchase'] ?? null;
if (!$pending) {
    echo json_encode(['success' => false, 'error' => 'Sepet bulunamadı']);
    exit;
}

$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

if (empty($couponCode)) {
    echo json_encode([
        'success' => true,
        'discount' => 0,
        'discountAmount' => 0,
        'finalTotal' => $pending['total'],
        'message' => ''
    ]);
    exit;
}

// Kupon kontrolü
$stmt = $db->prepare("SELECT * FROM Coupons WHERE code = ? LIMIT 1");
$stmt->execute([$couponCode]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz kupon kodu']);
    exit;
}

// Kupon süresi kontrolü
if (!empty($coupon['expire_date'])) {
    $expireTime = strtotime($coupon['expire_date']);
    if ($expireTime < time()) {
        echo json_encode(['success' => false, 'error' => 'Kupon süresi dolmuş']);
        exit;
    }
}

// Kullanım limiti kontrolü
$usageCount = $db->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
$usageCount->execute([$coupon['id']]);
$used = (int)$usageCount->fetchColumn();

if ($used >= (int)$coupon['usage_limit']) {
    echo json_encode(['success' => false, 'error' => 'Kupon kullanım limiti doldu']);
    exit;
}

// İndirim hesaplama
$originalTotal = $pending['total'];
$discountRate = (float)$coupon['discount']; // 0.1 = %10
$discountAmount = $originalTotal * $discountRate;
$finalTotal = $originalTotal - $discountAmount;

echo json_encode([
    'success' => true,
    'discount' => $discountRate * 100, // %10 olarak göstermek için
    'discountAmount' => number_format($discountAmount, 2, '.', ''),
    'finalTotal' => number_format($finalTotal, 2, '.', ''),
    'message' => '✅ Kupon uygulandı! %' . ($discountRate * 100) . ' indirim'
]);

