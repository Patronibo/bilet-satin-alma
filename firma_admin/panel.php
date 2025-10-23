<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: /../firma_admin/firma_admin_login.php");
    exit;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$company_id = $_SESSION['company_id'];
$full_name = $_SESSION['full_name'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    // CSRF koruması
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $_SESSION['panel_message'] = "❌ Geçersiz istek.";
    } else {
    $trip_id = $_POST['delete_trip'];
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $_SESSION['panel_message'] = "Sefer başarıyla silindi.";
    }
    
    // POST-Redirect-GET pattern
    header('Location: panel.php');
    exit;
}

// Mesajı session'dan al ve temizle
if (isset($_SESSION['panel_message'])) {
    $message = $_SESSION['panel_message'];
    unset($_SESSION['panel_message']);
}

$stmt = $db->prepare("
    SELECT id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date
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
<title>Firma Admin Paneli</title>
<style>
:root {
    --bg: #f4f6f8;
    --dark: #1d1d1f;
    --accent: #0071e3;
    --accent-hover: #005bb5;
    --danger: #dc3545;
    --card: #ffffff;
    --text-light: #555;
}
* {
    box-sizing: border-box;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
    padding: 0;
}
body {
    display: flex;
    height: 100vh;
    background: var(--bg);
    color: var(--dark);
    overflow: hidden;
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
}
.card h2 {
    margin-bottom: 15px;
    color: var(--accent);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    text-align: left;
}
th {
    color: var(--accent);
    font-weight: 600;
    background: #f8f9fa;
}
tbody tr:hover {
    background: #f1f5ff;
    transition: 0.2s;
}

.action-btn {
    border: none;
    padding: 5px 8px;
    border-radius: 6px;
    cursor: pointer;
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
    transition: 0.3s;
}
.action-btn:hover {
    transform: scale(1.05);
}
.action-btn.delete {
    background: var(--danger);
}
.action-btn.delete:hover {
    background: #bb2d3b;
}

.passenger-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.passenger-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    border-color: #0071e3;
}

/* OTOBÜS GÖRÜNÜMÜ - PROFESYONEL DİKEY DÜZEN */
.trip-layout {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.bus-view-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 18px;
    padding: 25px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.bus-view-header {
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 1.2rem;
    color: #1d1d1f;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 3px solid #0071e3;
}

.bus-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 25px;
    position: relative;
    border: 3px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    max-width: 100%;
    margin: 0 auto;
    overflow-x: auto;
    overflow-y: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
}

.bus-header {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 12px 8px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.75rem;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
    z-index: 10;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    letter-spacing: 2px;
}

.seats-container {
    position: relative;
    min-height: 240px;
    padding: 10px 20px;
    display: inline-block;
}

.seat {
    position: absolute;
    width: 40px;
    height: 40px;
    border: 2px solid #d1d5db;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    color: #374151;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    cursor: default;
}

.seat.male {
    background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
    border-color: #3b82f6;
    color: #1e3a8a;
}

.seat.female {
    background: linear-gradient(135deg, #fce7f3 0%, #f9a8d4 100%);
    border-color: #ec4899;
    color: #831843;
}

.legend {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
}

.legend-box {
    width: 24px;
    height: 24px;
    border-radius: 5px;
    border: 2px solid;
    flex-shrink: 0;
}

.legend-box.available {
    background: #e5e7eb;
    border-color: #d1d5db;
}

.legend-box.male {
    background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
    border-color: #3b82f6;
}

.legend-box.female {
    background: linear-gradient(135deg, #fce7f3 0%, #f9a8d4 100%);
    border-color: #ec4899;
}

.passengers-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 18px;
    padding: 25px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.passenger-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
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
            <h1>Hoş geldin, <?= htmlspecialchars($full_name) ?></h1>
            <button onclick="location.reload()">🔄 Yenile</button>
        </header>

        <div class="card">
            <h2>📅 Mevcut Seferler</h2>

            <?php if (!empty($message)): ?>
                <p style="color:green;font-weight:600;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <?php if (count($trips) === 0): ?>
                <p>Henüz sefer bulunmuyor. <strong>Yeni Sefer Ekle</strong> sekmesinden oluşturabilirsin.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Sefer Detayları</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                    <tr>
                        <td style="padding:20px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                                <div>
                                    <div style="font-size:1.2rem; font-weight:600; color:#1d1d1f; margin-bottom:6px;">
                                        <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>
                                    </div>
                                    <div style="color:#555; font-size:0.95rem;">
                                        ⏰ <?= htmlspecialchars($trip['departure_time']) ?> • <?= (int)$trip['capacity'] ?> koltuk • <?= (int)$trip['price'] ?> ₺
                                    </div>
                                    <div style="color:#999; font-size:0.85rem; margin-top:4px;">Oluşturulma: <?= htmlspecialchars($trip['created_date']) ?></div>
                                </div>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="delete_trip" value="<?= (int)$trip['id'] ?>">
                                    <button type="submit" class="action-btn delete" onclick="return confirm('Bu seferi silmek istediğine emin misin?')">Seferi Sil</button>
                                </form>
                            </div>

                            <div class="trip-layout">
                                <!-- OTOBÜS GÖRÜNÜMÜ -->
                                <div class="bus-view-section">
                                    <div class="bus-view-header">
                                        🚌 Otobüs Yerleşimi (2+1)
                                    </div>
                                    <div class="bus-container">
                                        <div class="bus-header">🚗 ÖN TARAF</div>
                                        <div id="bus-<?= (int)$trip['id'] ?>" class="seats-container">
                                            <!-- JavaScript ile oluşturulacak -->
                                        </div>
                                    </div>
                                    <div class="legend">
                                        <div class="legend-item">
                                            <div class="legend-box available"></div>
                                            <span>Boş</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-box male"></div>
                                            <span>Erkek</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-box female"></div>
                                            <span>Kadın</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- YOLCU LİSTESİ -->
                                <div class="passengers-section">
                                    <div class="bus-view-header" style="border-bottom-color: #10b981;">
                                        👥 Bileti Alan Yolcular
                                    </div>
                                    <div id="passengers-<?= (int)$trip['id'] ?>" class="passenger-grid">
                                        <!-- AJAX ile yüklenecek -->
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
<script>async function loadTrip(_0xf1){const _0xg2=await fetch('trip_data.php?trip_id='+encodeURIComponent(_0xf1),{cache:'no-store'}),_0xh3=await _0xg2.json();if(!_0xh3.success)return;_0xa1(_0xf1,_0xh3.trip.capacity||40,_0xh3.occupied||[]);const _0xi4=document.getElementById('passengers-'+_0xf1);_0xi4.innerHTML='';if(!_0xh3.passengers||_0xh3.passengers.length===0){_0xi4.innerHTML='<p style="color:#999; text-align:center; padding:20px;">Henüz yolcu yok</p>';return}_0xh3.passengers.forEach(_0xj5=>{const _0xk6=document.createElement('div');_0xk6.className='passenger-card';const _0xl7=_0xj5.status==='active'?'active':'canceled',_0xm8=_0xj5.status==='active'?'AKTİF':'İPTAL',_0xn9=_0xj5.status==='active'?'#dcfce7':'#fee2e2',_0xo0=_0xj5.status==='active'?'#166534':'#991b1b';function _0xp1(_0xq2){const _0xr3=document.createElement('div');_0xr3.textContent=_0xq2;return _0xr3.innerHTML}_0xk6.innerHTML='<div style="font-weight:600; font-size:1rem; margin-bottom:8px; color:#1d1d1f;">'+_0xp1(_0xj5.full_name||'-')+'</div>'+'<div style="font-size:0.9rem; color:#555; margin-bottom:4px;">'+_0xp1(_0xj5.email||'-')+'</div>'+'<div style="margin-top:6px;"><strong>Koltuklar:</strong> '+(_0xj5.seats||[]).join(', ')+'</div>'+'<div><strong>Tutar:</strong> '+_0xp1(String(_0xj5.total_price))+' ₺</div>'+'<div style="margin-top:6px;"><span style="background:'+_0xn9+';color:'+_0xo0+';padding:4px 12px;border-radius:6px;font-size:0.85rem;font-weight:600;">'+_0xp1(_0xm8)+'</span></div>';if(_0xj5.status==='active'){const _0xs4=document.createElement('div');_0xs4.textContent='Bileti İptal Et';_0xs4.className='action-btn delete';_0xs4.style.fontSize='0.85rem';_0xs4.style.padding='6px 12px';_0xs4.style.marginTop='12px';_0xs4.onclick=async()=>{if(!confirm('Bu bileti iptal etmek istiyor musunuz?'))return;const _0xt5=new FormData();_0xt5.append('ticket_id',_0xj5.ticket_id);_0xt5.append('csrf_token','<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>');const _0xu6=await fetch('cancel_ticket.php',{method:'POST',body:_0xt5}),_0xv7=await _0xu6.json();_0xv7.success?loadTrip(_0xf1):alert('Hata: '+_0xv7.error)};_0xk6.appendChild(_0xs4)}_0xi4.appendChild(_0xk6)})};function _0xa1(_0xb2,_0xc3,_0xd4){const _0xe5=document.getElementById('bus-'+_0xb2);if(!_0xe5)return;_0xe5.innerHTML='';const _0xf6=43,_0xg7=[10,53],_0xh8=175,_0xi8=10;let _0xi9=1,_0xj0=0;const _0xk1=new Map();_0xd4.forEach(_0xl2=>_0xk1.set(_0xl2.seat,_0xl2.gender||'male'));const _0xz9=Math.ceil(_0xc3/3);const _0xy8=_0xz9*_0xf6;_0xe5.style.width=_0xy8+'px';while(_0xi9<=_0xc3){const _0xm3=_0xi8+(_0xj0*_0xf6);for(let _0xn4=0;_0xn4<2;_0xn4++){if(_0xi9>_0xc3)break;const _0xo5=_0xg7[_0xn4],_0xp6=_0xq7(_0xi9,_0xm3,_0xo5,_0xk1);_0xe5.appendChild(_0xp6);_0xi9++}if(_0xi9<=_0xc3){const _0xr8=_0xh8,_0xs9=_0xq7(_0xi9,_0xm3,_0xr8,_0xk1);_0xe5.appendChild(_0xs9);_0xi9++}_0xj0++}}function _0xq7(_0xt0,_0xu1,_0xv2,_0xw3){const _0xx4=document.createElement('div');_0xx4.className='seat';_0xx4.textContent=_0xt0;_0xx4.style.left=_0xu1+'px';_0xx4.style.top=_0xv2+'px';if(_0xw3.has(_0xt0)){const _0xy5=_0xw3.get(_0xt0);_0xx4.classList.add(_0xy5==='female'?'female':'male')}return _0xx4}<?php foreach ($trips as $trip): ?>loadTrip(<?= (int)$trip['id'] ?>);<?php endforeach; ?></script>
</body>
</html>
