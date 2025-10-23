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

// Geriye gidince veya session expire olunca
$pending = $_SESSION['pending_purchase'] ?? null;
if (!$pending) {
    $_SESSION['error_message'] = 'Oturum süresi doldu. Lütfen tekrar sefer seçin.';
    header('Location: ../index.php');
    exit;
}

// POST metodu kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// CSRF koruması
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    $_SESSION['form_errors'] = ['Geçersiz istek. Lütfen tekrar deneyin.'];
    header('Location: index.php');
    exit;
}

// Collect inputs
$tc = trim((string)($_POST['tc'] ?? ''));
$fullName = trim((string)($_POST['full_name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$card = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
$expiry = trim((string)($_POST['expiry'] ?? ''));
$cvc = trim((string)($_POST['cvc'] ?? ''));
$couponCode = strtoupper(trim((string)($_POST['coupon_code'] ?? '')));

// Server-side validations (XSS-safe later when echoing)
$errors = [];
if (!preg_match('/^\d{11}$/', $tc)) $errors[] = 'TC Kimlik 11 hane olmalıdır.';
if ($fullName === '') $errors[] = 'Ad Soyad zorunludur.';
if (!preg_match('/^\d{10,11}$/', $phone)) $errors[] = 'Telefon 10-11 hane olmalıdır.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta giriniz.';
if (!preg_match('/^\d{16}$/', $card)) $errors[] = 'Kart numarası 16 hane olmalıdır.';
if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) $errors[] = 'SKT AA/YY formatında olmalıdır.';
if (!preg_match('/^\d{3}$/', $cvc)) $errors[] = 'CVC 3 haneli olmalıdır.';

// If logged in, ensure required profile fields exist
if (!empty($_SESSION['user_id'])) {
    $db = getDB();
    $st = $db->prepare('SELECT full_name, email FROM User WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        $errors[] = 'Kullanıcı bulunamadı.';
    } else {
        if (empty($u['full_name'])) $errors[] = 'Hesapta ad soyad eksik. Lütfen profilinizi güncelleyin.';
        if (empty($u['email'])) $errors[] = 'Hesapta e-posta eksik. Lütfen profilinizi güncelleyin.';
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: index.php');
    exit;
}

// Kupon kontrolü ve indirim hesaplama
$discount = 0;
$couponId = null;
$finalTotal = (int)$pending['total'];

if ($couponCode !== '') {
    $db = getDB();
    $couponStmt = $db->prepare("SELECT * FROM Coupons WHERE code = ?");
    $couponStmt->execute([$couponCode]);
    $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coupon) {
        // Son kullanma tarihi kontrolü
        if ($coupon['expire_date'] && strtotime($coupon['expire_date']) < time()) {
            $errors[] = 'Kupon süresi dolmuş';
        } else {
            $discount = (float)$coupon['discount'];
            $couponId = $coupon['id'];
            $finalTotal = (int)($pending['total'] * (1 - $discount));
        }
    } else {
        $errors[] = 'Geçersiz kupon kodu';
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: index.php');
    exit;
}

// At this demo stage, simulate payment success and finalize booking
$db = getDB();

try {
    $db->beginTransaction();

    // Giriş kontrolü
    if (empty($_SESSION['user_id'])) {
        $db->rollBack();
        $_SESSION['error_message'] = 'Ödeme yapmak için giriş yapmalısınız';
        header('Location: ../login.php');
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Kullanıcı bakiyesini kontrol et
    $userStmt = $db->prepare("SELECT balance, full_name FROM User WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->rollBack();
        $_SESSION['form_errors'] = ['Kullanıcı bulunamadı.'];
        header('Location: index.php');
        exit;
    }

    $currentBalance = (float)($user['balance'] ?? 0);
    
    if ($currentBalance < $finalTotal) {
        $db->rollBack();
        $_SESSION['form_errors'] = ['Yetersiz bakiye! Mevcut: ' . number_format($currentBalance, 2) . ' ₺, Gerekli: ' . number_format($finalTotal, 2) . ' ₺'];
        header('Location: index.php');
        exit;
    }

    // Reserve seats: ensure still free
    $placeholders = implode(',', array_fill(0, count($pending['seats']), '?'));
    $params = array_merge([$pending['trip_id']], $pending['seats']);
    $stmt = $db->prepare("SELECT seat_number FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? AND t.status = 'active' AND bs.seat_number IN ($placeholders)");
    $stmt->execute($params);
    $taken = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($taken) {
        $db->rollBack();
        $_SESSION['form_errors'] = ['Aşağıdaki koltuklar doldu: ' . implode(', ', $taken)];
        header('Location: index.php');
        exit;
    }

    // Bakiyeden düş
    $newBalance = $currentBalance - $finalTotal;
    $updateBalance = $db->prepare("UPDATE User SET balance = ? WHERE id = ?");
    $updateBalance->execute([$newBalance, $userId]);

    // Create ticket (indirimliyse indirimli tutar kaydediliyor)
    $ticketId = uniqid('ticket-', true);
$insT = $db->prepare('INSERT INTO Tickets (id, trip_id, user_id, total_price, status, created_at) VALUES (?, ?, ?, ?, "active", datetime("now","localtime"))');
$insT->execute([$ticketId, (int)$pending['trip_id'], $userId, $finalTotal]);

    // Kupon kullanımını kaydet
    if ($couponId && !empty($_SESSION['user_id'])) {
        $userCouponId = uniqid('user-coupon-', true);
        $insUC = $db->prepare('INSERT INTO User_Coupons (id, coupon_id, user_id, created_at) VALUES (?, ?, ?, datetime("now","localtime"))');
        $insUC->execute([$userCouponId, $couponId, $userId]);
    }

    // Insert seats with or without gender depending on schema
    $cols = $db->query('PRAGMA table_info(Booked_Seats)')->fetchAll(PDO::FETCH_ASSOC);
    $hasGender = false;
    foreach ($cols as $c) { if (strcasecmp($c['name'] ?? '', 'gender') === 0) { $hasGender = true; break; } }
    if ($hasGender) {
        $insS = $db->prepare('INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at, gender) VALUES (?, ?, ?, datetime("now","localtime"), ?)');
        foreach ($pending['seats'] as $sn) {
            $insS->execute([uniqid('seat-', true), $ticketId, (int)$sn, $pending['gender'] === 'female' ? 'female' : 'male']);
        }
    } else {
        $insS = $db->prepare('INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, datetime("now","localtime"))');
        foreach ($pending['seats'] as $sn) {
            $insS->execute([uniqid('seat-', true), $ticketId, (int)$sn]);
        }
    }

    $db->commit();

    // Clear pending
    unset($_SESSION['pending_purchase']);

    // Success page
    header('Location: success.php?ticket=' . urlencode($ticketId));
    exit;
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('payment finalize error: ' . $e->getMessage());
    $_SESSION['form_errors'] = ['Sunucu hatası, lütfen tekrar deneyin.'];
    header('Location: index.php');
    exit;
}


