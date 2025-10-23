<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz']);
    exit;
}

$companyId = $_SESSION['company_id'] ?? '';
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if ($tripId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'trip_id gerekli']);
    exit;
}

$db = getDB();

// Check ownership
$chk = $db->prepare('SELECT id, price, capacity, bus_type FROM Trips WHERE id = ? AND company_id = ?');
$chk->execute([$tripId, $companyId]);
$trip = $chk->fetch(PDO::FETCH_ASSOC);
if (!$trip) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Sefer bulunamadı']);
    exit;
}

// Occupied seats with gender
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
$occRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$occupied = [];
foreach ($occRows as $r) {
    $occupied[] = [
        'seat' => (int)$r['seat_number'],
        'gender' => ($r['gender'] ?? 'male') === 'female' ? 'female' : 'male'
    ];
}

// Passengers per ticket
$tickets = $db->prepare("SELECT t.id AS ticket_id, t.total_price, t.status, u.full_name, u.email
    FROM Tickets t
    JOIN User u ON t.user_id = u.id
    WHERE t.trip_id = ?
    ORDER BY t.created_at DESC");
$tickets->execute([$tripId]);
$ticketRows = $tickets->fetchAll(PDO::FETCH_ASSOC);

// Seats per ticket
$byTicket = [];
if (!empty($ticketRows)) {
    $ids = array_map(fn($x) => $x['ticket_id'], $ticketRows);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $seats = $db->prepare("SELECT ticket_id, seat_number FROM Booked_Seats WHERE ticket_id IN ($ph) ORDER BY seat_number");
    $seats->execute($ids);
    foreach ($seats->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $byTicket[$s['ticket_id']][] = (int)$s['seat_number'];
    }
}

$passengers = [];
foreach ($ticketRows as $t) {
    $passengers[] = [
        'ticket_id' => $t['ticket_id'],
        'full_name' => $t['full_name'],
        'email' => $t['email'],
        'status' => $t['status'],
        'total_price' => (int)$t['total_price'],
        'seats' => $byTicket[$t['ticket_id']] ?? []
    ];
}

echo json_encode(['success' => true, 'trip' => [
    'id' => (int)$trip['id'],
    'capacity' => (int)$trip['capacity'],
    'bus_type' => $trip['bus_type'] ?: '2+2',
    'price' => (int)$trip['price']
], 'occupied' => $occupied, 'passengers' => $passengers]);
exit;


