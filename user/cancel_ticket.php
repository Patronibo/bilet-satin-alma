<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

// CSRF koruması
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    $_SESSION['error'] = 'Geçersiz istek';
    header('Location: profile.php');
    exit;
}

$ticketId = trim((string)($_POST['ticket_id'] ?? ''));
if ($ticketId === '') {
    $_SESSION['error'] = 'Bilet ID gerekli';
    header('Location: profile.php');
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Bilet bilgilerini çek
    $stmt = $db->prepare("
        SELECT t.id, t.user_id, t.total_price, t.status, t.created_at, tr.departure_time
        FROM Tickets t
        JOIN Trips tr ON t.trip_id = tr.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $db->rollBack();
        $_SESSION['error'] = 'Bilet bulunamadı';
        header('Location: profile.php');
        exit;
    }

    // Yetkili mi kontrol
    if ($ticket['user_id'] !== $_SESSION['user_id']) {
        $db->rollBack();
        $_SESSION['error'] = 'Bu bileti iptal etme yetkiniz yok';
        header('Location: profile.php');
        exit;
    }

    // Zaten iptal edilmiş mi
    if ($ticket['status'] !== 'active') {
        $db->rollBack();
        $_SESSION['error'] = 'Bu bilet zaten iptal edilmiş';
        header('Location: profile.php');
        exit;
    }

    $now = time();
    
    // Sefer saatine 1 saatten az kaldıysa iptal edilemez
    $departureTime = strtotime($ticket['departure_time']);
    $hoursUntilDeparture = ($departureTime - $now) / 3600;

    if ($hoursUntilDeparture < 1) {
        $db->rollBack();
        $_SESSION['error'] = 'Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez';
        header('Location: profile.php');
        exit;
    }

    // Satın alma zamanından itibaren 1 saat içinde mi?
    $purchaseTime = strtotime($ticket['created_at']);
    $hoursSincePurchase = ($now - $purchaseTime) / 3600;
    $isFreeCancel = ($hoursSincePurchase <= 1);

    // Bileti iptal et
    $updateTicket = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
    $updateTicket->execute([$ticketId]);

    // İade işlemi
    if ($isFreeCancel) {
        // Satın alma sonrası 1 saat içinde iptal - Tam iade
        $refundAmount = $ticket['total_price'];
        $updateBalance = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $updateBalance->execute([$refundAmount, $_SESSION['user_id']]);
    } else {
        // 1 saatten sonra iptal - %80 iade (%20 iptal ücreti)
        $refundAmount = (int)($ticket['total_price'] * 0.80);
        $updateBalance = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $updateBalance->execute([$refundAmount, $_SESSION['user_id']]);
    }

    $db->commit();
    
    if ($isFreeCancel) {
        $_SESSION['success'] = 'Bilet başarıyla iptal edildi. Tam ücret (' . $refundAmount . ' TL) hesabınıza iade edildi.';
    } else {
        $_SESSION['success'] = 'Bilet başarıyla iptal edildi. İade tutarı (' . $refundAmount . ' TL) hesabınıza eklendi. (%20 iptal ücreti uygulandı)';
    }
    
    header('Location: profile.php');
    exit;

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Bilet iptal hatası: ' . $e->getMessage());
    $_SESSION['error'] = 'Bilet iptal edilirken bir hata oluştu';
    header('Location: profile.php');
    exit;
}
