<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz']);
    exit;
}

// CSRF koruması (JSON request için)
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek']);
    exit;
}

$ticketId = (string)($_POST['ticket_id'] ?? '');
if ($ticketId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id gerekli']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    // Validate ticket belongs to this company's trip
    $st = $db->prepare('SELECT t.id FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.id = ? AND tr.company_id = ?');
    $st->execute([$ticketId, $_SESSION['company_id']]);
    if (!$st->fetchColumn()) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Bilet bulunamadı']);
        exit;
    }

    // Mark ticket canceled (seats will be considered free by occupied API)
    $upd = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
    $upd->execute([$ticketId]);
    $db->commit();
    echo json_encode(['success' => true]);
    exit;
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('cancel_ticket error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Sunucu hatası']);
    exit;
}


