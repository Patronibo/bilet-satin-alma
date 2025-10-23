<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: /../firma_admin/firma_admin_login.php");
    exit;
}

$company_id = $_SESSION['company_id'];
$message = '';

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// POST geldiğinde sadece bir işlem yapıyoruz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $_SESSION['sefer_message'] = '❌ Geçersiz istek.';
        header("Location: sefer_ekle.php");
        exit;
    }
    
    // ====== SEFER SİLME ======
    if (isset($_POST['delete_trip'])) {
        $trip_id = $_POST['delete_trip'];
        $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
        if ($stmt->execute([$trip_id, $company_id])) {
            $_SESSION['sefer_message'] = "Sefer başarıyla silindi.";
        } else {
            $_SESSION['sefer_message'] = "Sefer silinirken hata oluştu.";
        }
        // POST sonrası yönlendirme ile tekrar gönderimi engelle
        header("Location: sefer_ekle.php");
        exit;
    }

    // ====== SEFER EKLEME ======
    if (isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $bus_type = $_POST['bus_type'] ?? '2+2';
    $bus_plate = trim($_POST['bus_plate'] ?? '');
    $bus_model = trim($_POST['bus_model'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($departure_city && $destination_city && $departure_time && $arrival_time && $price && $capacity) {
        $stmt = $db->prepare("
            INSERT INTO Trips 
            (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, bus_type, bus_plate, bus_model, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$company_id, $departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity, $bus_type, $bus_plate, $bus_model, $description]);
        $_SESSION['sefer_message'] = "Sefer başarıyla eklendi.";
    } else {
        $_SESSION['sefer_message'] = "Lütfen tüm alanları doldurun.";
    }
    header("Location: sefer_ekle.php"); // Yenileme ile duplicate form gönderimini engeller
    exit;
    }
}

// Mesajı session'dan al ve temizle
if (isset($_SESSION['sefer_message'])) {
    $message = $_SESSION['sefer_message'];
    unset($_SESSION['sefer_message']);
}

// ====== SEFERLERİ ÇEKME ======
$stmt = $db->prepare("
    SELECT id, departure_city, destination_city, departure_time, arrival_time, price, capacity, bus_type, bus_plate, bus_model, description, created_date
    FROM Trips 
    WHERE company_id = ? 
    ORDER BY datetime(departure_time) ASC
");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma Admin Paneli - Seferler</title>
<style>
:root {
    --bg: #f4f6f8;
    --dark: #1d1d1f;
    --accent: #0071e3;
    --accent-hover: #005bb5;
    --danger: #dc3545;
    --card: #ffffff;
}
* { 
    box-sizing: border-box; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 0; 
    padding: 0; 
}
body {
    display: flex; 
    height: 100vh; 
    background: var(--bg); 
    color: var(--dark); 
}
.sidebar {
    width: 260px; 
    background: white; 
    border-right: 1px solid rgba(0,0,0,0.1);
    display: flex; 
    flex-direction: column; 
    padding: 25px;
}
.sidebar h2 {
    color: var(--accent); 
    font-size: 1.5rem; 
    margin-bottom: 25px; 
    text-align: center; 
}
.tab-button {
    border: none; 
    background: #f1f3f5; 
    color: var(--dark); 
    padding: 12px;
    border-radius: 10px; 
    margin-bottom: 10px; 
    cursor: pointer;
    transition: all 0.3s ease; 
    text-align: left; 
    font-weight: 500;
    text-decoration: none;
    display: block;
}
.tab-button:hover {
    background: var(--accent); 
    color: #fff;
}
.main-content {
    flex: 1; 
    overflow-y: auto; 
    padding: 30px 40px;
}
header {
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 25px; 
}
header h1 {
    font-size: 1.6rem; 
    font-weight: 600; 
}
header button {
    background: var(--accent); 
    color: white; 
    border: none; 
    border-radius: 8px;
    padding: 10px 18px; 
    font-weight: 500; 
    cursor: pointer; 
    transition: 0.3s;
}
header button:hover {
    background: var(--accent-hover); 
}
.card {
    background: var(--card); 
    padding: 25px; 
    border-radius: 14px; 
    box-shadow: 0 6px 20px rgba(0,0,0,0.05); 
    max-width: 800px; 
    margin-bottom: 20px; 
}
.card h2 {
    margin-bottom: 15px; 
    color: var(--accent); 
}
form {
    margin-top: 20px; 
}
form input, form input[type=datetime-local], form input[type=number], form select {
    width: 100%; 
    padding: 10px; 
    border-radius: 8px; 
    border: 1px solid #ccc; 
    margin-bottom: 10px;
}
button[type="submit"] {
    background: var(--accent);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
button[type="submit"]:hover {
    background: var(--accent-hover);
}
.action-btn.delete {
    background: var(--danger);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s;
}
.action-btn.delete:hover {
    background: #b02a37;
}

/* ================= Modern ve Responsive Tablo ================= */
.table-wrapper {
    overflow-x: auto; /* Mobil için yatay kaydırma */
}

table {
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 10px; 
    font-size: 0.95rem;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

thead {
    background: var(--accent);
    color: #fff;
}

thead th {
    padding: 12px;
    text-align: left;
}

tbody td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

tbody tr:nth-child(even) {
    background: #f9f9f9;
}

tbody tr:hover {
    background: #f1f3f5;
}

.message {
    margin-top: 10px; 
    font-weight: 600; 
    color: green; 
}
</style>
</head>
<body>
    <div class="sidebar">
        <h2>🚌 Firma Paneli</h2>
        <a class="tab-button" href="panel.php">Seferler</a>
        <a class="tab-button" href="sefer_ekle.php">Yeni Sefer Ekle</a>
        <a class="tab-button" href="kupon_yonetimi.php">Kupon Yönetimi</a>
        <a class="tab-button" href="firma_admin_profil.php">Profil</a>
        <a class="tab-button" href="/firma_admin/logout.php">Çıkış Yap</a>
    </div>

    <div class="main-content">
        <header>
            <h1>📅 Mevcut Seferler</h1>
            <button onclick="location.reload()">🔄 Yenile</button>
        </header>

        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div class="card">
            <h2>Sefer Ekle</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="departure_city" placeholder="Kalkış Şehri" required>
                <input type="text" name="destination_city" placeholder="Varış Şehri" required>

                <label>Kalkış Saati</label>
                <input type="datetime-local" name="departure_time" required>

                <label>Varış Saati</label>
                <input type="datetime-local" name="arrival_time" required>

                <input type="number" name="price" placeholder="Fiyat (₺)" required>
                <input type="number" name="capacity" placeholder="Koltuk Sayısı" required>

                <!-- 🚌 Yeni Alanlar -->
                <select name="bus_type" required>
                    <option value="2+2">🚌 2+2 (Standart Otobüs)</option>
                    <option value="2+1">🚌 2+1 (Konfor Otobüs)</option>
                </select>

                <input type="text" name="bus_plate" placeholder="Araç Plakası (Opsiyonel)">
                <input type="text" name="bus_model" placeholder="Otobüs Modeli (Opsiyonel)">
                
                <label>Sefer Detayları (Açıklama)</label>
                <textarea name="description" placeholder="Sefer hakkında detaylı bilgi (mola noktaları, özellikler vb.)" rows="4" style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:10px;font-family:inherit;"></textarea>

                <button type="submit" name="add_trip">Seferi Ekle</button>
            </form>
        </div>

        <div class="card">
    <h2>Mevcut Seferler</h2>
    <?php if (count($trips) === 0): ?>
        <p>Henüz sefer bulunmuyor. Yeni sefer eklemek için yukarıdaki formu kullanabilirsiniz.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Kalkış Saati</th>
                    <th>Varış Saati</th>
                    <th>Fiyat</th>
                    <th>Koltuk</th>
                    <th>Oluşturulma</th>
                    <th>Otobüs Tipi</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                <tr>
                    <td><?= htmlspecialchars($trip['id']) ?></td>
                    <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                    <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                    <td><?= htmlspecialchars($trip['departure_time']) ?></td>
                    <td><?= htmlspecialchars($trip['arrival_time']) ?></td>
                    <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
                    <td><?= htmlspecialchars($trip['capacity']) ?></td>
                    <td><?= htmlspecialchars($trip['created_date']) ?></td>
                    <td><?= htmlspecialchars($trip['bus_type'] ?? '2+2') ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="delete_trip" value="<?= htmlspecialchars($trip['id']) ?>">
                            <button type="submit" class="action-btn delete" onclick="return confirm('Bu seferi silmek istediğine emin misin?')">Sil</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
