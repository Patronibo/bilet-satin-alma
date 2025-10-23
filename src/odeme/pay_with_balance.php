<?php
// Sanal Bakiye ile Ödeme İşlemi
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

// Geriye gidince veya direkt erişimde
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// CSRF koruması
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    $_SESSION['error_message'] = 'Güvenlik hatası. Lütfen tekrar deneyin.';
    header('Location: ../index.php');
    exit;
}

// Session kontrolü
$pending = $_SESSION['pending_purchase'] ?? null;
if (!$pending) {
    $_SESSION['error_message'] = 'Oturum süresi doldu. Lütfen tekrar sefer seçin.';
    header('Location: ../index.php');
    exit;
}

// Giriş kontrolü
if (empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Ödeme yapmak için giriş yapmalısınız';
    header('Location: ../login.php');
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    $userId = $_SESSION['user_id'];
    $trip_id = $pending['trip_id'];
    $seat_number = $pending['seat_number'];
    $gender = $pending['gender'] ?? 'male';
    $ticketPrice = (float)$pending['total'];

    // Kullanıcı bakiyesini kontrol et
    $userStmt = $db->prepare("SELECT balance, full_name FROM User WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->rollBack();
        die("Kullanıcı bulunamadı.");
    }

    $currentBalance = (float)($user['balance'] ?? 0);
    
    if ($currentBalance < $ticketPrice) {
        $db->rollBack();
        die("Yetersiz bakiye! Mevcut: " . number_format($currentBalance, 2) . " ₺, Gerekli: " . number_format($ticketPrice, 2) . " ₺");
    }

    // Koltuk hala müsait mi kontrol et
    $checkSeat = $db->prepare("
        SELECT COUNT(*) FROM Booked_Seats 
        INNER JOIN Tickets ON Tickets.id = Booked_Seats.ticket_id
        WHERE Tickets.trip_id = ? AND Booked_Seats.seat_number = ? AND Tickets.status = 'active'
    ");
    $checkSeat->execute([$trip_id, $seat_number]);

    if ($checkSeat->fetchColumn() > 0) {
        $db->rollBack();
        die("Bu koltuk artık dolu! Lütfen başka bir koltuk seçin.");
    }

    // Bakiyeden düş
    $newBalance = $currentBalance - $ticketPrice;
    $updateBalance = $db->prepare("UPDATE User SET balance = ? WHERE id = ?");
    $updateBalance->execute([$newBalance, $userId]);

    // Bilet oluştur
    $ticket_id = uniqid("ticket-", true);
    $stmt = $db->prepare("
        INSERT INTO Tickets (id, trip_id, user_id, total_price, status, created_at)
        VALUES (?, ?, ?, ?, 'active', datetime('now'))
    ");
    $stmt->execute([$ticket_id, $trip_id, $userId, $ticketPrice]);

    // Koltuğu işaretle
    $seat_id = uniqid("seat-", true);
    $stmt = $db->prepare("
        INSERT INTO Booked_Seats (id, ticket_id, seat_number, gender, created_at)
        VALUES (?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([$seat_id, $ticket_id, $seat_number, $gender]);

    $db->commit();

    // Pending purchase'ı temizle
    unset($_SESSION['pending_purchase']);

    // Success sayfasına yönlendir
    header('Location: success.php?ticket=' . urlencode($ticket_id));
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("pay_with_balance.php error: " . $e->getMessage());
    die("Hata: Bilet oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.");
}

