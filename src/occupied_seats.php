<?php
// occupied_seats.php
// Secure session cookie params before starting the session
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
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if ($tripId <= 0) {
	http_response_code(400);
	echo json_encode(['success' => false, 'error' => 'trip_id gerekli']);
	exit;
}

try {
    $db = getDB();
    // Detect if gender column exists
    $cols = $db->query('PRAGMA table_info(Booked_Seats)')->fetchAll(PDO::FETCH_ASSOC);
    $hasGender = false;
    foreach ($cols as $c) { if (strcasecmp($c['name'] ?? '', 'gender') === 0) { $hasGender = true; break; } }

    if ($hasGender) {
        $stmt = $db->prepare("SELECT bs.seat_number, COALESCE(bs.gender, 'male') AS gender
            FROM Booked_Seats bs
            JOIN Tickets t ON bs.ticket_id = t.id
            WHERE t.trip_id = ? AND t.status = 'active'");
    } else {
        $stmt = $db->prepare("SELECT bs.seat_number, 'male' AS gender
            FROM Booked_Seats bs
            JOIN Tickets t ON bs.ticket_id = t.id
            WHERE t.trip_id = ? AND t.status = 'active'");
    }
    $stmt->execute([$tripId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $occupied = [];
    foreach ($rows as $r) {
        $occupied[] = [
            'seat' => (int)$r['seat_number'],
            'gender' => ($r['gender'] ?? 'male') === 'female' ? 'female' : 'male'
        ];
    }
    echo json_encode(['success' => true, 'occupied' => $occupied]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Sunucu hatası']);
    exit;
}
